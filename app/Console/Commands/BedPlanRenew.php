<?php

namespace App\Console\Commands;

use App\Models\BedSubscription;
use App\Models\User;
use App\Notifications\BedPlanNotice;
use App\Services\BedBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Hourly. Owners whose Bed Plan month has ended are charged for the next month from their Bed Wallet, or, if
 * the wallet cannot pay, their plan ends and they are restricted at once. Owners whose month ends within
 * 3 days and whose wallet will not cover it get one reminder (per month). Safe to run again and again.
 */
class BedPlanRenew extends Command
{
    protected $signature = 'bed-plan:renew';
    protected $description = 'Renew Bed Plan months that have ended, or end the plan when the Bed Wallet cannot pay';

    public function handle(BedBillingService $billing): int
    {
        if (!$billing->enabled()) {
            $this->info('Bed Plan is switched off. Nothing to do.');

            return self::SUCCESS;
        }

        $renewed = $expired = 0;
        BedSubscription::where('status', 'active')->where('cycle_end', '<=', now())->orderBy('id')->chunkById(100, function ($subs) use ($billing, &$renewed, &$expired) {
            foreach ($subs as $sub) {
                try {
                    $result = $billing->renewDue($sub);
                } catch (\Throwable $e) {
                    \Log::error("Bed plan renewal failed for subscription {$sub->id}: " . $e->getMessage());
                    continue;
                }
                $renewed += $result === 'renewed' ? 1 : 0;
                $expired += $result === 'expired' ? 1 : 0;
            }
        });

        $reminded = 0;
        BedSubscription::where('status', 'active')
            ->whereBetween('cycle_end', [now(), now()->addDays(3)])
            ->orderBy('id')
            ->chunkById(100, function ($subs) use ($billing, &$reminded) {
                foreach ($subs as $sub) {
                    $key = "bed_plan_reminder_{$sub->id}_" . $sub->cycle_end->timestamp;
                    if (Cache::has($key)) {
                        continue;
                    }
                    $cost = round($billing->bedCount($sub->user_id) * $billing->pricePerBed($sub), 2);
                    $balance = (float) $billing->walletFor($sub->user_id)->balance;
                    if ($cost <= 0 || $balance >= $cost) {
                        continue;
                    }
                    try {
                        User::find($sub->user_id)?->notify(new BedPlanNotice(
                            'low',
                            'Your Bed Plan renews on ' . $sub->cycle_end->format('d M') . ' for ₹' . number_format($cost, 2)
                            . ', and your Bed Wallet has ₹' . number_format($balance, 2) . '. Add at least ₹'
                            . number_format($cost - $balance, 2) . ' before then, or your plan will end and you cannot add rooms, beds or tenants.'
                        ));
                        Cache::put($key, true, now()->addDays(5));
                        $reminded++;
                    } catch (\Throwable $e) {
                        \Log::warning('Bed plan reminder failed: ' . $e->getMessage());
                    }
                }
            });

        $this->info("Renewed {$renewed}, ended {$expired}, reminded {$reminded}.");

        return self::SUCCESS;
    }
}
