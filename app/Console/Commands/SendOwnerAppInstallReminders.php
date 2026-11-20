<?php

namespace App\Console\Commands;

use App\Services\OwnerAppUpdateService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Daily. A WhatsApp message (with the Play Store link) to every owner who has never signed in to the Pizi Owner
 * app. The list is rebuilt every run, so owners who join later are included and an owner who installs and signs in
 * drops out on their own. One message a day per owner at most, and no owner gets more than the cap in total.
 * Does nothing until the admin switches it on. Messages are logged in owner_app_update_reminders with
 * latest_version = 'install'.
 */
class SendOwnerAppInstallReminders extends Command
{
    protected $signature = 'owner-app:send-install-reminders {--dry : Only say who would get a message}';
    protected $description = 'WhatsApp the owners who never installed the app, once a day, until they sign in to it';

    public function handle(OwnerAppUpdateService $service, WhatsAppService $whatsapp): int
    {
        if (!$service->installEnabled()) {
            $this->info('Download reminders are switched off. Nothing to do.');

            return self::SUCCESS;
        }

        $cap = $service->installMax();
        $sent = $skipped = $failed = 0;

        foreach ($service->installTargets() as $owner) {
            $already = DB::table('owner_app_update_reminders')
                ->where('user_id', $owner->user_id)->where('latest_version', OwnerAppUpdateService::INSTALL_KIND);
            if ((clone $already)->whereDate('sent_at', today())->exists() || (clone $already)->count() >= $cap) {
                $skipped++;
                continue;
            }
            if ($this->option('dry')) {
                $this->line("would message {$owner->name} ({$owner->phone})");
                $sent++;
                continue;
            }

            $result = $whatsapp->sendTemplate($owner->phone, 'owner_app_install', [$owner->name ?: 'there']);
            if (!empty($result['ok'])) {
                DB::table('owner_app_update_reminders')->insert([
                    'user_id' => $owner->user_id, 'version_seen' => null,
                    'latest_version' => OwnerAppUpdateService::INSTALL_KIND, 'sent_at' => now(),
                ]);
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->info(($this->option('dry') ? 'Would send ' : 'Sent ') . "{$sent}, skipped {$skipped} (already today or over the cap), failed {$failed}.");

        return self::SUCCESS;
    }
}
