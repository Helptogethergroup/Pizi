@extends('layouts.dashboard')
@section('title', 'Buy Credits')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-4xl">Buy Credits</h1>
        <p class="text-ink-900/60 mt-1">Choose a package that fits your needs.</p>
    </div>
    <div class="px-5 py-3 bg-ink-900 text-cream rounded-xl">
        Current balance: <strong>{{ number_format($wallet->balance) }}</strong> credits
    </div>
</div>

@if($packages->count())
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        @foreach($packages as $pkg)
            <div class="relative p-8 rounded-3xl border-2 {{ $pkg->is_popular ? 'border-coral-500 bg-coral-50' : 'border-ink-900/10 bg-white' }}">
                @if($pkg->is_popular)
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-coral-500 text-white text-xs font-bold uppercase tracking-wider">⭐ Most Popular</span>
                @endif
                
                <h3 class="font-display font-bold text-2xl">{{ $pkg->name }}</h3>
                @if($pkg->description)
                    <p class="text-sm text-ink-900/60 mt-1">{{ $pkg->description }}</p>
                @endif
                
                <div class="mt-6">
                    <div class="font-display font-black text-5xl text-ink-950">₹{{ number_format($pkg->price_inr) }}</div>
                    <div class="text-sm text-ink-900/50 mt-1">one-time payment</div>
                </div>
                
                <div class="mt-6 p-4 rounded-xl bg-ink-950 text-cream">
                    <div class="text-xs uppercase tracking-wider text-cream/60">You get</div>
                    <div class="font-display font-bold text-3xl mt-1">{{ number_format($pkg->total_credits) }} credits</div>
                    @if($pkg->bonus_credits > 0)
                        <div class="text-xs text-coral-400 mt-1">{{ $pkg->credits }} base + <strong>{{ $pkg->bonus_credits }} bonus</strong></div>
                    @endif
                    <div class="text-xs text-cream/50 mt-2">≈ ₹{{ $pkg->price_per_credit }}/credit</div>
                </div>
                
                <a href="{{ route('owner.checkout', $pkg) }}" class="block mt-6 w-full text-center py-4 rounded-xl font-bold {{ $pkg->is_popular ? 'bg-coral-500 hover:bg-coral-600 text-white' : 'bg-ink-900 hover:bg-ink-800 text-cream' }}">
                    Buy now →
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="bg-white p-12 rounded-2xl border border-ink-900/10 text-center mt-8">
        <p class="text-ink-900/50">No packages available yet. Contact admin.</p>
    </div>
@endif

<div class="mt-12 p-6 rounded-2xl bg-amber-50 border border-amber-200">
    <h3 class="font-display font-bold text-lg text-amber-900">💡 How credits work</h3>
    <ul class="text-sm text-amber-900/80 mt-3 space-y-1 list-disc pl-5">
        <li>Use credits to unlock leads and view contact details (phone, email).</li>
        <li>Different lead types cost different credits — check pricing on the leads page.</li>
        <li>Credits never expire. Buy once, use anytime.</li>
        <li>Payment is secured by Razorpay (UPI, Cards, Net Banking).</li>
    </ul>
</div>

@endsection