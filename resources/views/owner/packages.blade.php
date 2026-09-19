@extends('layouts.dashboard')
@section('title', 'Buy Credits to Unlock Leads')
@section('content')

<div class="mb-8">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="font-display font-black text-4xl md:text-5xl">Unlock leads. Scale fast.</h1>
            <p class="text-lg text-ink-900/60 mt-2 max-w-2xl">Buy credits once, use anytime. No subscription. No expiry.</p>
        </div>
        <div class="md:text-right">
            <p class="text-xs text-ink-900/50 uppercase tracking-wider font-semibold">Your Balance</p>
            <div class="font-display font-black text-4xl md:text-5xl text-coral-600 mt-1">{{ number_format($wallet->balance) }}</div>
            <p class="text-sm text-ink-900/60 mt-0.5">credits</p>
        </div>
    </div>
</div>

{{-- PRICING CARDS --}}
@if($packages->count())
<div class="space-y-4 lg:space-y-0 lg:grid lg:grid-cols-3 lg:gap-6 mt-12">
    @foreach($packages as $pkg)
    <div class="group relative rounded-2xl border-2 transition-all duration-300 {{ $pkg->is_popular ? 'border-coral-500 bg-gradient-to-br from-coral-50 to-white shadow-2xl shadow-coral-500/20 lg:scale-105' : 'border-ink-900/10 bg-white hover:border-coral-300 hover:shadow-lg' }}">
        
        {{-- Badge --}}
        @if($pkg->is_popular)
        <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1.5 rounded-full bg-gradient-to-r from-coral-500 to-coral-600 text-white text-xs font-black uppercase tracking-wider shadow-lg">
            <i class="fa-solid fa-star fa-fw" style="color:#f59e0b"></i> Best Value
        </div>
        @endif
        
        <div class="p-8 lg:p-10">
            
            {{-- Header --}}
            <div class="mb-8">
                <h3 class="font-display font-black text-2xl lg:text-3xl text-ink-950">{{ $pkg->name }}</h3>
                @if($pkg->description)
                <p class="text-sm text-ink-900/60 mt-2">{{ $pkg->description }}</p>
                @endif
            </div>
            
            {{-- Price --}}
            <div class="mb-8 p-6 rounded-2xl {{ $pkg->is_popular ? 'bg-white shadow-sm' : 'bg-cream' }}">
                <p class="text-xs uppercase tracking-wider text-ink-900/50 font-semibold">Amount to Pay</p>
                <div class="font-display font-black text-5xl lg:text-6xl text-ink-950 mt-1">₹{{ number_format($pkg->price_inr) }}</div>
                <p class="text-xs text-ink-900/40 mt-2">One-time payment • No recurring charges</p>
            </div>
            
            {{-- Credits Breakdown --}}
            <div class="mb-8 space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-emerald-700 font-bold text-xs">✓</span>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-ink-900/50 font-semibold">Base Credits</p>
                        <p class="font-display font-black text-3xl text-ink-950">{{ number_format($pkg->credits) }}</p>
                    </div>
                </div>
                
                @if($pkg->bonus_credits > 0)
                <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                    <div class="w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-white font-bold text-xs"><i class="fa-solid fa-gift fa-fw"></i></span>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-emerald-700 font-semibold">Bonus Credits</p>
                        <p class="font-display font-black text-2xl text-emerald-700">+ {{ number_format($pkg->bonus_credits) }}</p>
                        <p class="text-xs text-emerald-600 mt-1">Free bonus ({{ round(($pkg->bonus_credits / $pkg->total_credits) * 100) }}% extra)</p>
                    </div>
                </div>
                @endif
                
                <div class="pt-4 border-t-2 border-ink-900/5">
                    <p class="text-xs uppercase tracking-wider text-ink-900/50 font-semibold mb-2">You'll Get</p>
                    <div class="flex items-baseline gap-2">
                        <span class="font-display font-black text-4xl text-coral-600">{{ number_format($pkg->total_credits) }}</span>
                        <span class="text-lg text-ink-900/60">total credits</span>
                    </div>
                </div>
            </div>
            
            {{-- Value --}}
            <div class="mb-8 p-4 rounded-xl bg-ink-900/5">
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <p class="text-xs text-ink-900/50 uppercase tracking-wider font-semibold">Per Credit</p>
                        <p class="font-display font-bold text-xl text-ink-950 mt-1">₹{{ number_format($pkg->price_per_credit, 1) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-900/50 uppercase tracking-wider font-semibold">Savings</p>
                        <p class="font-display font-bold text-xl text-emerald-600 mt-1">{{ $pkg->discount_percent ?? '0' }}%</p>
                    </div>
                </div>
            </div>
            
            {{-- Button --}}
            <a href="{{ route('owner.checkout', $pkg) }}" class="block w-full text-center py-4 px-4 rounded-xl font-black text-lg transition-all duration-200 {{ $pkg->is_popular ? 'bg-gradient-to-r from-coral-500 to-coral-600 hover:from-coral-600 hover:to-coral-700 text-white shadow-lg shadow-coral-500/30 group-hover:shadow-xl' : 'bg-ink-900 hover:bg-ink-800 text-cream' }}">
                Buy Now →
            </a>
        </div>
    </div>
    @endforeach
</div>

{{-- Info Section --}}
<div class="mt-16 grid md:grid-cols-2 gap-8">
    <div class="p-8 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-50/50 border border-blue-200">
        <h3 class="font-display font-bold text-xl text-blue-900 mb-4"><i class="fa-solid fa-lightbulb fa-fw"></i> How Credits Work</h3>
        <ul class="space-y-3 text-sm text-blue-900/80">
            <li class="flex items-start gap-3">
                <span class="text-lg mt-0.5">1️⃣</span>
                <span><strong>Buy credits</strong> — choose any package above</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg mt-0.5">2️⃣</span>
                <span><strong>Unlock leads</strong> — view phone, email, budget from property inquiries</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg mt-0.5">3️⃣</span>
                <span><strong>Follow up</strong> — call, message, visit tenants at properties</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg mt-0.5"><i class="fa-solid fa-bullseye fa-fw"></i></span>
                <span><strong>Credits never expire</strong> — use them anytime, no rush</span>
            </li>
        </ul>
    </div>
    
    <div class="p-8 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-50/50 border border-amber-200">
        <h3 class="font-display font-bold text-xl text-amber-900 mb-4"><i class="fa-solid fa-lock fa-fw"></i> Safe & Secure</h3>
        <ul class="space-y-3 text-sm text-amber-900/80">
            <li class="flex items-start gap-3">
                <span class="text-lg">✓</span>
                <span><strong>Razorpay</strong> — India's most trusted payment gateway</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg">✓</span>
                <span><strong>SSL Encrypted</strong> — your card details are safe</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg">✓</span>
                <span><strong>Multiple payment methods</strong> — UPI, cards, net banking</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg">✓</span>
                <span><strong>Instant confirmation</strong> — credits added to wallet instantly</span>
            </li>
        </ul>
    </div>
</div>

@else
<div class="bg-white p-12 rounded-2xl border border-ink-900/10 text-center mt-12">
    <p class="text-xl text-ink-900/50 font-semibold">No packages available yet.</p>
    <p class="text-sm text-ink-900/40 mt-2">Admin is setting up pricing plans. Check back soon!</p>
</div>
@endif

@endsection