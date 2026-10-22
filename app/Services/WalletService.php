<?php

namespace App\Services;
use App\Notifications\CreditsAdded;
use App\Notifications\LowBalance;
use App\Models\Lead;
use App\Models\LeadPricing;
use App\Models\LeadUnlock;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    /**
     * Get or create a wallet for a user.
     */
    public function walletFor(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'lifetime_added' => 0, 'lifetime_spent' => 0]
        );
    }

    /**
     * Add credits to a user's wallet (admin action / purchase / bonus).
     */
 public function credit(
        User $user,
        int $amount,
        string $source = 'admin_credit',
        ?string $reference = null,
        ?string $notes = null,
        ?User $actionedBy = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be positive.');
        }

        $tx = DB::transaction(function () use ($user, $amount, $source, $reference, $notes, $actionedBy) {
            $wallet = Wallet::lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            $wallet->balance += $amount;
            $wallet->lifetime_added += $amount;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'source' => $source,
                'reference' => $reference,
                'notes' => $notes,
                'actioned_by' => $actionedBy?->id,
            ]);
        });

        // Fire notification (outside transaction)
        try {
            $user->notify(new CreditsAdded($amount, $tx->balance_after, $source));
        } catch (\Exception $e) {
            \Log::warning('Credit notification failed: ' . $e->getMessage());
        }

        return $tx;
    }
    /**
     * Move credits between an owner and one of that owner's PG Managers, in one
     * step: both wallets are locked, the total never changes, and each side gets
     * its own history line. $from pays, $to receives.
     */
    public function transferCredits(User $from, User $to, int $amount, ?User $actionedBy = null): array
    {
        if ($amount <= 0) {
            throw new RuntimeException('Enter a number of credits greater than zero.');
        }

        $isOwnersManager = ($to->role === 'pg_manager' && (int) $to->owner_id === (int) $from->id)
            || ($from->role === 'pg_manager' && (int) $from->owner_id === (int) $to->id);
        if (!$isOwnersManager) {
            throw new RuntimeException('Credits can only move between an owner and their own manager.');
        }

        return DB::transaction(function () use ($from, $to, $amount, $actionedBy) {
            // Lock in a fixed order so two transfers never wait on each other.
            $ids = [$from->id, $to->id];
            sort($ids);
            $wallets = [];
            foreach ($ids as $id) {
                $wallets[$id] = Wallet::lockForUpdate()->firstOrCreate(['user_id' => $id], ['balance' => 0]);
            }
            $src = $wallets[$from->id];
            $dst = $wallets[$to->id];

            if ($src->balance < $amount) {
                throw new RuntimeException("Not enough credits. Available: {$src->balance}.");
            }

            $src->balance -= $amount;
            $src->save();
            $dst->balance += $amount;
            $dst->lifetime_added += $amount;
            $dst->save();

            $toManager = $to->role === 'pg_manager';
            WalletTransaction::create([
                'wallet_id' => $src->id,
                'user_id' => $from->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $src->balance,
                'source' => $toManager ? 'manager_transfer_out' : 'manager_transfer_in',
                'notes' => $toManager ? "Given to {$to->name}" : "Returned to {$to->name}",
                'actioned_by' => $actionedBy?->id,
            ]);
            WalletTransaction::create([
                'wallet_id' => $dst->id,
                'user_id' => $to->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $dst->balance,
                'source' => $toManager ? 'manager_transfer_in' : 'manager_transfer_out',
                'notes' => $toManager ? "From {$from->name}" : "Taken back by {$to->name}",
                'actioned_by' => $actionedBy?->id,
            ]);

            return ['from_balance' => $src->balance, 'to_balance' => $dst->balance];
        });
    }

    /**
     * Debit credits from a user's wallet (admin action / expiry).
     */
    public function debit(
        User $user,
        int $amount,
        string $source = 'admin_debit',
        ?string $notes = null,
        ?User $actionedBy = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be positive.');
        }

        $tx = DB::transaction(function () use ($user, $amount, $source, $notes, $actionedBy) {
            $wallet = Wallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->first();

            if (!$wallet || $wallet->balance < $amount) {
                throw new RuntimeException('Insufficient credits to debit.');
            }

            $wallet->balance -= $amount;
            $wallet->lifetime_spent += $amount;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'source' => $source,
                'notes' => $notes,
                'actioned_by' => $actionedBy?->id,
            ]);
        });

        // Low balance nudge — only owners buy credits, and only fire once
        // per dip below threshold (not on every single debit while already low)
        try {
            $lowBalanceThreshold = 50;
            if ($tx->balance_after < $lowBalanceThreshold && ($tx->balance_after + $amount) >= $lowBalanceThreshold) {
                $user->notify(new LowBalance($tx->balance_after));
                if ($user->phone) {
                    app(\App\Services\WhatsAppService::class)->sendTemplate(
                        $user->phone,
                        'wallet_low_balance',
                        [$user->name, $tx->balance_after]
                    );
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Low balance notification failed: ' . $e->getMessage());
        }

        return $tx;
    }

    /**
     * Unlock a lead for a PG owner — atomic operation.
     * - Checks lock status (lead can be unlocked by only one owner)
     * - Checks owner's credit balance
     * - Deducts credits + creates unlock record + locks lead
     * - All-or-nothing (transactional)
     */
    public function unlockLead(User $owner, Lead $lead): array
    {
        // A PG Manager pays from their own wallet, but the lead belongs to their owner.
        $realOwnerId = $owner->role === 'pg_manager' ? (int) $owner->owner_id : (int) $owner->id;

        // Already unlocked by this owner (or one of their managers)? Return success.
        $alreadyUnlocked = \DB::table('lead_unlocks')
            ->where('lead_id', $lead->id)
            ->where(function ($q) use ($owner, $realOwnerId) {
                $q->where('user_id', $owner->id)->orWhere('owner_id', $realOwnerId);
            })
            ->exists();

        if ($alreadyUnlocked) {
            $wallet = $owner->wallet ?? $this->walletFor($owner);
            return [
                'ok' => true,
                'message' => 'Already unlocked.',
                'credits_spent' => 0,
                'balance_remaining' => $wallet->balance,
            ];
        }

        // Get pricing
        $cost = LeadPricing::costFor($lead->lead_type ?? 'direct');
        if ($cost <= 0) {
            throw new RuntimeException('Pricing not configured for this lead type. Please contact admin.');
        }

        $result = DB::transaction(function () use ($owner, $lead, $cost, $realOwnerId) {
            $freshLead = Lead::lockForUpdate()->find($lead->id);
            if (!$freshLead) {
                throw new RuntimeException('Lead no longer exists.');
            }

            // Lock check — anyone else unlocked this lead?
            $lockedByOther = \DB::table('lead_unlocks')
                ->where('lead_id', $freshLead->id)
                ->where('user_id', '!=', $owner->id)
                ->where(function ($q) use ($realOwnerId) {
                    $q->whereNull('owner_id')->orWhere('owner_id', '!=', $realOwnerId);
                })
                // an internal QA/test account unlocking a lead never takes it from real owners
                ->whereNotIn('user_id', DB::table('users')->where('is_test_account', true)->select('id'))
                ->exists();

            if ($lockedByOther) {
                throw new RuntimeException('This lead has already been taken by another owner.');
            }

            // Lock owner's wallet
            $wallet = Wallet::lockForUpdate()
                ->firstOrCreate(['user_id' => $owner->id], ['balance' => 0]);

            if ($wallet->balance < $cost) {
                throw new RuntimeException("Insufficient credits. You need {$cost} credits but have only {$wallet->balance}.");
            }

            // Deduct credits
            $wallet->balance -= $cost;
            $wallet->lifetime_spent += $cost;
            $wallet->save();

            // Lock the lead (not for internal QA/test accounts: their unlock is only a live test,
            // the lead must stay available to real owners and workable by the team)
            $isTestAccount = (bool) $owner->is_test_account
                || ($realOwnerId !== (int) $owner->id && (bool) DB::table('users')->where('id', $realOwnerId)->value('is_test_account'));
            if (!$isTestAccount) {
                $freshLead->is_locked = true;
                $freshLead->locked_by_user_id = $owner->id;
                $freshLead->save();
            }

            // Record the unlock — use BOTH user_id AND owner_id (both exist in your live DB)
            \DB::table('lead_unlocks')->insert([
                'lead_id' => $freshLead->id,
                'user_id' => $owner->id,
                'owner_id' => $realOwnerId,
                'credits_spent' => $cost,
                'lead_type_at_unlock' => $freshLead->lead_type ?? 'direct',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log the transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $owner->id,
                'type' => 'debit',
                'amount' => $cost,
                'balance_after' => $wallet->balance,
                'source' => 'lead_unlock',
                'lead_id' => $freshLead->id,
                'notes' => "Unlocked lead: {$freshLead->name}",
            ]);

   return [
                'ok' => true,
                'message' => 'Lead unlocked successfully.',
                'credits_spent' => $cost,
                'balance_remaining' => $wallet->balance,
            ];
        });

        $this->notifyLeadUnlocked($lead->fresh(), $owner);

        // Low balance nudge right after spending credits on a lead unlock —
        // the most common way an owner's balance actually runs low.
        try {
            $lowBalanceThreshold = 50;
            if ($result['balance_remaining'] < $lowBalanceThreshold) {
                $owner->notify(new LowBalance($result['balance_remaining']));
                if ($owner->phone) {
                    app(\App\Services\WhatsAppService::class)->sendTemplate(
                        $owner->phone,
                        'wallet_low_balance',
                        [$owner->name, $result['balance_remaining']]
                    );
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Low balance notification failed: ' . $e->getMessage());
        }

        return $result;
    }

    private function notifyLeadUnlocked(Lead $lead, User $owner): void
    {
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\LeadUnlockedByOwner($lead, $owner));
            }
            if ($lead->assigned_telecaller_id) {
                $tc = User::find($lead->assigned_telecaller_id);
                $tc?->notify(new \App\Notifications\LeadUnlockedByOwner($lead, $owner));
            }
        } catch (\Exception $e) {
            \Log::warning('Lead unlock notification failed: ' . $e->getMessage());
        }
    }
}