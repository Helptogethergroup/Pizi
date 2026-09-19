@extends('layouts.dashboard')
@section('title', 'Complete Your Purchase')
@section('content')

<div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
    
    {{-- LEFT: Order Summary --}}
    <div>
        <a href="{{ route('owner.packages') }}" class="inline-flex items-center gap-1 text-sm text-coral-600 hover:text-coral-700 mb-6 font-semibold">
            ← Back to packages
        </a>
        
        <h1 class="font-display font-black text-3xl">Order Summary</h1>
        
        <div class="mt-8 p-8 rounded-2xl border-2 border-coral-200 bg-gradient-to-br from-coral-50 to-white">
            
            {{-- Package Name --}}
            <p class="text-xs uppercase tracking-wider text-coral-700 font-black">{{ $package->name }}</p>
            <h2 class="font-display font-black text-3xl text-ink-950 mt-1">{{ $package->name }}</h2>
            
            {{-- Divider --}}
            <div class="my-6 h-px bg-coral-200"></div>
            
            {{-- Breakdown --}}
            <div class="space-y-4 mb-8">
                <div class="flex justify-between items-baseline">
                    <span class="text-ink-900/70">{{ $package->credits }} base credits</span>
                    <span class="font-display font-bold text-lg">{{ $package->credits }}</span>
                </div>
                
                @if($package->bonus_credits > 0)
                <div class="flex justify-between items-baseline p-3 rounded-lg bg-emerald-50">
                    <span class="text-emerald-700 font-semibold">{{ $package->bonus_credits }} bonus credits <i class="fa-solid fa-gift fa-fw"></i></span>
                    <span class="font-display font-bold text-lg text-emerald-700">+ {{ $package->bonus_credits }}</span>
                </div>
                @endif
                
                <div class="pt-4 border-t-2 border-coral-200 flex justify-between items-baseline">
                    <span class="font-semibold text-ink-950">Total credits</span>
                    <span class="font-display font-black text-3xl text-coral-600">{{ $package->total_credits }}</span>
                </div>
            </div>
            
            {{-- Price Box --}}
            <div class="p-6 rounded-xl bg-ink-900 text-cream">
                <p class="text-xs uppercase tracking-wider text-cream/60 font-bold">Total Amount</p>
                <div class="flex items-baseline justify-between mt-2">
                    <div class="font-display font-black text-4xl">₹{{ number_format($package->price_inr) }}</div>
                    <div class="text-right text-xs text-cream/60">
                        <p>₹{{ number_format($package->price_per_credit, 1) }}/credit</p>
                        <p class="text-cream/40 mt-0.5">{{ $package->discount_percent ?? '0' }}% discount</p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Features --}}
        <div class="mt-8 space-y-3">
            <div class="flex items-center gap-3 text-sm">
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">✓</span>
                <span class="text-ink-900/70">No subscription — one-time payment only</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">✓</span>
                <span class="text-ink-900/70">Credits never expire</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">✓</span>
                <span class="text-ink-900/70">Instant credit delivery after payment</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">✓</span>
                <span class="text-ink-900/70">Full refund within 7 days if unused</span>
            </div>
        </div>
    </div>
    
    {{-- RIGHT: Payment Form --}}
    <div>
        <div class="sticky top-8">
            <h2 class="font-display font-bold text-2xl mb-6">Payment Method</h2>
            
            <div class="p-8 rounded-2xl border-2 border-ink-900/10 bg-white">
                
                {{-- Owner Info --}}
                <div class="mb-8 pb-8 border-b border-ink-900/10">
                    <p class="text-xs uppercase tracking-wider text-ink-900/50 font-bold mb-3">Buying As</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-coral-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-coral-600 font-bold text-lg">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-ink-950">{{ $user->name }}</p>
                            <p class="text-xs text-ink-900/60">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>
                
                {{-- Payment Info --}}
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold text-ink-950">Secure Payment</span>
                    </div>
                    <p class="text-xs text-ink-900/60 mb-4">Your payment will be processed securely through Razorpay. We support:</p>
                    <div class="space-y-2 text-sm text-ink-900/70 pl-5">
                        <p>✓ UPI (Google Pay, PhonePe, Paytm, etc.)</p>
                        <p>✓ Credit & Debit Cards</p>
                        <p>✓ Net Banking</p>
                        <p>✓ Wallets</p>
                    </div>
                </div>
                
                {{-- CTA Button --}}
                <button id="payBtn" class="w-full py-5 bg-gradient-to-r from-coral-500 to-coral-600 hover:from-coral-600 hover:to-coral-700 text-white rounded-xl font-black text-lg transition-all duration-200 shadow-lg shadow-coral-500/30 hover:shadow-xl">
                    Pay ₹{{ number_format($package->price_inr) }} Now →
                </button>
                
                <p class="text-xs text-center text-ink-900/40 mt-4">
                    <i class="fa-solid fa-lock fa-fw"></i> Powered by Razorpay • 256-bit SSL Encrypted
                </p>
            </div>
            
            {{-- Security Badges --}}
            <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200">
                    <p class="text-2xl"><i class="fa-solid fa-lock fa-fw"></i></p>
                    <p class="text-xs font-semibold text-emerald-700 mt-2">Secure</p>
                </div>
                <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                    <p class="text-2xl"><i class="fa-solid fa-bolt fa-fw"></i></p>
                    <p class="text-xs font-semibold text-blue-700 mt-2">Instant</p>
                </div>
                <div class="p-4 rounded-lg bg-purple-50 border border-purple-200">
                    <p class="text-2xl">✓</p>
                    <p class="text-xs font-semibold text-purple-700 mt-2">Verified</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('payBtn').addEventListener('click', function() {
    var options = {
        "key": "{{ $razorpayKey }}",
        "amount": "{{ $package->price_inr * 100 }}",
        "currency": "INR",
        "name": "Pizi",
        "description": "{{ $package->name }} — {{ $package->total_credits }} Credits",
        "order_id": "{{ $payment->razorpay_order_id }}",
        "handler": function (response) {
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
        window.location.href = "{{ route('owner.payment.failed') }}?error=" + encodeURIComponent(response.error.description);
    });
    rzp.open();
});
</script>

@endsection