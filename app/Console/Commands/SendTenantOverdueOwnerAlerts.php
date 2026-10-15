<?php

namespace App\Console\Commands;

use App\Models\RentBill;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendTenantOverdueOwnerAlerts extends Command
{
    protected $signature = 'rent:send-overdue-owner-alerts {--days=5 : Minimum days overdue before alerting the owner}';
    protected $description = "Alert owners when a tenant's rent is overdue by N+ days";

    public function handle(WhatsAppService $whatsapp): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days)->toDateString();

        $bills = RentBill::where('status', '!=', 'paid')
            ->whereDate('due_date', '<=', $cutoffDate)
            ->with(['tenant', 'owner'])
            ->get();

        $sent = 0;

        // The owner app gets one summary per owner per day, whether or not
        // WhatsApp is configured (it is a different, free channel).
        $this->notifyOwnersInApp($bills);

        foreach ($bills as $bill) {
            if (!$bill->owner || !$bill->owner->phone || !$bill->tenant) {
                continue;
            }

            // Once every 3 days per bill — an owner already following up
            // doesn't need a fresh ping daily.
            $cacheKey = "tenant_overdue_owner_alert_sent_{$bill->id}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $bill->owner->phone,
                'tenant_rent_overdue_owner_alert',
                [
                    $bill->owner->name,
                    $bill->tenant->name,
                    $bill->tenant->room_number ?? '-',
                    number_format($bill->due_amount, 0),
                    now()->diffInDays($bill->due_date),
                ]
            );

            if ($result['ok']) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(3));
                $sent++;
            }
        }

        $this->info("Checked {$bills->count()} overdue bills, sent {$sent} owner alerts.");
        return self::SUCCESS;
    }

    private function notifyOwnersInApp($bills): void
    {
        foreach ($bills->filter(fn ($b) => $b->owner)->groupBy('owner_id') as $ownerBills) {
            $owner = $ownerBills->first()->owner;
            $key = 'rent_overdue_app_digest_' . $owner->id . '_' . now()->toDateString();
            if (\Illuminate\Support\Facades\Cache::has($key)) {
                continue;
            }

            try {
                $owner->notify(new \App\Notifications\RentOverdueDigest(
                    $ownerBills->count(),
                    (float) $ownerBills->sum('due_amount'),
                    $ownerBills->map(fn ($b) => $b->tenant?->name)->filter()->unique()->values()->all()
                ));
                \Illuminate\Support\Facades\Cache::put($key, true, now()->addDay());
            } catch (\Throwable $e) {
                \Log::warning('Rent overdue app digest failed: ' . $e->getMessage());
            }
        }
    }
}
