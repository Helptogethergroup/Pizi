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

        $this->notifyOwners();

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

    /** Owners hear 30 and 7 days before an agreement ends, once each. */
    private function notifyOwners(): void
    {
        foreach ([30, 7] as $daysLeft) {
            $agreements = RentAgreement::whereIn('status', ['active', 'signed_owner', 'signed_tenant'])
                ->whereDate('end_date', now()->addDays($daysLeft)->toDateString())
                ->with(['tenant', 'owner'])
                ->get();

            foreach ($agreements as $agreement) {
                if (!$agreement->owner) {
                    continue;
                }
                $key = "agreement_owner_alert_{$agreement->id}_{$daysLeft}";
                if (\Illuminate\Support\Facades\Cache::has($key)) {
                    continue;
                }
                try {
                    $agreement->owner->notify(new \App\Notifications\AgreementExpiring(
                        $agreement->tenant->name ?? 'Your tenant',
                        $agreement->end_date->format('d M Y'),
                        $daysLeft,
                        $agreement->id
                    ));
                    \Illuminate\Support\Facades\Cache::put($key, true, now()->addDays(40));
                } catch (\Throwable $e) {
                    \Log::warning('Agreement owner alert failed: ' . $e->getMessage());
                }
            }
        }
    }
}
