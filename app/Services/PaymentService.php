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
        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->firstOrFail();

        // Already processed? Don't double-credit.
        if ($payment->status === 'paid') {
            return $payment;
        }

        // Verify signature using Razorpay SDK
        try {
            $this->razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ]);
        } catch (\Exception $e) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'Signature verification failed: ' . $e->getMessage(),
            ]);
            throw new RuntimeException('Payment verification failed. Please contact support.');
        }

        // Mark as paid
        $payment->update([
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature,
            'status' => 'paid',
        ]);

        // Credit the wallet
        $walletService->credit(
            $payment->user,
            $payment->credits_to_add,
            'purchase',
            $razorpayPaymentId,
            "Purchased {$payment->package?->name}",
            null
        );

        return $payment;
    }
}