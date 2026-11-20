<?php

namespace App\Console\Commands;

use App\Services\TenantAppInviteService;
use Illuminate\Console\Command;

/**
 * Daily batch: the "download the Pizi tenant app" WhatsApp for tenants who were added before the automatic message
 * existed. Each run messages up to the batch size, one per phone number, and the next run carries on, until
 * everybody has had it. Does nothing until the admin switches both the new-tenant message and this batch on.
 */
class SendTenantAppInvitesToExisting extends Command
{
    protected $signature = 'tenant-app:invite-existing {--dry : Only say how many would get a message} {--limit= : Messages in this run (default: the batch size set by the admin)}';
    protected $description = 'WhatsApp the tenant app link to tenants added earlier, a batch a day';

    public function handle(TenantAppInviteService $invites): int
    {
        if (!TenantAppInviteService::enabled() || !TenantAppInviteService::backfillEnabled()) {
            $this->info('Messages to existing tenants are switched off. Nothing to do.');

            return self::SUCCESS;
        }

        $pending = $invites->pendingExisting();
        $limit = $this->option('limit') ? max(1, (int) $this->option('limit')) : TenantAppInviteService::batchSize();
        $this->info("{$pending->count()} tenants still to be messaged; this run: up to {$limit}.");

        $sent = $failed = $skipped = 0;
        foreach ($pending->take($limit) as $t) {
            if ($this->option('dry')) {
                $sent++;
                continue;
            }
            match ($invites->send($t->id)) {
                'sent' => $sent++,
                'failed' => $failed++,
                default => $skipped++,
            };
        }

        $this->info(($this->option('dry') ? 'Would send ' : 'Sent ') . "{$sent}, failed {$failed}, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
