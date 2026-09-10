@extends('layouts.tenant')
@section('title', 'Pay Token')
@section('content')

<div class="max-w-2xl mx-auto">

    {{-- flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 font-semibold">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-rose-50 border border-rose-300 rounded-xl text-rose-800 font-semibold">{{ session('error') }}</div>
    @endif

    <div class="bg-gradient-to-br from-ink-950 to-ink-900 text-cream rounded-3xl p-6 lg:p-8">
        <h1 class="font-display font-black text-2xl lg:text-3xl">Pay Token Amount</h1>
        <p class="text-cream/70 mt-1">Confirm your PG booking by paying the token amount.</p>
    </div>

    @if(!$tokenPayment)
        {{-- Field exec ne amount set nahi kiya --}}
        <div class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-8 text-center">
            <div class="text-5xl mb-3">⏳</div>
            <h2 class="font-bold text-xl text-ink-950">Token amount not set yet</h2>
            <p class="text-ink-900/60 mt-2">Your field executive will set the token amount after your visit. Please check back shortly or contact us.</p>
            <a href="https://wa.me/918006680092" target="_blank" class="inline-block mt-4 px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm">💬 Contact us</a>
        </div>

    @elseif($tokenPayment->status === 'paid')
        <div class="mt-6 bg-white rounded-2xl border-2 border-emerald-300 p-8 text-center">
            <div class="text-5xl mb-3">✅</div>
            <h2 class="font-bold text-xl text-ink-950">Token already paid</h2>
            <p class="text-ink-900/60 mt-2">₹{{ number_format($tokenPayment->amount) }} received via {{ ucfirst($tokenPayment->payment_method) }}.</p>
            <a href="{{ route('tenant.onboarding') }}" class="inline-block mt-4 px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm">Continue →</a>
        </div>

    @else
        {{-- Amount set, pending payment --}}
        <div class="mt-6 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
            <div class="p-6 bg-coral-50 border-b border-coral-100 text-center">
                <div class="text-sm text-ink-900/60 uppercase font-bold tracking-wider">Token Amount</div>
                <div class="font-display font-black text-4xl text-ink-950 mt-1">₹{{ number_format($tokenPayment->amount) }}</div>
                @if($property)
                    <div class="text-sm text-ink-900/60 mt-2">For: <strong>{{ $property->name }}</strong></div>
                @endif
                @if($tokenPayment->payment_method === 'cash')
                    <div class="mt-3 inline-block px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">💵 Cash selected — pay your field executive</div>
                @endif
            </div>

            <div class="p-6 space-y-3">
                <h3 class="font-bold text-ink-950 text-center mb-2">Choose payment method</h3>

                {{-- ONLINE --}}
                @if($razorpayOrderId)
                    <button id="payOnlineBtn" type="button" class="w-full py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold flex items-center justify-center gap-2 transition shadow-lg shadow-coral-500/30">
                        💳 Pay Online (UPI / Card / Netbanking)
                    </button>
                @else
                    <div class="w-full py-4 bg-ink-100 text-ink-500 rounded-xl font-bold text-center text-sm">Online payment temporarily unavailable. Please choose cash.</div>
                @endif

                {{-- CASH --}}
                <form method="POST" action="{{ route('tenant.token.cash') }}">
                    @csrf
                    <input type="hidden" name="token_payment_id" value="{{ $tokenPayment->id }}">
                    <button type="submit" class="w-full py-4 bg-white border-2 border-ink-900/15 hover:border-coral-500 text-ink-950 rounded-xl font-bold flex items-center justify-center gap-2 transition">
                        💵 Pay Cash to Field Executive
                    </button>
                </form>

                <p class="text-xs text-center text-ink-900/40 mt-2">Token is refundable as per terms. Paid securely.</p>
            </div>
        </div>

        {{-- Razorpay checkout --}}
        @if($razorpayOrderId)
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
        document.getElementById('payOnlineBtn').addEventListener('click', function () {
            var options = {
                "key": "{{ $razorpayKeyId }}",
                "amount": "{{ (int) round($tokenPayment->amount * 100) }}",
                "currency": "INR",
                "name": "Pizi",
                "description": "PG Token Payment",
                "order_id": "{{ $razorpayOrderId }}",
                "prefill": {
                    "name": "{{ $tenant->name ?? auth()->user()->name }}",
                    "contact": "{{ $tenant->phone ?? auth()->user()->phone }}",
                    "email": "{{ $tenant->email ?? auth()->user()->email }}"
                },
                "theme": { "color": "#ff6b5b" },
                "handler": function (response) {
                    var f = document.createElement('form');
                    f.method = 'POST';
                    f.action = "{{ route('tenant.token.verify') }}";
                    var fields = {
                        '_token': "{{ csrf_token() }}",
                        'token_payment_id': "{{ $tokenPayment->id }}",
                        'razorpay_payment_id': response.razorpay_payment_id,
                        'razorpay_order_id': response.razorpay_order_id,
                        'razorpay_signature': response.razorpay_signature
                    };
                    for (var k in fields) {
                        var i = document.createElement('input');
                        i.type = 'hidden'; i.name = k; i.value = fields[k];
                        f.appendChild(i);
                    }
                    document.body.appendChild(f); f.submit();
                }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        });
        </script>
        @endif
    @endif

</div>
@endsection
