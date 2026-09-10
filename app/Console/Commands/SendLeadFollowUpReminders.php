<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Notifications\LeadFollowUpDue;
use App\Notifications\LeadGoingStale;
use Illuminate\Console\Command;

class SendLeadFollowUpReminders extends Command
{
    protected $signature = 'leads:check-followups {--stale-days=5 : Days without contact before a lead is flagged as going cold}';
    protected $description = 'Notify telecallers about leads due for follow-up today, and leads going cold from no contact';

    private const CLOSED_STATUSES = ['closed_won', 'deal_closed'];

    public function handle(): int
    {
        $followUpsSent = 0;
        $staleSent = 0;

        // Follow-up due today (or overdue), not yet closed
        $dueLeads = Lead::whereNotNull('next_follow_up_at')
            ->whereDate('next_follow_up_at', '<=', now()->toDateString())
            ->whereNotIn('status', self::CLOSED_STATUSES)
            ->whereNotNull('assigned_telecaller_id')
            ->with('telecaller')
            ->get();

        foreach ($dueLeads as $lead) {
            if ($telecaller = $lead->telecaller) {
                $telecaller->notify(new LeadFollowUpDue($lead));
                $followUpsSent++;
            }
        }

        // Going cold — no contact in N days, still open, has an owner (telecaller).
        // Only alert once (stale_notified_at) — re-alerts if last_contacted_at
        // later moves forward (telecaller re-engaged, then went quiet again).
        $staleDays = (int) $this->option('stale-days');
        $staleLeads = Lead::whereNotIn('status', self::CLOSED_STATUSES)
            ->whereNotNull('assigned_telecaller_id')
            ->where(function ($q) use ($staleDays) {
                $q->whereNull('last_contacted_at')
                  ->orWhereDate('last_contacted_at', '<=', now()->subDays($staleDays)->toDateString());
            })
            ->where('created_at', '<=', now()->subDays($staleDays))
            ->where(function ($q) {
                $q->whereNull('stale_notified_at')
                  ->orWhereColumn('stale_notified_at', '<', 'last_contacted_at');
            })
            ->with('telecaller')
            ->get();

        foreach ($staleLeads as $lead) {
            if ($telecaller = $lead->telecaller) {
                $daysSince = abs($lead->last_contacted_at
                    ? now()->diffInDays($lead->last_contacted_at)
                    : now()->diffInDays($lead->created_at));
                $telecaller->notify(new LeadGoingStale($lead, (int) $daysSince));
                $lead->update(['stale_notified_at' => now()]);
                $staleSent++;
            }
        }

        $this->info("Sent {$followUpsSent} follow-up reminders, {$staleSent} stale-lead alerts.");
        return self::SUCCESS;
    }
}
