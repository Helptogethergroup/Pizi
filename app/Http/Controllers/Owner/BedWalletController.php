<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BedPayment;
use App\Models\BedTopupPackage;
use App\Services\BedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

/** The owner's Bed Wallet page and top-up (Razorpay). */
class BedWalletController extends Controller
{
    public function __construct(private BedBillingService $billing)
    {
    }

    public function index()
    {
        $owner = auth()->user();
        $summary = $this->billing->summary($owner->id);

        return view('owner.bed-wallet', [
            'summary' => $summary,
            'packages' => BedTopupPackage::where('is_active', true)->orderBy('display_order')->orderBy('amount_inr')->get(),
            'transactions' => $summary['wallet']->transactions()->paginate(20),
            'razorpayKey' => config('services.razorpay.key'),
            'enabled' => $this->billing->enabled(),
        ]);
    }

    /** Step 1: make the Razorpay order for a package and remember it. */
    public function createOrder(Request $request)
    {
        $data = $request->validate(['package_id' => 'required|integer']);
        $package = BedTopupPackage::where('is_active', true)->find($data['package_id']);
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'This package is not available.'], 404);
        }

        try {
            $api = $this->razorpay();
            $order = $api->order->create([
                'amount' => (int) $package->amount_inr * 100,
                'currency' => 'INR',
                'receipt' => 'bed_' . auth()->id() . '_' . time(),
                'notes' => ['purpose' => 'bed_wallet', 'user_id' => auth()->id(), 'package_id' => $package->id],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Bed wallet order failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not start the payment. Please try again.'], 500);
        }

        BedPayment::create([
            'user_id' => auth()->id(),
            'bed_topup_package_id' => $package->id,
            'amount_inr' => $package->amount_inr,
            'wallet_credit' => $package->wallet_credit,
            'razorpay_order_id' => $order['id'],
            'status' => 'created',
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'name' => $package->name,
        ]);
    }

    /** Step 2: the payment is done. Check Razorpay's signature, then credit the wallet exactly once. */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        // the order must be this owner's own
        $payment = BedPayment::where('razorpay_order_id', $data['razorpay_order_id'])->where('user_id', auth()->id())->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }
        if ($payment->status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Already added to your Bed Wallet.']);
        }

        try {
            $this->razorpay()->utility->verifyPaymentSignature($data);
        } catch (\Throwable $e) {
            $payment->update(['status' => 'failed', 'failure_reason' => 'Signature check failed']);
            \Log::warning('Bed wallet signature failed for order ' . $data['razorpay_order_id']);

            return response()->json(['success' => false, 'message' => 'The payment could not be verified. If money was deducted, it will be added to your wallet after we check it, or refunded.'], 422);
        }

        // credit once, even if this is called twice at the same moment (the key is unique)
        DB::transaction(function () use ($payment, $data) {
            $locked = BedPayment::lockForUpdate()->find($payment->id);
            if ($locked->status === 'paid') {
                return;
            }
            $locked->update(['status' => 'paid', 'razorpay_payment_id' => $data['razorpay_payment_id']]);
            $this->billing->credit(
                $locked->user_id, (float) $locked->wallet_credit, 'topup', $data['razorpay_payment_id'],
                'Top-up ₹' . number_format($locked->amount_inr) . ($locked->wallet_credit > $locked->amount_inr ? ' + ₹' . number_format($locked->wallet_credit - $locked->amount_inr) . ' bonus' : ''),
                null, 'topup:' . $locked->razorpay_order_id
            );
        });

        $summary = $this->billing->summary(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Money added to your Bed Wallet.',
            'balance' => (float) $summary['wallet']->fresh()->balance,
            'plan_active' => $summary['sub'] ? $summary['sub']->fresh()->status === 'active' : null,
        ]);
    }

    private function razorpay(): Api
    {
        return new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }
}
