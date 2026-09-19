@extends('layouts.dashboard')
@section('title', 'My Invoices')
@section('content')

<h1 class="font-display font-black text-3xl mb-2"><i class="fa-solid fa-receipt fa-fw"></i> My Invoices</h1>
<p class="text-ink-900/60 mb-6">Aapke package-purchases ki saari invoices yahan milengi.</p>

<div class="bg-white rounded-2xl border border-ink-100 overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-ink-900/5 text-left text-xs uppercase text-ink-900/60">
            <tr>
                <th class="px-4 py-3">Invoice #</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
                <tr class="border-t border-ink-900/5">
                    <td class="px-4 py-3 font-mono text-xs">{{ $inv->invoice_number }}</td>
                    <td>{{ $inv->title }}</td>
                    <td class="font-semibold">₹{{ number_format($inv->total_amount, 2) }}</td>
                    <td class="text-xs text-ink-900/60">{{ $inv->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('owner.invoices.download', $inv) }}" class="text-blue-600 text-xs font-bold">Download PDF</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-8 text-center text-ink-900/50">No invoices yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $invoices->links() }}</div>

@endsection
