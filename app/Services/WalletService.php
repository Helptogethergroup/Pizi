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

        return DB::transaction(function () use ($user, $amount, $source, $notes, $actionedBy) {
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
        // Already unlocked by this owner? Return success without re-charging.
        if ($lead->isUnlockedBy($owner->id)) {
            return ['ok' => true, 'message' => 'Already unlocked.', 'credits_spent' => 0];
        }

        // Get pricing for this lead type
        $cost = LeadPricing::costFor($lead->lead_type ?? 'direct');
        if ($cost <= 0) {
            throw new RuntimeException('Pricing not configured for this lead type.');
        }

        return DB::transaction(function () use ($owner, $lead, $cost) {
            // Lock the lead row to prevent two owners unlocking simultaneously
            $freshLead = Lead::lockForUpdate()->find($lead->id);
            if (!$freshLead) {
                throw new RuntimeException('Lead no longer exists.');
            }

            // Lock check — if locked by someone else, deny
            if ($freshLead->is_locked && $freshLead->locked_by_user_id !== $owner->id) {
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

            // Lock the lead
            $freshLead->is_locked = true;
            $freshLead->locked_by_user_id = $owner->id;
            $freshLead->save();

            // Record the unlock
            LeadUnlock::create([
                'lead_id' => $freshLead->id,
                'user_id' => $owner->id,
                'credits_spent' => $cost,
                'lead_type_at_unlock' => $freshLead->lead_type ?? 'direct',
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

            // Low balance warning
            if ($wallet->balance < 50 && $wallet->balance > 0) {
                try {
                    $owner->notify(new LowBalance($wallet->balance));
                } catch (\Exception $e) {
                    \Log::warning('Low balance notification failed: ' . $e->getMessage());
                }
            }

            return [
                'ok' => true,
                'message' => 'Lead unlocked successfully.',
                'credits_spent' => $cost,
                'balance_remaining' => $wallet->balance,
            ];
        });
    }
}