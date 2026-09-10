{{-- File: resources/views/field/token-collection.blade.php
     NOTE: @extends line ko apne field-executive ke baaki pages jaisa rakho.
     Agar field exec pages 'layouts.dashboard' use karte hain to theek,
     warna apna layout naam daal do. --}}
@extends('layouts.dashboard')
@section('title', 'Token Collection')
@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 font-semibold">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-300 rounded-xl text-rose-800 font-semibold">{{ session('error') }}</div>
    @endif

    <div>
        <h1 class="font-display font-black text-2xl lg:text-3xl text-ink-950">Token Collection</h1>
        <p class="text-ink-900/60 mt-1">Set token amount for a tenant, and collect cash payments.</p>
    </div>

    {{-- SET TOKEN AMOUNT --}}
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-bold text-lg text-ink-950 mb-4">💳 Set Token Amount</h2>
        <form method="POST" action="{{ route('field.token.set') }}" class="grid sm:grid-cols-3 gap-3">
            @csrf
            <div class="sm:col-span-1">
                <label class="text-xs font-semibold text-ink-900/60 uppercase">Tenant Phone</label>
                <input name="phone" required placeholder="10-digit phone" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            </div>
            <div class="sm:col-span-1">
                <label class="text-xs font-semibold text-ink-900/60 uppercase">Token Amount (₹)</label>
                <input name="amount" type="number" min="1" required placeholder="e.g. 1000" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            </div>
            <div class="sm:col-span-1 flex items-end">
                <button class="w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold transition">Set Amount</button>
            </div>
        </form>
        <p class="text-xs text-ink-900/50 mt-2">After setting, tenant sees this amount and can pay Online (Razorpay) or Cash. Cash payments appear below for you to collect.</p>
    </div>

    {{-- PENDING CASH --}}
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-bold text-lg text-ink-950 mb-4">💵 Pending Cash Collections ({{ $pendingCash->count() }})</h2>
        @if($pendingCash->isEmpty())
            <p class="text-ink-900/50 text-sm">No pending cash collections right now.</p>
        @else
            <div class="space-y-3">
                @foreach($pendingCash as $tp)
                    <div class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl border border-ink-900/10 bg-cream">
                        <div>
                            <div class="font-bold text-ink-950">{{ $tp->tenant_name }}</div>
                            <div class="text-sm text-ink-900/60">{{ $tp->tenant_phone }} · <span class="font-bold text-coral-600">₹{{ number_format($tp->amount) }}</span></div>
                        </div>
                        <form method="POST" action="{{ route('field.token.collect') }}">
                            @csrf
                            <input type="hidden" name="token_payment_id" value="{{ $tp->id }}">
                            <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm transition">✓ Mark Collected</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- COLLECTED --}}
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-bold text-lg text-ink-950 mb-4">✅ Recently Collected</h2>
        @if($collected->isEmpty())
            <p class="text-ink-900/50 text-sm">No tokens collected yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-ink-900/50 border-b border-ink-900/10">
                        <th class="py-2">Tenant</th><th class="py-2">Amount</th><th class="py-2">Mode</th><th class="py-2">Paid At</th>
                    </tr></thead>
                    <tbody>
                        @foreach($collected as $tp)
                            <tr class="border-b border-ink-900/5">
                                <td class="py-2.5">{{ $tp->tenant_name }}<div class="text-xs text-ink-900/50">{{ $tp->tenant_phone }}</div></td>
                                <td class="py-2.5 font-bold">₹{{ number_format($tp->amount) }}</td>
                                <td class="py-2.5"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $tp->payment_method === 'cash' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ ucfirst($tp->payment_method) }}</span></td>
                                <td class="py-2.5 text-ink-900/60">{{ $tp->paid_at ? \Carbon\Carbon::parse($tp->paid_at)->format('d M, h:i A') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
