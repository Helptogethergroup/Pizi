<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LeadMatchingService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Morning WhatsApp (11:30 AM — see routes/console.php): tells each owner how
 * many NEW tenant leads reached them in the last 24 hours (same matching
 * logic as their dashboard — LeadMatchingService). Sent in the morning
 * instead of at night so owners actually see and act on it, not sleeping
 * through it. Owners with zero leads in that window are skipped — no message.
 */
class SendOwnerDailyLeadCount extends Command
{
    protected $signature = 'owner:send-daily-lead-count';
    protected $description = "Send each owner a WhatsApp with their last-24-hours new lead count (skips owners with 0 leads)";

    public function handle(LeadMatchingService $matcher, WhatsAppService $whatsapp): int
    {
        $since = now()->subDay();
        $todayLabel = now()->format('d M Y');

        $owners = User::where('role', 'owner')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->whereHas('properties', fn ($q) => $q->where('is_active', true))
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($owners as $owner) {
            $leadsLast24h = $matcher->leadsForOwner($owner, 500)
                ->filter(fn ($lead) => $lead->created_at && $lead->created_at->greaterThanOrEqualTo($since))
                ->count();

            if ($leadsLast24h < 1) {
                $skipped++;
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $owner->phone,
                'owner_daily_lead_count',
                [$owner->name, $leadsLast24h, $todayLabel]
            );

            if ($result['ok']) {
                $sent++;
            }
        }

        $this->info("Sent to {$sent} owner(s) with new leads in the last 24h. Skipped {$skipped} owner(s) with 0 leads.");
        return self::SUCCESS;
    }
}
