@extends('layouts.dashboard')
@section('title', 'Bed Plan: ' . $owner->name)
@section('content')

@php
    $sub = $summary['sub'];
    $wallet = $summary['wallet'];
@endphp

<a href="{{ route('admin.bed-plans.index') }}" class="text-sm text-coral-600 font-semibold">← All owners</a>
<h1 class="font-display font-black text-3xl mt-2">{{ $owner->name }}</h1>
<p class="text-ink-900/60 text-sm">{{ $owner->phone }} {{ $owner->email ? '· ' . $owner->email : '' }}</p>

@if(session('error'))
    <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
@endif
@if(!$enabled)
    <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-xl text-sm">The Bed Plan is switched OFF for everyone right now, so nothing is charged and nobody is restricted.</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-6">
    <div class="p-4 rounded-xl bg-white border border-ink-900/10"><div class="text-xs text-ink-900/50">Plan</div><div class="font-display font-black text-xl mt-1">{{ $sub ? ucfirst($sub->status) : 'Not on plan' }}</div></div>
    <div class="p-4 rounded-xl bg-white border border-ink-900/10"><div class="text-xs text-ink-900/50">Bed Wallet</div><div class="font-display font-black text-xl mt-1">₹{{ number_format((float) $wallet->balance, 2) }}</div></div>
    <div class="p-4 rounded-xl bg-white border border-ink-900/10"><div class="text-xs text-ink-900/50">Beds</div><div class="font-display font-black text-xl mt-1">{{ $summary['beds'] }}</div></div>
    <div class="p-4 rounded-xl bg-white border border-ink-900/10"><div class="text-xs text-ink-900/50">Price per bed</div><div class="font-display font-black text-xl mt-1">{{ $sub ? '₹' . number_format($summary['price'], 2) : '-' }}</div></div>
    <div class="p-4 rounded-xl bg-white border border-ink-900/10"><div class="text-xs text-ink-900/50">{{ $sub && $sub->status === 'active' ? 'Renews on' : 'Next charge' }}</div><div class="font-display font-black text-xl mt-1">{{ $sub && $sub->status === 'active' ? $sub->cycle_end?->format('d M Y') : ($sub ? '₹' . number_format($summary['renewal_cost'], 2) : '-') }}</div></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
    {{-- Plan on / off / price --}}
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-lg">Plan for this owner</h2>
        @if(!$sub || $sub->status !== 'active')
            <form method="POST" action="{{ route('admin.bed-plans.owner.activate', $owner) }}" class="mt-3 space-y-3">
                @csrf
                <label class="block text-xs">Plan
                    <select name="bed_plan_id" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200">
                        @foreach($plans as $p)<option value="{{ $p->id }}" @selected($sub ? $sub->bed_plan_id == $p->id : $p->is_default)>{{ $p->name }} (₹{{ number_format($p->price_per_bed, 2) }} per bed)</option>@endforeach
                    </select>
                </label>
                <label class="block text-xs">Special price for this owner, ₹ per bed (optional)
                    <input name="custom_price_per_bed" type="number" step="0.01" min="0" value="{{ $sub?->custom_price_per_bed }}" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200">
                </label>
                <p class="text-xs text-ink-900/50">The month starts now and {{ $summary['beds'] }} bed(s) are charged from the Bed Wallet at once. If the wallet cannot pay, the owner is restricted until they recharge.</p>
                <button class="px-5 py-2.5 bg-coral-500 text-white rounded-xl font-bold text-sm" onclick="return confirm('Switch the Bed Plan on for {{ addslashes($owner->name) }}? This month is charged now.')">{{ $sub ? 'Switch on again' : 'Switch on' }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.bed-plans.owner.price', $owner) }}" class="mt-3 space-y-3">
                @csrf
                <label class="block text-xs">Plan
                    <select name="bed_plan_id" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200">
                        @foreach($plans as $p)<option value="{{ $p->id }}" @selected($sub->bed_plan_id == $p->id)>{{ $p->name }} (₹{{ number_format($p->price_per_bed, 2) }} per bed)</option>@endforeach
                    </select>
                </label>
                <label class="block text-xs">Special price for this owner, ₹ per bed (empty = the plan's price)
                    <input name="custom_price_per_bed" type="number" step="0.01" min="0" value="{{ $sub->custom_price_per_bed }}" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200">
                </label>
                <p class="text-xs text-ink-900/50">A new price is used from this owner's next charge. This month stays as it was paid.</p>
                <button class="px-5 py-2.5 bg-ink-900 text-white rounded-xl font-bold text-sm">Save price</button>
            </form>
        @endif
        @if($sub && $sub->status !== 'disabled')
            <form method="POST" action="{{ route('admin.bed-plans.owner.disable', $owner) }}" class="mt-4 pt-4 border-t border-ink-900/10">
                @csrf
                <button class="text-sm text-rose-700 font-bold" onclick="return confirm('Switch the plan off for this owner? Nothing is charged and nothing is restricted.')">Switch the plan off for this owner</button>
            </form>
        @endif
    </div>

    {{-- Wallet by hand --}}
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-lg">Bed Wallet, by hand</h2>
        <form method="POST" action="{{ route('admin.bed-plans.owner.adjust', $owner) }}" class="mt-3 space-y-3">
            @csrf
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-1"><input type="radio" name="type" value="credit" checked> Add money</label>
                <label class="flex items-center gap-1"><input type="radio" name="type" value="debit"> Take money out</label>
            </div>
            <label class="block text-xs">Amount ₹<input name="amount" type="number" step="0.01" min="0.01" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
            <label class="block text-xs">Reason (the owner sees it in their history)<input name="notes" required maxlength="300" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
            <button class="px-5 py-2.5 bg-ink-900 text-white rounded-xl font-bold text-sm">Update wallet</button>
            <p class="text-xs text-ink-900/50">If the plan had ended and the new balance covers this month, it starts again at once.</p>
        </form>
    </div>
</div>

{{-- History --}}
<div class="mt-8 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="p-6 border-b border-ink-900/10"><h2 class="font-display font-bold text-lg">History</h2></div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase"><tr><th class="px-6 py-3">When</th><th>What</th><th class="text-right">Amount</th><th class="text-right pr-6">Balance after</th></tr></thead>
        <tbody>
        @forelse($transactions as $tx)
            <tr class="border-t border-ink-900/5">
                <td class="px-6 py-3">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                <td class="text-xs"><div class="font-semibold">{{ \App\Models\BedWalletTransaction::sourceLabel($tx->source) }}</div>@if($tx->notes)<div class="text-ink-900/50">{{ $tx->notes }}</div>@endif @if($tx->actioned_by)<div class="text-ink-900/40">by admin #{{ $tx->actioned_by }}</div>@endif</td>
                <td class="text-right font-bold {{ $tx->type === 'credit' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $tx->type === 'credit' ? '+' : '−' }}₹{{ number_format((float) $tx->amount, 2) }}</td>
                <td class="text-right pr-6">₹{{ number_format((float) $tx->balance_after, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-6 py-10 text-center text-ink-900/50">Nothing yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="p-4">{{ $transactions->links() }}</div>
</div>

@endsection
