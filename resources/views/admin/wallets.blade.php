@extends('layouts.dashboard')
@section('title', 'Wallet Management')
@section('content')

<h1 class="font-display font-black text-3xl mb-2">Owner Wallets</h1>
<p class="text-ink-900/60">Manage credit balances for all PG owners.</p>

<form class="flex gap-2 mt-6 mb-6">
    <input name="search" value="{{ request('search') }}" placeholder="Search owner name or email…" class="flex-1 px-3 py-2 rounded-lg border border-ink-900/15">
    <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg">Search</button>
</form>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Owner</th>
                <th class="text-right">Balance</th>
                <th class="text-right">Lifetime added</th>
                <th class="text-right">Lifetime spent</th>
                <th class="text-center pr-4">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($owners as $owner)
            @php $balance = $owner->wallet->balance ?? 0; @endphp
            <tr class="border-t border-ink-900/5">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $owner->name }}</div>
                    <div class="text-xs text-ink-900/50">{{ $owner->email }}</div>
                </td>
                <td class="text-right">
                    <span class="font-display font-bold text-2xl {{ $balance > 0 ? 'text-emerald-700' : 'text-ink-900/40' }}">{{ number_format($balance) }}</span>
                </td>
                <td class="text-right text-ink-900/60">{{ number_format($owner->wallet->lifetime_added ?? 0) }}</td>
                <td class="text-right text-ink-900/60">{{ number_format($owner->wallet->lifetime_spent ?? 0) }}</td>
                <td class="text-center pr-4">
                    <button onclick="openModal({{ $owner->id }}, '{{ addslashes($owner->name) }}', {{ $balance }})" 
                            class="px-3 py-2 rounded-lg bg-coral-500 text-white text-xs font-bold">+ / − Credits</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $owners->links() }}</div>

{{-- Recent transactions --}}
<div class="mt-10 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="p-6 border-b border-ink-900/10">
        <h2 class="font-display font-bold text-xl">Recent transactions (all owners)</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr><th class="px-6 py-3">When</th><th>Owner</th><th>Type</th><th>Source</th><th class="text-right pr-6">Amount</th></tr>
        </thead>
        <tbody>
        @foreach($recentTransactions as $tx)
            <tr class="border-t border-ink-900/5">
                <td class="px-6 py-2 text-xs">{{ $tx->created_at->format('d M, h:i A') }}</td>
                <td class="text-xs">{{ $tx->user?->name }}</td>
                <td>
                    @if($tx->type === 'credit')<span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">Credit</span>
                    @else<span class="px-2 py-1 rounded-full text-xs bg-rose-100 text-rose-700">Debit</span>@endif
                </td>
                <td class="text-xs">{{ $tx->sourceLabel() }}</td>
                <td class="text-right pr-6 font-bold {{ $tx->type === 'credit' ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $tx->type === 'credit' ? '+' : '−' }}{{ number_format($tx->amount) }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Adjust modal --}}
<div id="adjustModal" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="font-display font-bold text-2xl">Adjust credits</h2>
                <p class="text-sm text-ink-900/60 mt-1" id="ownerInfo"></p>
            </div>
            <button onclick="document.getElementById('adjustModal').classList.add('hidden')" class="text-2xl">×</button>
        </div>
        <form method="POST" id="adjustForm" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <label>
                    <input type="radio" name="action" value="credit" checked class="peer hidden">
                    <div class="text-center text-sm py-3 rounded-lg border-2 border-ink-900/15 cursor-pointer peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500">
                        + Add credits
                    </div>
                </label>
                <label>
                    <input type="radio" name="action" value="debit" class="peer hidden">
                    <div class="text-center text-sm py-3 rounded-lg border-2 border-ink-900/15 cursor-pointer peer-checked:bg-rose-500 peer-checked:text-white peer-checked:border-rose-500">
                        − Remove credits
                    </div>
                </label>
            </div>
            <div>
                <label class="text-xs font-semibold text-ink-900/60 uppercase">Amount</label>
                <input name="amount" type="number" min="1" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 text-2xl font-bold">
            </div>
            <div>
                <label class="text-xs font-semibold text-ink-900/60 uppercase">Notes (optional)</label>
                <input name="notes" placeholder="e.g. Promotional bonus, Refund for issue #123…" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <button class="w-full py-3 bg-ink-900 text-cream rounded-xl font-bold">Apply adjustment</button>
        </form>
    </div>
</div>

<script>
function openModal(userId, name, balance) {
    document.getElementById('ownerInfo').innerHTML = `<strong>${name}</strong> · Current: <strong>${balance}</strong> credits`;
    document.getElementById('adjustForm').action = `/admin/wallets/${userId}/adjust`;
    document.getElementById('adjustModal').classList.remove('hidden');
}
</script>

@endsection