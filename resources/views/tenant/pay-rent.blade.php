@extends('layouts.tenant')
@section('title', 'Pay Rent')
@section('content')

<div class="max-w-md mx-auto">
    <a href="{{ route('tenant.dashboard') }}" class="text-coral-600 font-bold text-sm">← Back to dashboard</a>

    <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden mt-4">
        <div class="bg-ink-950 text-cream p-6 text-center">
            <div class="text-sm opacity-70">Rent Bill</div>
            <div class="font-display font-black text-3xl mt-1">₹{{ number_format($bill->due_amount, 0) }}</div>
            <div class="text-xs opacity-60 mt-1">Due</div>
        </div>

        <div class="p-6 space-y-3 border-b border-ink-900/10">
            <div class="flex justify-between text-sm">
                <span class="text-ink-900/60">Month</span>
                <span class="font-bold">{{ $bill->month_label }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-ink-900/60">Due Date</span>
                <span class="font-bold">{{ $bill->due_date->format('d M Y') }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-ink-900/60">Bill No.</span>
                <span class="font-mono text-xs">{{ $bill->bill_number }}</span>
            </div>
        </div>

        @if($bill->due_amount <= 0)
            <div class="p-8 text-center">
                <div class="text-4xl mb-2"><i class="fa-solid fa-circle-check fa-fw"></i></div>
                <p class="font-bold text-emerald-700">This bill is fully paid.</p>
                <p class="text-sm text-ink-900/60 mt-1">Thank you!</p>
            </div>
        @else
            {{-- SUCCESS STATE --}}
            <div id="paySuccess" class="hidden p-8 text-center">
                <div class="text-5xl mb-3"><i class="fa-solid fa-champagne-glasses fa-fw"></i></div>
                <p class="font-bold text-emerald-700 text-lg">Payment Successful!</p>
                <p class="text-sm text-ink-900/60 mt-1">Your rent has been recorded.</p>
                <a href="{{ route('tenant.rent.history') }}" class="inline-block mt-4 px-5 py-2.5 bg-ink-950 text-cream rounded-lg font-bold text-sm">View Rent History</a>
            </div>

            {{-- PAYMENT BUTTON --}}
            <div id="paySection" class="p-6 text-center">
                <p class="text-sm text-ink-900/60 mb-4">Pay securely via Razorpay (UPI, Card, NetBanking)</p>
                <button id="payBtn"
                    class="w-full px-6 py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-base transition">
                    <i class="fa-solid fa-credit-card fa-fw"></i> Pay ₹{{ number_format($bill->due_amount, 0) }} Now
                </button>
                <p class="text-xs text-ink-900/40 mt-2">Powered by Razorpay · 100% Secure</p>
            </div>
        @endif
    </div>
</div>

@if($bill->due_amount > 0)
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('payBtn').addEventListener('click', async function () {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Creating order...';

    const orderRes = await fetch('{{ route('tenant.rent.razorpay.order', $bill->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });

    const orderData = await orderRes.json();

    if (!orderData.order_id) {
        alert('Failed to create order. Please try again.');
        btn.disabled = false;
        btn.textContent = '💳 Pay ₹{{ number_format($bill->due_amount, 0) }} Now';
        return;
    }

    const options = {
        key: '{{ env("RAZORPAY_KEY_ID") }}',
        amount: orderData.amount,
        currency: orderData.currency,
        name: 'Pizi',
        description: 'Rent - ' + orderData.bill_no,
        order_id: orderData.order_id,
        theme: { color: '#FF6B5B' },
        handler: async function (response) {
            const verifyRes = await fetch('{{ route('tenant.rent.razorpay.verify', $bill->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    razorpay_order_id:   response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature:  response.razorpay_signature,
                })
            });

            const result = await verifyRes.json();

            if (result.success) {
                document.getElementById('paySection').classList.add('hidden');
                document.getElementById('paySuccess').classList.remove('hidden');
            } else {
                alert('Payment verification failed. Contact support with ID: ' + response.razorpay_payment_id);
            }
        },
        modal: {
            ondismiss: function () {
                btn.disabled = false;
                btn.textContent = '💳 Pay ₹{{ number_format($bill->due_amount, 0) }} Now';
            }
        }
    };

    const rzp = new Razorpay(options);
    rzp.open();
});
</script>
@endif

@endsection