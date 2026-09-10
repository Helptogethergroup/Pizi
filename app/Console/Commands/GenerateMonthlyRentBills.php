<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\RentBill;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateMonthlyRentBills extends Command
{
    protected $signature = 'rent:generate-monthly {--due-day=5 : Day of the month bills are due} {--month= : Override month (Y-m format), defaults to current month}';
    protected $description = 'Auto-generate rent bills for all active tenants platform-wide (runs monthly, no owner action needed)';

    public function handle(WhatsAppService $whatsapp): int
    {
        $month = $this->option('month') ?: now()->format('Y-m');
        $dueDay = (int) $this->option('due-day');
        $dueDate = \Carbon\Carbon::parse($month . '-01')->day(min($dueDay, 28))->format('Y-m-d');

        $tenants = Tenant::where('status', 'active')
            ->where('monthly_rent', '>', 0)
            ->whereNotNull('owner_id')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            $exists = RentBill::where('tenant_id', $tenant->id)
                ->where('month', $month)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $bill = RentBill::create([
                'tenant_id' => $tenant->id,
                'property_id' => $tenant->property_id,
                'owner_id' => $tenant->owner_id,
                'bill_number' => 'PIZI-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'month' => $month,
                'rent_amount' => $tenant->monthly_rent,
                'total_amount' => $tenant->monthly_rent,
                'due_amount' => $tenant->monthly_rent,
                'paid_amount' => 0,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);

            $whatsapp->sendTemplate(
                $tenant->phone,
                'bill_generated',
                // Numbered params matching rent_bill_generated_v3: {{1}} {{2}} {{3}} {{4}} {{5}}=pay link
                [
                    $tenant->name,
                    \Carbon\Carbon::parse($month . '-01')->format('M Y'),
                    number_format($tenant->monthly_rent, 0),
                    \Carbon\Carbon::parse($dueDate)->format('d M Y'),
                    $bill->pay_url,
                ]
            );

            $created++;
        }

        $this->info("Month: {$month} — Created {$created} bills, skipped {$skipped} (already existed).");
        return self::SUCCESS;
    }
}