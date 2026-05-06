<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function packages()
    {
        $packages = CreditPackage::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('price_inr')
            ->get();

        $wallet = auth()->user()->wallet ?? auth()->user()->wallet()->create(['balance' => 0]);

        return view('owner.packages', compact('packages', 'wallet'));
    }

    public function checkout(CreditPackage $package, PaymentService $paymentService)
    {
        try {
            $payment = $paymentService->createOrder(auth()->user(), $package);
        } catch (RuntimeException $e) {
            return back()->withErrors(['razorpay' => $e->getMessage()]);
        }

        return view('owner.checkout', [
            'payment' => $payment,
            'package' => $package,
            'razorpayKey' => config('services.razorpay.key'),
            'user' => auth()->user(),
        ]);
    }

    public function callback(Request $request, PaymentService $paymentService, WalletService $walletService)
    {
        $data = $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        try {
            $payment = $paymentService->verifyAndComplete(
                $data['razorpay_order_id'],
                $data['razorpay_payment_id'],
                $data['razorpay_signature'],
                $walletService
            );

            return redirect()->route('owner.wallet')
                ->with('success', "🎉 Payment successful! {$payment->credits_to_add} credits added to your wallet.");
        } catch (RuntimeException $e) {
            return redirect()->route('owner.packages')
                ->withErrors(['payment' => $e->getMessage()]);
        }
    }

    public function failed(Request $request)
    {
        return redirect()->route('owner.packages')
            ->withErrors(['payment' => $request->error_description ?? 'Payment was cancelled or failed.']);
    }
}