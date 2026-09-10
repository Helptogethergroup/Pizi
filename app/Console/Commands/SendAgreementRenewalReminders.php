<?php

namespace App\Console\Commands;

use App\Models\RentAgreement;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendAgreementRenewalReminders extends Command
{
    protected $signature = 'agreements:send-renewal-reminders {--days=15 : Days before expiry to remind}';
    protected $description = 'Remind tenants whose rent agreement is expiring soon';

    public function handle(WhatsAppService $whatsapp): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->toDateString();

        $agreements = RentAgreement::whereIn('status', ['active', 'signed_owner', 'signed_tenant'])
            ->whereDate('end_date', $targetDate)
            ->whereNull('renewal_reminded_at')
            ->with('tenant')
            ->get();

        $sent = 0;

        foreach ($agreements as $agreement) {
            if (!$agreement->tenant || !$agreement->tenant->phone) {
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $agreement->tenant->phone,
                'agreement_renewal_reminder',
                [$agreement->tenant->name, $agreement->end_date->format('d M Y')]
            );

            if ($result['ok']) {
                $agreement->update(['renewal_reminded_at' => now()]);
                $sent++;
            }
        }

        $this->info("Checked {$agreements->count()} agreements, sent {$sent} renewal reminders.");
        return self::SUCCESS;
    }
}
