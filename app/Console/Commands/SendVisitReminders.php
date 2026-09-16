<?php

namespace App\Console\Commands;

use App\Models\FieldVisit;
use App\Notifications\VisitReminder;
use Illuminate\Console\Command;

class SendVisitReminders extends Command
{
    protected $signature = 'visits:send-reminders {--minutes=30 : How soon before scheduled_at counts as "starting soon"}';
    protected $description = 'Notify field executives about scheduled visits starting within the next N minutes';

    public function handle(): int
    {
        $window = (int) $this->option('minutes');
        $now = now();
        $cutoff = $now->copy()->addMinutes($window);

        $visits = FieldVisit::where('status', 'scheduled')
            ->whereNull('reminder_sent_at')
            ->whereBetween('scheduled_at', [$now, $cutoff])
            ->with('fieldExecutive', 'property')
            ->get();

        $sent = 0;

        foreach ($visits as $visit) {
            if (!$visit->fieldExecutive) {
                continue;
            }

            try {
                $visit->fieldExecutive->notify(new VisitReminder($visit));
                $visit->update(['reminder_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                \Log::warning('Visit reminder failed: ' . $e->getMessage(), ['visit_id' => $visit->id]);
            }
        }

        $this->info("Sent {$sent} visit reminder(s).");
        return self::SUCCESS;
    }
}
