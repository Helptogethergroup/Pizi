<?php

namespace App\Services;

use App\Models\CreditPackage;
use App\Models\Payment;
use App\Models\User;
use Razorpay\Api\Api;
use RuntimeException;

class PaymentService
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
     * Create a Razorpay order. Called when user clicks "Buy" on a package.
     */
    public function createOrder(User $user, CreditPackage $package): Payment
    {
        $order = $this->razorpay->order->create([
            'receipt' => 'pgfind_' . time() . '_' . $user->id,
            'amount' => $package->price_inr * 100, // Razorpay uses paise
            'currency' => 'INR',
            'notes' => [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'credits' => $package->total_credits,
            ],
        ]);

        return Payment::create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'amount_inr' => $package->price_inr,
            'credits_to_add' => $package->total_credits,
            'razorpay_order_id' => $order->id,
            'status' => 'created',
        ]);
    }

    /**
     * Verify payment signature & credit user's wallet.
     * Called when Razorpay redirects back after successful payment.
     */
    public function verifyAndComplete(
    string $razorpayOrderId,
    string $razorpayPaymentId,
    string $razorpaySignature,
    WalletService $walletService
): Payment {
    try {
        \Log::info('Payment verification started', [
            'order_id' => $razorpayOrderId,
            'payment_id' => $razorpayPaymentId,
        ]);

        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->firstOrFail();
        
        // Already processed? Don't double-credit.
        if ($payment->status === 'paid') {
            \Log::info('Payment already processed', ['payment_id' => $payment->id]);
            return $payment;
        }

        // Verify signature using Razorpay SDK
        try {
            $this->razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ]);
            \Log::info('Signature verified successfully', ['payment_id' => $payment->id]);
        } catch (\Exception $e) {
            \Log::error('Signature verification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'Signature verification failed: ' . $e->getMessage(),
            ]);
            $this->alertAdminOnRepeatedFailures('Razorpay (credits)');
            throw new RuntimeException('Payment verification failed. Please contact support.');
        }

        // Mark as paid
        $payment->update([
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature,
            'status' => 'paid',
        ]);

        \Log::info('Payment marked as paid', ['payment_id' => $payment->id]);

        // Credit the wallet
        try {
            $walletService->credit(
                $payment->user,
                $payment->credits_to_add,
                'purchase',
                $razorpayPaymentId,
                "Purchased {$payment->package?->name}",
                null
            );

            \Log::info('Credits added successfully', [
                'user_id' => $payment->user_id,
                'credits' => $payment->credits_to_add,
                'new_balance' => $payment->user->wallet->balance ?? 0,
            ]);

        } catch (\Exception $e) {
            \Log::error('Credit allocation failed', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RuntimeException('Credits could not be allocated: ' . $e->getMessage());
        }

        return $payment;

    } catch (\Exception $e) {
        \Log::error('Payment verification failed', [
            'order_id' => $razorpayOrderId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

    /**
     * If N payments fail within the last 2 hours on a gateway, ping admins —
     * throttled to once per 2-hour window so a bad run doesn't spam.
     */
    private function alertAdminOnRepeatedFailures(string $gateway, int $threshold = 5, int $windowHours = 2): void
    {
        try {
            $cacheKey = 'payment_failure_alert_sent_' . \Illuminate\Support\Str::slug($gateway);
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return; // already alerted this window
            }

            $failCount = Payment::where('status', 'failed')
                ->where('created_at', '>=', now()->subHours($windowHours))
                ->count();

            if ($failCount < $threshold) {
                return;
            }

            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours($windowHours));

            $admins = User::where('role', 'admin')->whereNotNull('phone')->get();
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