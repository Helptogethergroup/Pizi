<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendKycPendingReminders extends Command
{
    protected $signature = 'kyc:send-pending-reminders {--days=2 : Days since tenant creation before nudging}';
    protected $description = 'Send WhatsApp reminders to tenants whose KYC is still pending/submitted after N days — owner never needs to chase this manually';

    public function handle(WhatsAppService $whatsapp): int
    {
        $daysAgo = (int) $this->option('days');
        $cutoff = now()->subDays($daysAgo);

        $tenants = Tenant::whereIn('kyc_status', ['pending', 'submitted'])
            ->where('status', 'active')
            ->where('created_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->whereNull('kyc_reminder_sent_at')
                  ->orWhereDate('kyc_reminder_sent_at', '!=', now()->toDateString());
            })
            ->get();

        $sent = 0;

        foreach ($tenants as $tenant) {
            if (!$tenant->phone) {
                continue;
            }

            $publicBase = rtrim(env('APP_PUBLIC_URL', config('app.url')), '/');

            $result = $whatsapp->sendTemplate(
                $tenant->phone,
                'kyc_reminder',
                [
                    $tenant->name,
                    $publicBase . route('tenant.kyc.upload', absolute: false),
                ]
            );

            if ($result['ok']) {
                $tenant->update(['kyc_reminder_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Checked {$tenants->count()} tenants with pending KYC, sent {$sent} reminders.");
        return self::SUCCESS;
    }
}