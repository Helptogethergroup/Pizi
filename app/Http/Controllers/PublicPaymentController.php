<?php

namespace App\Http\Controllers;

use App\Models\RentBill;
use App\Models\RentRazorpayOrder;
use App\Services\RentPaymentService;
use Illuminate\Http\Request;
use RuntimeException;

class PublicPaymentController extends Controller
{
    /**
     * Public rent-payment page — no login required, so the link works
     * when tenant opens it from WhatsApp on any device. Payment goes
     * through the platform's Razorpay account (not the owner's).
     */
    public function show(string $billNumber, RentPaymentService $service)
    {
        $bill = RentBill::where('bill_number', $billNumber)
            ->with('tenant', 'property')
            ->firstOrFail();

        $order = null;
        $errorMsg = null;

        if ($bill->due_amount > 0) {
            // Reuse an existing unpaid order if the tenant refreshes the page
            $order = RentRazorpayOrder::where('rent_bill_id', $bill->id)
                ->where('status', 'created')
                ->latest()
                ->first();

            if (!$order) {
                try {
                    $order = $service->createOrder($bill);
                } catch (RuntimeException $e) {
                    $errorMsg = $e->getMessage();
                }
            }
        }

        return view('public.pay', [
            'bill' => $bill,
            'order' => $order,
            'errorMsg' => $errorMsg,
            'razorpayKey' => config('services.razorpay.key'),
        ]);
    }

    /**
     * Razorpay redirects/posts back here after checkout completes.
     */
    public function callback(Request $request, string $billNumber, RentPaymentService $service)
    {
        $data = $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        try {
            $service->verifyAndComplete(
                $data['razorpay_order_id'],
                $data['razorpay_payment_id'],
                $data['razorpay_signature']
            );

            return redirect()->route('public.pay', $billNumber)
                ->with('success', '✓ Payment successful! Thank you.');
        } catch (\Exception $e) {
            return redirect()->route('public.pay', $billNumber)
                ->withErrors(['payment' => 'Payment verification failed: ' . $e->getMessage()]);
        }
    }

    public function failed(string $billNumber, Request $request)
    {
        return redirect()->route('public.pay', $billNumber)
            ->withErrors(['payment' => $request->error_description ?? 'Payment was cancelled or failed.']);
    }
}