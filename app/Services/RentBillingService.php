<?php

namespace App\Services;

use App\Models\RentBill;
use App\Models\RentPayment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Rent bills the way the website does them (Owner\RentController), for the app's API:
 * the same duplicate rule, bill and receipt numbers, WhatsApp messages and recalculation.
 */
class RentBillingService
{
    public const PAYMENT_METHODS = ['cash', 'upi', 'bank_transfer', 'razorpay', 'phonepe', 'paytm', 'cheque', 'other'];

    /** @throws RuntimeException a message that is safe to show */
    public function createBill(Tenant $tenant, array $data, int $ownerId): RentBill
    {
        // soft-deleted bills block a new one too (they can be restored from the Bill Archive)
        if (RentBill::withTrashed()->where('tenant_id', $tenant->id)->where('month', $data['month'])->exists()) {
            throw new RuntimeException('A bill for this month already exists. Check the Bill Archive if it was deleted.');
        }

        $n = fn ($key) => (float) ($data[$key] ?? 0);
        $total = $n('rent_amount') + $n('electricity') + $n('water') + $n('maintenance')
            + $n('food_charges') + $n('other_charges') + $n('late_fee') - $n('discount');

        $bill = RentBill::create([
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'owner_id' => $ownerId,
            'bill_number' => $this->billNumber(),
            'month' => $data['month'],
            'rent_amount' => $n('rent_amount'),
            'electricity' => $n('electricity'),
            'water' => $n('water'),
            'maintenance' => $n('maintenance'),
            'food_charges' => $n('food_charges'),
            'other_charges' => $n('other_charges'),
            'other_charges_label' => $data['other_charges_label'] ?? null,
            'late_fee' => $n('late_fee'),
            'discount' => $n('discount'),
            'total_amount' => $total,
            'paid_amount' => 0,
            'due_amount' => $total,
            'due_date' => $data['due_date'],
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->tell($tenant->phone, 'bill_generated', [
            $tenant->name,
            $bill->month_label,
            number_format($bill->total_amount, 0),
            $bill->due_date->format('d M Y'),
            $bill->pay_url,
        ]);

        return $bill;
    }

    /** @return array{created:int, skipped:int} */
    public function generateAll($tenants, string $month, string $dueDate, int $ownerId): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            if (RentBill::withTrashed()->where('tenant_id', $tenant->id)->where('month', $month)->exists()) {
                $skipped++;
                continue;
            }

            $bill = RentBill::create([
                'tenant_id' => $tenant->id,
                'property_id' => $tenant->property_id,
                'owner_id' => $ownerId,
                'bill_number' => $this->billNumber(),
                'month' => $month,
                'rent_amount' => $tenant->monthly_rent,
                'total_amount' => $tenant->monthly_rent,
                'due_amount' => $tenant->monthly_rent,
                'paid_amount' => 0,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);

            $this->tell($tenant->phone, 'bill_generated', [
                $tenant->name,
                Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                number_format($tenant->monthly_rent, 0),
                Carbon::parse($dueDate)->format('d M Y'),
                $bill->pay_url,
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** @throws RuntimeException */
    public function recordPayment(RentBill $bill, array $data, int $receivedById): RentPayment
    {
        $amount = (float) $data['amount'];
        if ($amount > (float) $bill->due_amount + 0.009) {
            throw new RuntimeException('The amount is more than what is due on this bill.');
        }

        $payment = RentPayment::create([
            'rent_bill_id' => $bill->id,
            'tenant_id' => $bill->tenant_id,
            'owner_id' => $bill->owner_id,
            'received_by_id' => $receivedById,
            'receipt_number' => 'RCP-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            'amount' => $amount,
            'payment_method' => in_array($data['payment_method'] ?? 'cash', self::PAYMENT_METHODS, true) ? ($data['payment_method'] ?? 'cash') : 'other',
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
        ]);

        $bill->refresh()->recalculate();

        $tenant = $bill->tenant;
        if ($tenant) {
            $this->tell($tenant->phone, 'payment_confirmed', [$tenant->name, number_format($amount, 0), $bill->month_label]);
        }

        return $payment;
    }

    public function deletePayment(RentPayment $payment): void
    {
        $bill = $payment->bill;
        $payment->delete();
        $bill?->refresh()->recalculate();
    }

    /**
     * Tell the tenant a bill is due. When the WhatsApp API is not set up, hand back a
     * ready-made wa.me link so the owner can send it from his own phone.
     *
     * @return array{ok:bool, message?:string, wa_link?:string}
     */
    public function remind(RentBill $bill): array
    {
        if ($bill->status === 'paid') {
            return ['ok' => false, 'message' => 'This bill is already paid.'];
        }

        $tenant = $bill->tenant;
        $result = app(WhatsAppService::class)->sendTemplate($tenant->phone, 'bill_generated', [
            $tenant->name,
            $bill->month_label,
            number_format($bill->due_amount, 0),
            $bill->due_date->format('d M Y'),
            $bill->pay_url,
        ]);

        if (!empty($result['ok'])) {
            $bill->forceFill(['last_reminder_sent_at' => now()])->save();
            return ['ok' => true, 'message' => 'Reminder sent on WhatsApp.'];
        }

        $phone = preg_replace('/[^0-9]/', '', (string) $tenant->phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        $text = "Hi {$tenant->name},\n\n"
            . "Your rent bill for {$bill->month_label} is pending.\n"
            . 'Amount: ₹' . number_format($bill->due_amount, 0) . "\n"
            . 'Due date: ' . $bill->due_date->format('d M Y') . "\n"
            . "Bill no: {$bill->bill_number}\n\n"
            . "Pay here: {$bill->pay_url}\n\n- Pizi";

        return [
            'ok' => false,
            'message' => 'WhatsApp is not connected yet. Send the message from your own WhatsApp.',
            'wa_link' => "https://wa.me/{$phone}?text=" . rawurlencode($text),
        ];
    }

    private function billNumber(): string
    {
        return 'PIZI-' . date('Ymd') . '-' . strtoupper(Str::random(4));
    }

    /** A failed WhatsApp must never undo the bill or the payment. */
    private function tell(?string $phone, string $template, array $params): void
    {
        if (empty($phone)) {
            return;
        }
        try {
            app(WhatsAppService::class)->sendTemplate($phone, $template, $params);
        } catch (\Throwable $e) {
            \Log::warning('Rent WhatsApp failed: ' . $e->getMessage());
        }
    }
}
