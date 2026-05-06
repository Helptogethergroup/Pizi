@extends('layouts.dashboard')
@section('title', 'My Wallet')
@section('content')

<h1 class="font-display font-black text-4xl mb-2">My Wallet</h1>
<p class="text-ink-900/60">Manage your credits and view transaction history.</p>

{{-- Balance card --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
    <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-ink-900 to-ink-950 text-cream relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-60 h-60 bg-coral-500/20 rounded-full blur-3xl"></div>
        <div class="relative">
            <div class="text-xs uppercase tracking-wider text-cream/60">Current balance</div>
            <div class="font-display font-black text-6xl mt-2">{{ number_format($wallet->balance) }}</div>
            <div class="text-cream/60 mt-1">credits available</div>
            
            <div class="grid grid-cols-2 gap-4 mt-8 pt-6 border-t border-cream/10">
                <div>
                    <div class="text-xs text-cream/60">Lifetime added</div>
                    <div class="font-bold text-xl mt-1">{{ number_format($wallet->lifetime_added) }}</div>
                </div>
                <div>
                    <div class="text-xs text-cream/60">Lifetime spent</div>
                    <div class="font-bold text-xl mt-1">{{ number_format($wallet->lifetime_spent) }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="p-8 rounded-2xl bg-coral-50 border border-coral-100">
        <div class="text-xs uppercase tracking-wider text-coral-700">Need more credits?</div>
        <h3 class="font-display font-bold text-2xl mt-2">Buy credits</h3>
        <p class="text-sm text-ink-900/60 mt-2">Recharge your wallet to unlock more leads.</p>
      <a href="{{ route('owner.packages') }}" class="block text-center mt-4 w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
            Buy credits →
        </a>
       
    </div>
</div>

{{-- Transactions --}}
<div class="mt-10 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="p-6 border-b border-ink-900/10">
        <h2 class="font-display font-bold text-xl">Transaction history</h2>
    </div>
    
    @if($transactions->count())
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
                <tr>
                    <th class="px-6 py-3">When</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right pr-6">Balance after</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $tx)
                    <tr class="border-t border-ink-900/5">
                        <td class="px-6 py-3">
                            <div class="text-sm">{{ $tx->created_at->format('d M, h:i A') }}</div>
                            <div class="text-xs text-ink-900/50">{{ $tx->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            @if($tx->type === 'credit')
                                <span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700 font-semibold">+ Credit</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-rose-100 text-rose-700 font-semibold">− Debit</span>
                            @endif
                        </td>
                        <td class="text-xs">
                            <div>{{ $tx->sourceLabel() }}</div>
                            @if($tx->notes)<div class="text-ink-900/50 italic mt-0.5">{{ $tx->notes }}</div>@endif
                        </td>
                        <td class="text-right font-bold {{ $tx->type === 'credit' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $tx->type === 'credit' ? '+' : '−' }}{{ number_format($tx->amount) }}
                        </td>
                        <td class="text-right pr-6 font-semibold">{{ number_format($tx->balance_after) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $transactions->links() }}</div>
    @else
        <p class="p-12 text-center text-ink-900/50">No transactions yet.</p>
    @endif
</div>

@endsection