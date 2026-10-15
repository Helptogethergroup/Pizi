<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\LeadsWaiting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Leads the owner paid credits for and then left untouched are the most
 * wasted money on the platform: remind them, at most every 2 days.
 */
class SendOwnerLeadsWaitingAlerts extends Command
{
    protected $signature = 'owner:notify-leads-waiting {--days=3 : Days since the lead was unlocked}';
    protected $description = 'Remind owners about unlocked leads they have not followed up on';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $waiting = DB::table('lead_unlocks as u')
            ->join('leads as l', 'l.id', '=', 'u.lead_id')
            ->where('u.created_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->whereNull('l.status')->orWhereIn('l.status', ['new_lead', 'open']);
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('lead_status_history as h')
                    ->whereColumn('h.lead_id', 'u.lead_id')
                    ->whereColumn('h.updated_by', 'u.user_id');
            })
            ->select('u.user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('u.user_id')
            ->get();

        $sent = 0;
        foreach ($waiting as $row) {
            $key = 'leads_waiting_alert_' . $row->user_id;
            if (Cache::has($key)) {
                continue;
            }
            $owner = User::where('id', $row->user_id)->where('role', 'owner')->where('is_active', true)->first();
            if (!$owner) {
                continue;
            }
            $owner->notify(new LeadsWaiting((int) $row->total));
            Cache::put($key, true, now()->addDays(2));
            $sent++;
        }

        $this->info("Sent {$sent} leads-waiting alerts.");
        return self::SUCCESS;
    }
}
