@extends('layouts.tenant')
@section('title', 'Rent History')
@section('content')

<h1 class="font-display font-black text-3xl text-ink-950">Rent History</h1>
<p class="text-ink-900/60 mt-1">Aapke saare rent bills aur payments</p>

<div class="mt-6 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    @if($bills->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-cream border-b border-ink-900/10">
                    <tr>
                        <th class="text-left px-4 py-3 font-bold text-xs uppercase text-ink-900/60">Month</th>
                        <th class="text-left px-4 py-3 font-bold text-xs uppercase text-ink-900/60">Amount</th>
                        <th class="text-left px-4 py-3 font-bold text-xs uppercase text-ink-900/60">Due Date</th>
                        <th class="text-left px-4 py-3 font-bold text-xs uppercase text-ink-900/60">Status</th>
                        <th class="text-left px-4 py-3 font-bold text-xs uppercase text-ink-900/60">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bills as $bill)
                        <tr class="border-b border-ink-900/5 hover:bg-cream/50">
                            <td class="px-4 py-3 font-bold">{{ \Carbon\Carbon::parse($bill->month ?? $bill->due_date)->format('M Y') }}</td>
                            <td class="px-4 py-3">₹{{ number_format($bill->total_amount ?? $bill->amount_due ?? 0) }}</td>
                            <td class="px-4 py-3 text-ink-900/70">{{ \Carbon\Carbon::parse($bill->due_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                                    {{ $bill->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $bill->status === 'pending' || $bill->status === 'unpaid' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $bill->status === 'partial' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $bill->status === 'overdue' ? 'bg-rose-100 text-rose-700' : '' }}
                                ">{{ $bill->status }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($bill->status !== 'paid')
                                    <a href="{{ route('tenant.pay-rent', $bill->id) }}" class="px-3 py-1.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg text-xs font-bold">Pay Now</a>
                                @else
                                    <span class="text-emerald-600 font-bold text-xs">✓ Paid</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-ink-900/5">
            {{ $bills->links() }}
        </div>
    @else
        <p class="text-center py-12 text-ink-900/50">No rent bills yet.</p>
    @endif
</div>

@endsection