<?php

namespace App\Services;

use App\Models\BedPlan;
use App\Models\BedSubscription;
use App\Models\BedWallet;
use App\Models\BedWalletTransaction;
use App\Models\User;
use App\Notifications\BedPlanNotice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bed Plan billing.
 *
 * An owner on the plan pays, from the Bed Wallet, the monthly rate for EVERY bed they have (empty or full,
 * there is no vacancy discount). The rate has the GST inside it. The month starts the day the plan is
 * switched on. There is no free period and no grace period: when a month ends and the wallet cannot pay the
 * next one, the owner is restricted at once (cannot add rooms, beds or tenants; everything else keeps working
 * and no data is ever hidden) until the wallet is recharged, which renews the plan on the spot.
 *
 * A bed added in the middle of a month costs the full month at the moment it is added. A deleted bed is
 * not refunded, but its paid slot can be used again within the same month.
 *
 * Owners without a subscription row are not on the plan: nothing is charged and nothing is restricted.
 */
class BedBillingService
{
    // ── settings ─────────────────────────────────────────────────────────

    public function setting(string $key, $default = null)
    {
        $row = DB::table('bed_plan_settings')->where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    public function setSetting(string $key, $value): void
    {
        DB::table('bed_plan_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /** The emergency switch: off means nothing is charged and nobody is restricted. */
    public function enabled(): bool
    {
        return (string) $this->setting('enabled', '1') === '1';
    }

    // ── money helpers (rupees with 2 decimals; compared in paise so nothing drifts) ──

    public static function paise($rupees): int
    {
        return (int) round(((float) $rupees) * 100);
    }

    public static function rupees(int $paise): float
    {
        return round($paise / 100, 2);
    }

    /** The GST that sits inside a GST-inclusive amount. */
    public static function gstInside(float $amount, float $gstPercent): float
    {
        return round($amount - ($amount / (1 + $gstPercent / 100)), 2);
    }

    // ── lookups ──────────────────────────────────────────────────────────

    public function walletFor(int $ownerId): BedWallet
    {
        return BedWallet::firstOrCreate(['user_id' => $ownerId], ['balance' => 0, 'lifetime_added' => 0, 'lifetime_spent' => 0]);
    }

    public function subscriptionFor(int $ownerId): ?BedSubscription
    {
        return BedSubscription::where('user_id', $ownerId)->first();
    }

    public function defaultPlan(): ?BedPlan
    {
        return BedPlan::where('is_active', true)->where('is_default', true)->first()
            ?? BedPlan::where('is_active', true)->orderBy('id')->first();
    }

    /** Price of one bed for one month (GST included): the owner's own price, else the plan's, else the default plan's. */
    public function pricePerBed(BedSubscription $sub): float
    {
        if ($sub->custom_price_per_bed !== null) {
            return (float) $sub->custom_price_per_bed;
        }
        $plan = $sub->plan ?? $this->defaultPlan();

        return $plan ? (float) $plan->price_per_bed : 0.0;
    }

    public function gstPercent(BedSubscription $sub): float
    {
        $plan = $sub->plan ?? $this->defaultPlan();

        return $plan ? (float) $plan->gst_percent : 18.0;
    }

    /** Every bed the owner has (empty or occupied). */
    public function bedCount(int $ownerId): int
    {
        return (int) DB::table('beds')->where('owner_id', $ownerId)->whereNull('deleted_at')->count();
    }

    /** The numbers the owner page and the admin page show. */
    public function summary(int $ownerId): array
    {
        $sub = $this->subscriptionFor($ownerId);
        $wallet = $this->walletFor($ownerId);
        $beds = $this->bedCount($ownerId);
        $price = $sub ? $this->pricePerBed($sub) : 0.0;

        return [
            'sub' => $sub,
            'wallet' => $wallet,
            'beds' => $beds,
            'price' => $price,
            'renewal_cost' => round($beds * $price, 2),
            'gst_percent' => $sub ? $this->gstPercent($sub) : 0,
            'on_plan' => (bool) $sub && $sub->status !== 'disabled',
            'restricted' => $this->isRestricted($ownerId),
        ];
    }

    public function isRestricted(int $ownerId): bool
    {
        if (!$this->enabled()) {
            return false;
        }
        $sub = $this->subscriptionFor($ownerId);

        return $sub && $sub->status === 'expired';
    }

    // ── wallet movements ─────────────────────────────────────────────────

    /**
     * Put money in. With an idempotency key the same top-up can be booked only once (a Razorpay order that
     * is verified twice, for example, credits the wallet a single time).
     */
    public function credit(int $ownerId, float $amount, string $source, ?string $reference = null, ?string $notes = null, ?User $by = null, ?string $idempotencyKey = null): BedWalletTransaction
    {
        if (self::paise($amount) <= 0) {
            throw new RuntimeException('The amount must be more than zero.');
        }

        $tx = DB::transaction(function () use ($ownerId, $amount, $source, $reference, $notes, $by, $idempotencyKey) {
            if ($idempotencyKey && ($existing = BedWalletTransaction::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing;
            }
            $wallet = BedWallet::lockForUpdate()->firstOrCreate(['user_id' => $ownerId], ['balance' => 0]);
            $wallet->balance = self::rupees(self::paise($wallet->balance) + self::paise($amount));
            $wallet->lifetime_added = self::rupees(self::paise($wallet->lifetime_added) + self::paise($amount));
            $wallet->save();

            return BedWalletTransaction::create([
                'wallet_id' => $wallet->id, 'user_id' => $ownerId, 'type' => 'credit',
                'amount' => round($amount, 2), 'balance_after' => $wallet->balance,
                'source' => $source, 'reference' => $reference, 'notes' => $notes,
                'idempotency_key' => $idempotencyKey, 'actioned_by' => $by?->id,
            ]);
        });

        // money in: if the plan had ended, renew it now
        $this->recover($ownerId);

        return $tx;
    }

    /** Take money out. Fails (nothing changes) when the balance is not enough. Inside the caller's transaction. */
    private function debit(int $ownerId, float $amount, string $source, ?int $beds = null, ?float $gst = null, ?string $notes = null, ?User $by = null, ?string $idempotencyKey = null): BedWalletTransaction
    {
        if ($idempotencyKey && ($existing = BedWalletTransaction::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }
        $wallet = BedWallet::lockForUpdate()->where('user_id', $ownerId)->first();
        if (!$wallet || self::paise($wallet->balance) < self::paise($amount)) {
            throw new RuntimeException('Not enough balance in the Bed Wallet.');
        }
        $wallet->balance = self::rupees(self::paise($wallet->balance) - self::paise($amount));
        $wallet->lifetime_spent = self::rupees(self::paise($wallet->lifetime_spent) + self::paise($amount));
        $wallet->save();

        return BedWalletTransaction::create([
            'wallet_id' => $wallet->id, 'user_id' => $ownerId, 'type' => 'debit',
            'amount' => round($amount, 2), 'balance_after' => $wallet->balance,
            'source' => $source, 'beds' => $beds, 'gst_amount' => $gst, 'notes' => $notes,
            'idempotency_key' => $idempotencyKey, 'actioned_by' => $by?->id,
        ]);
    }

    /** The admin takes money out by hand. */
    public function adminDebit(int $ownerId, float $amount, string $notes, User $by): BedWalletTransaction
    {
        return DB::transaction(function () use ($ownerId, $amount, $notes, $by) {
            $this->walletFor($ownerId);

            return $this->debit($ownerId, $amount, 'admin_debit', null, null, $notes, $by);
        });
    }

    // ── the plan ─────────────────────────────────────────────────────────

    /**
     * Switch the plan on for an owner (or switch it on again). The month starts now and the beds the owner has
     * are charged at once. If the wallet cannot pay, the plan starts as "expired": the owner is restricted
     * until they recharge.
     */
    public function activate(int $ownerId, ?int $planId = null, ?float $customPrice = null, ?User $by = null): BedSubscription
    {
        $this->walletFor($ownerId);

        $sub = DB::transaction(function () use ($ownerId, $planId, $customPrice) {
            $sub = BedSubscription::lockForUpdate()->firstOrNew(['user_id' => $ownerId]);
            if ($planId !== null || !$sub->exists) {
                $sub->bed_plan_id = $planId ?? $this->defaultPlan()?->id;
            }
            if ($customPrice !== null || !$sub->exists) {
                $sub->custom_price_per_bed = $customPrice;
            }
            $sub->started_at = $sub->started_at ?? now();
            $sub->status = 'expired'; // until the first month is paid below
            $sub->paid_beds = 0;
            $sub->cycle_start = now();
            $sub->cycle_end = now();
            $sub->save();

            return $sub;
        });

        $this->startCycleNow($sub->id, 'activate');

        return $sub->fresh();
    }

    /** The admin switches the plan off for one owner: no charge, no restriction. */
    public function disable(int $ownerId): void
    {
        BedSubscription::where('user_id', $ownerId)->update(['status' => 'disabled', 'updated_at' => now()]);
    }

    /**
     * Start a new month from now and charge the beds the owner has. Returns true when it was paid.
     * Used when the plan is switched on and when a recharge brings an ended plan back.
     */
    private function startCycleNow(int $subscriptionId, string $why): bool
    {
        return DB::transaction(function () use ($subscriptionId, $why) {
            $sub = BedSubscription::lockForUpdate()->find($subscriptionId);
            if (!$sub || $sub->status === 'disabled') {
                return false;
            }
            $beds = $this->bedCount($sub->user_id);
            $price = $this->pricePerBed($sub);
            $cost = round($beds * $price, 2);

            if (self::paise($cost) > 0) {
                $wallet = BedWallet::lockForUpdate()->where('user_id', $sub->user_id)->first();
                if (!$wallet || self::paise($wallet->balance) < self::paise($cost)) {
                    $sub->status = 'expired';
                    $sub->save();

                    return false;
                }
                $this->debit($sub->user_id, $cost, 'plan_renewal', $beds,
                    self::gstInside($cost, $this->gstPercent($sub)),
                    "Bed plan: {$beds} bed(s) x ₹" . number_format($price, 2) . ' for one month',
                    null, "cycle:{$sub->id}:" . now()->timestamp . ":{$why}");
            }

            $sub->status = 'active';
            $sub->paid_beds = $beds;
            $sub->cycle_start = now();
            $sub->cycle_end = now()->addMonthNoOverflow();
            $sub->last_renewed_at = now();
            $sub->save();

            return true;
        });
    }

    /** After money came in: if the plan had ended, try to bring it back now. */
    public function recover(int $ownerId): void
    {
        $sub = $this->subscriptionFor($ownerId);
        if ($sub && $sub->status === 'expired' && $this->startCycleNow($sub->id, 'recover')) {
            $this->tell($ownerId, 'renewed', 'Your Bed Wallet paid for your beds and your Bed Plan is active again.');
        }
    }

    /**
     * The scheduled job: a month has ended. Pay the next month from the wallet, or end the plan so the owner
     * is restricted at once. Safe to run again and again: a month that is already paid is skipped.
     */
    public function renewDue(BedSubscription $subscription): string
    {
        $result = DB::transaction(function () use ($subscription) {
            $sub = BedSubscription::lockForUpdate()->find($subscription->id);
            if (!$sub || $sub->status !== 'active' || !$sub->cycle_end || $sub->cycle_end->isFuture()) {
                return 'skipped';
            }

            $beds = $this->bedCount($sub->user_id);
            $price = $this->pricePerBed($sub);
            $cost = round($beds * $price, 2);

            if (self::paise($cost) > 0) {
                $wallet = BedWallet::lockForUpdate()->where('user_id', $sub->user_id)->first();
                if (!$wallet || self::paise($wallet->balance) < self::paise($cost)) {
                    $sub->status = 'expired';
                    $sub->save();

                    return 'expired';
                }
                $this->debit($sub->user_id, $cost, 'plan_renewal', $beds,
                    self::gstInside($cost, $this->gstPercent($sub)),
                    "Bed plan: {$beds} bed(s) x ₹" . number_format($price, 2) . ' for one month',
                    null, "renew:{$sub->id}:" . $sub->cycle_end->timestamp);
            }

            // keep the owner's own day of the month; if the job was down for months, start from now
            $next = $sub->cycle_end->copy()->addMonthNoOverflow();
            $sub->cycle_start = $next->isPast() ? now() : $sub->cycle_end;
            $sub->cycle_end = $next->isPast() ? now()->addMonthNoOverflow() : $next;
            $sub->paid_beds = $beds;
            $sub->last_renewed_at = now();
            $sub->save();

            return 'renewed';
        });

        if ($result === 'expired') {
            $this->tell($subscription->user_id, 'expired', 'Your Bed Plan month has ended and the Bed Wallet balance is not enough. Recharge your Bed Wallet now to add rooms, beds and tenants again.');
        } elseif ($result === 'renewed') {
            $this->tell($subscription->user_id, 'renewed', 'Your Bed Plan was renewed for another month from your Bed Wallet.');
        }

        return $result;
    }

    // ── rules the rest of the site asks about ────────────────────────────

    /**
     * Before an owner adds rooms, beds or tenants: null when it is fine, otherwise the message to show.
     */
    public function operateBlock(int $ownerId): ?string
    {
        return $this->isRestricted($ownerId)
            ? 'Your Bed Plan has ended. Recharge your Bed Wallet to add rooms, beds and tenants. Everything else keeps working.'
            : null;
    }

    /**
     * Before $add new beds are created. A bed costs a full month at the moment it is added, unless a slot
     * that is already paid for this month is free (a deleted bed leaves its slot behind). Charges now.
     *
     * @return array{error: ?string, slots: int, amount: float} error is null when the beds may be created;
     *         slots and amount say what was charged so [[releaseReservation]] can undo it if creating fails.
     */
    public function reserveBeds(int $ownerId, int $add): array
    {
        $none = ['error' => null, 'slots' => 0, 'amount' => 0.0];
        if ($add <= 0 || !$this->enabled()) {
            return $none;
        }
        $sub = $this->subscriptionFor($ownerId);
        if (!$sub || $sub->status === 'disabled') {
            return $none;
        }
        if ($sub->status === 'expired') {
            return ['error' => $this->operateBlock($ownerId), 'slots' => 0, 'amount' => 0.0];
        }

        return DB::transaction(function () use ($ownerId, $add, $sub) {
            $sub = BedSubscription::lockForUpdate()->find($sub->id);
            $beds = $this->bedCount($ownerId);
            $newSlots = max(0, $beds + $add - (int) $sub->paid_beds);
            if ($newSlots === 0) {
                return ['error' => null, 'slots' => 0, 'amount' => 0.0];
            }

            $price = $this->pricePerBed($sub);
            $cost = round($newSlots * $price, 2);
            $wallet = BedWallet::lockForUpdate()->firstOrCreate(['user_id' => $ownerId], ['balance' => 0]);
            if (self::paise($wallet->balance) < self::paise($cost)) {
                $short = self::rupees(self::paise($cost) - self::paise($wallet->balance));

                return ['error' => "Adding {$add} bed(s) costs ₹" . number_format($cost, 2) . ' for a full month, and your Bed Wallet has ₹'
                    . number_format((float) $wallet->balance, 2) . '. Add at least ₹' . number_format($short, 2) . ' to your Bed Wallet to continue.',
                    'slots' => 0, 'amount' => 0.0];
            }

            $this->debit($ownerId, $cost, 'new_beds', $newSlots,
                self::gstInside($cost, $this->gstPercent($sub)),
                "{$newSlots} new bed(s) x ₹" . number_format($price, 2) . ' (full month)');
            $sub->paid_beds = (int) $sub->paid_beds + $newSlots;
            $sub->save();

            return ['error' => null, 'slots' => $newSlots, 'amount' => $cost];
        });
    }

    /** Beds could not be created after being paid for: give the money back. */
    public function releaseReservation(int $ownerId, array $reservation): void
    {
        if (($reservation['slots'] ?? 0) <= 0) {
            return;
        }
        DB::transaction(function () use ($ownerId, $reservation) {
            $sub = $this->subscriptionFor($ownerId);
            if ($sub) {
                $sub->paid_beds = max(0, (int) $sub->paid_beds - (int) $reservation['slots']);
                $sub->save();
            }
            $wallet = BedWallet::lockForUpdate()->where('user_id', $ownerId)->first();
            $amount = (float) $reservation['amount'];
            $wallet->balance = self::rupees(self::paise($wallet->balance) + self::paise($amount));
            $wallet->lifetime_spent = self::rupees(max(0, self::paise($wallet->lifetime_spent) - self::paise($amount)));
            $wallet->save();
            BedWalletTransaction::create([
                'wallet_id' => $wallet->id, 'user_id' => $ownerId, 'type' => 'credit', 'amount' => $amount,
                'balance_after' => $wallet->balance, 'source' => 'refund',
                'notes' => 'The beds could not be created, so the charge was returned.',
            ]);
        });
    }

    private function tell(int $ownerId, string $kind, string $message): void
    {
        try {
            User::find($ownerId)?->notify(new BedPlanNotice($kind, $message));
        } catch (\Throwable $e) {
            \Log::warning('Bed plan notice failed: ' . $e->getMessage());
        }
    }
}
