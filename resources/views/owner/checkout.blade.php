@extends('layouts.dashboard')
@section('title', 'Checkout')
@section('content')

<div class="max-w-md mx-auto">
    <a href="{{ route('owner.packages') }}" class="text-sm text-ink-900/60">← Back to packages</a>
    
    <div class="bg-white p-8 rounded-3xl border border-ink-900/10 mt-4">
        <h1 class="font-display font-black text-3xl">Complete payment</h1>
        <p class="text-ink-900/60 mt-2">You're purchasing the following package:</p>
        
        <div class="mt-6 p-6 rounded-2xl bg-cream border border-ink-900/10">
            <div class="font-display font-bold text-xl">{{ $package->name }}</div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <div class="text-xs text-ink-900/60 uppercase">Credits</div>
                    <div class="font-bold text-lg">{{ number_format($package->total_credits) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-900/60 uppercase">Amount</div>
                    <div class="font-display font-black text-2xl">₹{{ number_format($package->price_inr) }}</div>
                </div>
            </div>
        </div>
        
        <button id="payBtn" class="w-full mt-6 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg">
            Pay ₹{{ number_format($package->price_inr) }} via Razorpay →
        </button>
        
        <p class="text-xs text-center text-ink-900/40 mt-4">
            🔒 Secure payment via Razorpay • UPI / Cards / Net Banking
        </p>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('payBtn').addEventListener('click', function() {
    var options = {
        "key": "{{ $razorpayKey }}",
        "amount": "{{ $package->price_inr * 100 }}",
        "currency": "INR",
        "name": "PGFind",
        "description": "{{ $package->name }} — {{ $package->total_credits }} credits",
        "order_id": "{{ $payment->razorpay_order_id }}",
        "handler": function (response) {
            // On success, submit form to verify on server
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('owner.payment.callback') }}";
            
            var fields = {
                'razorpay_order_id': response.razorpay_order_id,
                'razorpay_payment_id': response.razorpay_payment_id,
                'razorpay_signature': response.razorpay_signature,
            };
            
            for (var key in fields) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
        },
        "prefill": {
            "name": "{{ $user->name }}",
            "email": "{{ $user->email }}",
            "contact": "{{ $user->phone }}"
        },
        "theme": {
            "color": "#ff6b5b"
        },
        "modal": {
            "ondismiss": function() {
                window.location.href = "{{ route('owner.payment.failed') }}";
            }
        }
    };
    
    var rzp = new Razorpay(options);
    rzp.on('payment.failed', function (response) {
        window.location.href = "{{ route('owner.payment.failed') }}?error_description=" + encodeURIComponent(response.error.description);
    });
    rzp.open();
});
</script>

@endsection