{{-- File: resources/views/admin/tokens.blade.php  (aur owner ke liye copy: resources/views/owner/tokens.blade.php)
     @extends apne admin/owner dashboard layout jaisa rakho (layouts.dashboard) --}}
@extends('layouts.dashboard')
@section('title', 'Token Payments')
@section('content')

<div class="max-w-6xl mx-auto space-y-6">

    <div>
        <h1 class="font-display font-black text-2xl lg:text-3xl text-ink-950">Token Payments</h1>
        <p class="text-ink-900/60 mt-1">All token amounts collected — online & cash.</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
            <div class="text-xs text-ink-900/50 uppercase font-bold">Total Collected</div>
            <div class="font-display font-black text-2xl text-ink-950 mt-1">₹{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
            <div class="text-xs text-ink-900/50 uppercase font-bold">Online</div>
            <div class="font-display font-black text-2xl text-emerald-600 mt-1">₹{{ number_format($stats['online']) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
            <div class="text-xs text-ink-900/50 uppercase font-bold">Cash</div>
            <div class="font-display font-black text-2xl text-amber-600 mt-1">₹{{ number_format($stats['cash']) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
            <div class="text-xs text-ink-900/50 uppercase font-bold">Pending</div>
            <div class="font-display font-black text-2xl text-coral-600 mt-1">{{ $stats['pending'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-ink-900/50 border-b border-ink-900/10">
                    <th class="py-2">Tenant</th><th class="py-2">Property</th><th class="py-2">Amount</th>
                    <th class="py-2">Mode</th><th class="py-2">Status</th><th class="py-2">Collected By</th><th class="py-2">Paid At</th>
                </tr></thead>
                <tbody>
                    @forelse($payments as $p)
                        <tr class="border-b border-ink-900/5">
                            <td class="py-2.5">{{ $p->tenant_name }}<div class="text-xs text-ink-900/50">{{ $p->tenant_phone }}</div></td>
                            <td class="py-2.5 text-ink-900/70">{{ $p->property_name ?? '-' }}</td>
                            <td class="py-2.5 font-bold">₹{{ number_format($p->amount) }}</td>
                            <td class="py-2.5">{{ $p->payment_method ? ucfirst($p->payment_method) : '-' }}</td>
                            <td class="py-2.5">
                                @if($p->status === 'paid')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">✓ Paid</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-coral-100 text-coral-700">Pending</span>
                                @endif
                            </td>
                            <td class="py-2.5 text-ink-900/60">{{ $p->collector_name ?? '-' }}</td>
                            <td class="py-2.5 text-ink-900/60">{{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d M, h:i A') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-ink-900/50">No token payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
