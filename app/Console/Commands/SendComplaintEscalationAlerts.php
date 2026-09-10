<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ComplaintEscalated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendComplaintEscalationAlerts extends Command
{
    protected $signature = 'complaints:check-escalated {--days=3 : Days unresolved before alerting admins}';
    protected $description = 'Alert admins about complaints still open after N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $complaints = DB::table('complaints')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->where('created_at', '<=', now()->subDays($days))
            ->where(function ($q) {
                // Only re-alert once a day, not on every scheduler tick
                $q->whereNull('escalation_notified_at')
                  ->orWhereDate('escalation_notified_at', '!=', now()->toDateString());
            })
            ->get();

        $admins = User::where('role', 'admin')->get();
        $sent = 0;

        foreach ($complaints as $complaint) {
            $daysOpen = now()->diffInDays($complaint->created_at);
            foreach ($admins as $admin) {
                $admin->notify(new ComplaintEscalated(
                    $complaint->id,
                    $complaint->ticket_number,
                    $complaint->title,
                    (int) $daysOpen
                ));
            }
            DB::table('complaints')->where('id', $complaint->id)->update(['escalation_notified_at' => now()]);
            $sent++;
        }

        $this->info("Checked complaints, escalated {$sent} unresolved ticket(s) to " . $admins->count() . ' admin(s).');
        return self::SUCCESS;
    }
}
