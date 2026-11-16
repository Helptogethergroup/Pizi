@extends('layouts.dashboard')
@section('title', 'Bed Wallet')
@section('content')

@php
    $sub = $summary['sub'];
    $wallet = $summary['wallet'];
    $onPlan = $summary['on_plan'];
    $restricted = $summary['restricted'];
@endphp

<h1 class="font-display font-black text-4xl mb-2">Bed Wallet</h1>
<p class="text-ink-900/60">Your Bed Plan is paid from this wallet. It is separate from your Lead Wallet (lead credits).</p>

@if(session('error'))
    <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
@endif

@if($restricted)
    <div class="mt-6 p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900">
        <div class="font-display font-bold text-lg"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Your Bed Plan has ended</div>
        <p class="text-sm mt-1">You cannot add rooms, beds or tenants until the wallet is recharged. Everything else keeps working and none of your data is hidden. Add at least <strong>₹{{ number_format(max(0, $summary['renewal_cost'] - (float) $wallet->balance), 2) }}</strong> to renew now.</p>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
    {{-- Balance --}}
    <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-ink-900 to-ink-950 text-cream relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-60 h-60 bg-coral-500/20 rounded-full blur-3xl"></div>
        <div class="relative">
            <div class="text-xs uppercase tracking-wider text-cream/60">Bed Wallet balance</div>
            <div class="font-display font-black text-6xl mt-2">₹{{ number_format((float) $wallet->balance, 2) }}</div>

            <div class="grid grid-cols-3 gap-4 mt-8 pt-6 border-t border-cream/10">
                <div>
                    <div class="text-xs text-cream/60">Your beds</div>
                    <div class="font-bold text-xl mt-1">{{ $summary['beds'] }}</div>
                </div>
                <div>
                    <div class="text-xs text-cream/60">Price per bed / month</div>
                    <div class="font-bold text-xl mt-1">{{ $onPlan ? '₹' . number_format($summary['price'], 2) : '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-cream/60">Next month's charge</div>
                    <div class="font-bold text-xl mt-1">{{ $onPlan ? '₹' . number_format($summary['renewal_cost'], 2) : '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Plan status --}}
    <div class="p-8 rounded-2xl {{ $restricted ? 'bg-rose-50 border border-rose-100' : 'bg-coral-50 border border-coral-100' }}">
        <div class="text-xs uppercase tracking-wider {{ $restricted ? 'text-rose-700' : 'text-coral-700' }}">Bed Plan</div>
        @if(!$enabled || !$sub || $sub->status === 'disabled')
            <h3 class="font-display font-bold text-2xl mt-2">Not charged</h3>
            <p class="text-sm text-ink-900/60 mt-2">The Bed Plan is not switched on for your account.</p>
        @elseif($sub->status === 'active')
            <h3 class="font-display font-bold text-2xl mt-2">Active</h3>
            <p class="text-sm text-ink-900/60 mt-2">Paid until <strong>{{ $sub->cycle_end?->format('d M Y') }}</strong>.</p>
            <p class="text-xs text-ink-900/50 mt-2">On that day {{ $summary['beds'] }} bed(s) x ₹{{ number_format($summary['price'], 2) }} = ₹{{ number_format($summary['renewal_cost'], 2) }} is taken from this wallet. If the balance is short, the plan ends and you must recharge.</p>
        @else
            <h3 class="font-display font-bold text-2xl mt-2 text-rose-700">Ended</h3>
            <p class="text-sm text-ink-900/60 mt-2">Recharge to start the next month.</p>
        @endif
    </div>
</div>

@if($onPlan && $enabled)
    <div class="mt-4 p-4 rounded-xl bg-white border border-ink-900/10 text-xs text-ink-900/60 leading-relaxed">
        Every bed you have counts for the full month, empty or occupied. The price already includes GST ({{ rtrim(rtrim(number_format($summary['gst_percent'], 2), '0'), '.') }}%).
        A bed you add in the middle of a month costs the full month at once. A bed you delete is not refunded, but you can add another bed in its place within the same month without paying again.
    </div>
@endif

{{-- Top-up --}}
<div class="mt-10">
    <h2 class="font-display font-bold text-2xl mb-4">Add money to your Bed Wallet</h2>
    @if($packages->count())
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($packages as $p)
                <div class="p-6 rounded-2xl bg-white border border-ink-900/10">
                    <div class="text-xs uppercase tracking-wider text-ink-900/50">{{ $p->name }}</div>
                    <div class="font-display font-black text-3xl mt-2">₹{{ number_format($p->amount_inr) }}</div>
                    @if($p->bonus_inr > 0)
                        <div class="text-sm text-emerald-700 font-semibold mt-1">+ ₹{{ number_format($p->bonus_inr) }} bonus</div>
                    @endif
                    <div class="text-xs text-ink-900/50 mt-1">You get ₹{{ number_format($p->wallet_credit) }} in your wallet</div>
                    <button type="button" data-package="{{ $p->id }}" class="bed-pay mt-4 w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Pay ₹{{ number_format($p->amount_inr) }}</button>
                </div>
            @endforeach
        </div>
        <p id="bed-pay-msg" class="text-sm mt-3"></p>
    @else
        <p class="p-6 rounded-xl bg-white border border-ink-900/10 text-ink-900/60">No top-up packages are available right now. Please contact Pizi.</p>
    @endif
</div>

{{-- History --}}
<div class="mt-10 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="p-6 border-b border-ink-900/10"><h2 class="font-display font-bold text-xl">Bed Wallet history</h2></div>
    @if($transactions->count())
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
                <tr><th class="px-6 py-3">When</th><th>What</th><th class="text-right">Amount</th><th class="text-right pr-6">Balance after</th></tr>
            </thead>
            <tbody>
                @foreach($transactions as $tx)
                    <tr class="border-t border-ink-900/5">
                        <td class="px-6 py-3">
                            <div>{{ $tx->created_at->format('d M Y, h:i A') }}</div>
                        </td>
                        <td class="text-xs">
                            <div class="font-semibold">{{ \App\Models\BedWalletTransaction::sourceLabel($tx->source) }}</div>
                            @if($tx->notes)<div class="text-ink-900/50 mt-0.5">{{ $tx->notes }}</div>@endif
                            @if($tx->gst_amount !== null && (float) $tx->gst_amount > 0)<div class="text-ink-900/40">GST included: ₹{{ number_format((float) $tx->gst_amount, 2) }}</div>@endif
                        </td>
                        <td class="text-right font-bold {{ $tx->type === 'credit' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $tx->type === 'credit' ? '+' : '−' }}₹{{ number_format((float) $tx->amount, 2) }}
                        </td>
                        <td class="text-right pr-6 font-semibold">₹{{ number_format((float) $tx->balance_after, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <div class="p-4">{{ $transactions->links() }}</div>
    @else
        <p class="p-12 text-center text-ink-900/50">Nothing here yet.</p>
    @endif
</div>

@if($packages->count() && $razorpayKey)
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var msg = document.getElementById('bed-pay-msg');
    function say(text, bad) { msg.textContent = text; msg.className = 'text-sm mt-3 ' + (bad ? 'text-rose-700' : 'text-emerald-700'); }
    function post(url, body) {
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(body) })
            .then(function (r) { return r.json().then(function (j) { j.__status = r.status; return j; }); });
    }
    document.querySelectorAll('.bed-pay').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.disabled = true;
            say('Starting the payment...', false);
            post(@json(route('owner.bed-wallet.order')), { package_id: btn.dataset.package }).then(function (o) {
                if (!o.success) { btn.disabled = false; return say(o.message || 'Could not start the payment.', true); }
                new Razorpay({
                    key: @json($razorpayKey),
                    amount: o.amount,
                    currency: o.currency,
                    name: 'Pizi',
                    description: 'Bed Wallet top-up',
                    order_id: o.order_id,
                    prefill: { name: @json(auth()->user()->name), email: @json(auth()->user()->email), contact: @json(auth()->user()->phone) },
                    theme: { color: '#FF6B5B' },
                    handler: function (resp) {
                        say('Confirming your payment...', false);
                        post(@json(route('owner.bed-wallet.verify')), resp).then(function (v) {
                            if (v.success) { say(v.message + ' Refreshing...', false); setTimeout(function () { location.reload(); }, 1200); }
                            else { btn.disabled = false; say(v.message || 'Could not confirm the payment.', true); }
                        }).catch(function () { btn.disabled = false; say('Network problem. If money was deducted, it will be added to your wallet after we check it.', true); });
                    },
                    modal: { ondismiss: function () { btn.disabled = false; say('', false); } }
                }).open();
            }).catch(function () { btn.disabled = false; say('Network problem. Please try again.', true); });
        });
    });
})();
</script>
@endif

@endsection
