<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LeadMatchingService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * End-of-day WhatsApp: tells each owner how many NEW tenant leads reached
 * them today (same matching logic as their dashboard — LeadMatchingService).
 * Owners who got zero leads today are skipped entirely — no message.
 */
class SendOwnerDailyLeadCount extends Command
{
    protected $signature = 'owner:send-daily-lead-count';
    protected $description = "Send each owner a WhatsApp with today's new lead count (skips owners with 0 leads)";

    public function handle(LeadMatchingService $matcher, WhatsAppService $whatsapp): int
    {
        $today = now()->toDateString();
        $todayLabel = now()->format('d M Y');

        $owners = User::where('role', 'owner')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->whereHas('properties', fn ($q) => $q->where('is_active', true))
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($owners as $owner) {
            $leadsToday = $matcher->leadsForOwner($owner, 500)
                ->filter(fn ($lead) => $lead->created_at && $lead->created_at->toDateString() === $today)
                ->count();

            if ($leadsToday < 1) {
                $skipped++;
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $owner->phone,
                'owner_daily_lead_count',
                [$owner->name, $leadsToday, $todayLabel]
            );

            if ($result['ok']) {
                $sent++;
            }
        }

        $this->info("Sent to {$sent} owner(s) with new leads today. Skipped {$skipped} owner(s) with 0 leads.");
        return self::SUCCESS;
    }
}
