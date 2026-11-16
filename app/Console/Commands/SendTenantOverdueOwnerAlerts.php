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

        // One WhatsApp message per tenant (their bills added together, oldest due date), not one per bill, and
        // at most 3 a day per owner (the biggest dues first). The rest of the list is in the owner's app digest.
        $perOwnerLimit = 3;
        $sentToOwner = [];

        $byTenant = $bills
            ->filter(fn ($b) => $b->owner && $b->owner->phone && $b->tenant)
            ->groupBy('tenant_id')
            ->map(fn ($tenantBills) => [
                'owner' => $tenantBills->first()->owner,
                'tenant' => $tenantBills->first()->tenant,
                'amount' => (float) $tenantBills->sum('due_amount'),
                'since' => $tenantBills->min('due_date'),
            ])
            ->sortByDesc('amount');

        foreach ($byTenant as $tenantId => $row) {
            $owner = $row['owner'];
            if (($sentToOwner[$owner->id] ?? 0) >= $perOwnerLimit) {
                continue;
            }

            // Once every 3 days per tenant — an owner already following up
            // doesn't need a fresh ping daily.
            $cacheKey = "tenant_overdue_owner_alert_sent_tenant_{$tenantId}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                continue;
            }

            $result = $whatsapp->sendTemplate(
                $owner->phone,
                'tenant_rent_overdue_owner_alert',
                [
                    $owner->name,
                    $row['tenant']->name,
                    $row['tenant']->room_number ?? '-',
                    number_format($row['amount'], 0),
                    // whole days since the oldest due date (Carbon 3 gives a signed fraction, which is what showed up as "-41.43... days")
                    (int) \Carbon\Carbon::parse($row['since'])->startOfDay()->diffInDays(now()->startOfDay()),
                ]
            );

            if ($result['ok']) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(3));
                $sentToOwner[$owner->id] = ($sentToOwner[$owner->id] ?? 0) + 1;
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
