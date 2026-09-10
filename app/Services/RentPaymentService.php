<?php

namespace App\Services;

use App\Models\RentBill;
use App\Models\RentPayment;
use App\Models\RentRazorpayOrder;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use RuntimeException;

class RentPaymentService
{
    private Api $razorpay;

    public function __construct()
    {
        $key = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        if (empty($key) || empty($secret)) {
            throw new RuntimeException('Razorpay keys not configured. Check your .env file.');
        }

        $this->razorpay = new Api($key, $secret);
    }

    /**
     * Create a Razorpay order for a rent bill. Called when the tenant
     * opens the payment page. Reuses an existing unpaid order if one
     * was already created (avoids spamming Razorpay with duplicate
     * orders if the tenant refreshes the page).
     */
    public function createOrder(RentBill $bill): RentRazorpayOrder
    {
        if ($bill->due_amount <= 0) {
            throw new RuntimeException('This bill has no due amount.');
        }

        $order = $this->razorpay->order->create([
            'receipt' => 'rent_' . $bill->bill_number . '_' . time(),
            'amount' => (int) round($bill->due_amount * 100), // paise
            'currency' => 'INR',
            'notes' => [
                'rent_bill_id' => $bill->id,
                'tenant_id' => $bill->tenant_id,
                'bill_number' => $bill->bill_number,
            ],
        ]);

        return RentRazorpayOrder::create([
            'rent_bill_id' => $bill->id,
            'tenant_id' => $bill->tenant_id,
            'amount' => $bill->due_amount,
            'razorpay_order_id' => $order->id,
            'status' => 'created',
        ]);
    }

    /**
     * Verify payment signature & record it as a real RentPayment,
     * automatically recalculating the bill's status — no manual
     * "mark as paid" needed for online payments.
     */
    public function verifyAndComplete(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): RentPayment {
        $order = RentRazorpayOrder::where('razorpay_order_id', $razorpayOrderId)->firstOrFail();

        // Already processed? Don't double-record.
        if ($order->status === 'paid') {
            $existing = RentPayment::where('transaction_ref', $razorpayPaymentId)->first();
            if ($existing) {
                return $existing;
            }
        }

        try {
            $this->razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ]);
        } catch (\Exception $e) {
            $order->update([
                'status' => 'failed',
                'failure_reason' => 'Signature verification failed: ' . $e->getMessage(),
            ]);
            $this->alertAdminOnRepeatedFailures('Razorpay (rent)');
            throw new RuntimeException('Payment verification failed. Please contact support.');
        }

        $order->update([
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature,
            'status' => 'paid',
        ]);

        $bill = $order->bill;

        $payment = RentPayment::create([
            'rent_bill_id' => $bill->id,
            'tenant_id' => $bill->tenant_id,
            'owner_id' => $bill->owner_id,
            'receipt_number' => 'RCP-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            'amount' => $order->amount,
            'payment_method' => 'razorpay',
            'transaction_ref' => $razorpayPaymentId,
            'paid_at' => now(),
            'received_by_id' => null, // online — collected by platform, not a specific staff member
            'notes' => 'Paid online via Razorpay — collected by Pizi, to be settled to owner.',
        ]);

        $bill->refresh()->recalculate();

        // Notify tenant automatically — no owner action needed
        app(\App\Services\WhatsAppService::class)->sendTemplate(
            $bill->tenant->phone,
            'payment_confirmed',
            [$bill->tenant->name, number_format($order->amount, 0), $bill->month_label]
        );

        try {
            $bill->owner?->notify(new \App\Notifications\PaymentReceived($payment));
        } catch (\Exception $e) {
            \Log::warning('Payment notification failed: ' . $e->getMessage());
        }

        return $payment;
    }

    /**
     * If N payments fail within the last 2 hours on a gateway, ping admins —
     * throttled to once per 2-hour window so a bad run doesn't spam.
     */
    private function alertAdminOnRepeatedFailures(string $gateway, int $threshold = 5, int $windowHours = 2): void
    {
        try {
            $cacheKey = 'payment_failure_alert_sent_' . Str::slug($gateway);
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return; // already alerted this window
            }

            $failCount = RentRazorpayOrder::where('status', 'failed')
                ->where('created_at', '>=', now()->subHours($windowHours))
                ->count();

            if ($failCount < $threshold) {
                return;
            }

            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours($windowHours));

            $admins = \App\Models\User::where('role', 'admin')->whereNotNull('phone')->get();
            foreach ($admins as $admin) {
                app(WhatsAppService::class)->sendTemplate(
                    $admin->phone,
                    'payment_gateway_failure_alert',
                    [$windowHours, $failCount, $gateway]
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Payment failure alert check failed: ' . $e->getMessage());
        }
    }
}