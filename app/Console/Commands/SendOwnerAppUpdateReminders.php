<?php

namespace App\Console\Commands;

use App\Services\OwnerAppUpdateService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Daily. A WhatsApp message (with the Play Store link) to every owner whose Pizi Owner app is older than the
 * latest version the admin set. The list is rebuilt every run from the app versions the apps report, so an
 * owner who updates drops out on their own and the messages stop. One message a day per owner at most, and no
 * owner gets more than the cap in total. Does nothing until the admin switches it on.
 */
class SendOwnerAppUpdateReminders extends Command
{
    protected $signature = 'owner-app:send-update-reminders {--dry : Only say who would get a message}';
    protected $description = 'WhatsApp the owners whose app is out of date, once a day, until they update';

    public function handle(OwnerAppUpdateService $updates, WhatsAppService $whatsapp): int
    {
        if (!$updates->enabled()) {
            $this->info('Update reminders are switched off. Nothing to do.');

            return self::SUCCESS;
        }
        $latest = $updates->latestVersion();
        if ($latest === null) {
            $this->info('No latest version is set. Nothing to do.');

            return self::SUCCESS;
        }

        $cap = $updates->maxReminders();
        $sent = $skipped = $failed = 0;

        foreach ($updates->outdated($latest) as $owner) {
            if (!$owner->phone) {
                $skipped++;
                continue;
            }
            $already = DB::table('owner_app_update_reminders')->where('user_id', $owner->user_id);
            if ((clone $already)->whereDate('sent_at', today())->exists() || (clone $already)->count() >= $cap) {
                $skipped++;
                continue;
            }
            if ($this->option('dry')) {
                $this->line("would message {$owner->name} ({$owner->phone}), app " . ($owner->version ?: 'unknown'));
                $sent++;
                continue;
            }

            $result = $whatsapp->sendTemplate($owner->phone, 'owner_app_update', [$owner->name ?: 'there']);
            if (!empty($result['ok'])) {
                DB::table('owner_app_update_reminders')->insert([
                    'user_id' => $owner->user_id, 'version_seen' => $owner->version, 'latest_version' => $latest, 'sent_at' => now(),
                ]);
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->info(($this->option('dry') ? 'Would send ' : 'Sent ') . "{$sent}, skipped {$skipped} (already today, over the cap, or no phone), failed {$failed}.");

        return self::SUCCESS;
    }
}
