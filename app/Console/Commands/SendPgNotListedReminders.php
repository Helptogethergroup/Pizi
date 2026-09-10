<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendPgNotListedReminders extends Command
{
    protected $signature = 'owner:send-pg-reminder {--days=1 : Days since signup (or since last reminder) before nudging}';
    protected $description = 'Nudge owners (old backlog + new signups) who registered but never listed a PG property';

    public function handle(WhatsAppService $whatsapp): int
    {
        $daysAgo = (int) $this->option('days');
        $cutoff = now()->subDays($daysAgo);

        // Covers BOTH:
        // - old backlog owners who signed up long ago and never got this reminder
        // - new owners, once they've had at least {--days} to list a property
        $owners = User::where('role', 'owner')
            ->whereDoesntHave('properties')
            ->where('created_at', '<=', $cutoff)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('pg_reminder_sent_at')
                  ->orWhere('pg_reminder_sent_at', '<=', $cutoff);
            })
            ->get();

        $sent = 0;
        $alreadyMessagedPhones = [];

        foreach ($owners as $owner) {
            if (!$owner->phone) {
                continue;
            }

            // Defensive dedupe — if two owner accounts share the same
            // phone (old duplicate signups from before dedup was added),
            // only message that number once per run, not once per account.
            $normalizedPhone = substr(preg_replace('/[^0-9]/', '', $owner->phone), -10);
            if (in_array($normalizedPhone, $alreadyMessagedPhones)) {
                $owner->update(['pg_reminder_sent_at' => now()]);
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $owner->phone,
                'pg_not_listed',
                [$owner->name]
            );

            if ($result['ok']) {
                $owner->update(['pg_reminder_sent_at' => now()]);
                $alreadyMessagedPhones[] = $normalizedPhone;
                $sent++;
            }
        }

        $this->info("Checked {$owners->count()} owners without a listed PG, sent {$sent} reminders.");
        return self::SUCCESS;
    }
}
