@extends('layouts.dashboard')
@section('title', 'Invoices — Admin')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl mb-2"><i class="fa-solid fa-receipt fa-fw"></i> Invoices</h1>
        <p class="text-ink-900/60">All owner invoices — auto-generated (on package purchase) and manual (created by you).</p>
    </div>
    <a href="{{ route('admin.invoices.create') }}" class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">+ New Invoice</a>
</div>

@if(session('success'))
    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 px-4 py-3 rounded text-emerald-700 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-6 bg-rose-50 border-l-4 border-rose-500 px-4 py-3 rounded text-rose-700 text-sm">{{ session('error') }}</div>
@endif

<form method="GET" class="flex gap-3 mb-6">
    <input type="text" name="owner" value="{{ request('owner') }}" placeholder="Owner name / phone…" class="px-4 py-2.5 rounded-xl border border-ink-200 flex-1 max-w-xs">
    <select name="type" class="px-4 py-2.5 rounded-xl border border-ink-200">
        <option value="">All types</option>
        <option value="auto" @selected(request('type')==='auto')>Auto (Package purchase)</option>
        <option value="manual" @selected(request('type')==='manual')>Manual</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-900 text-white rounded-xl font-bold">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-ink-100 overflow-x-auto">
    <table class="w-full text-sm min-w-[900px]">
        <thead class="bg-ink-900/5 text-left text-xs uppercase text-ink-900/60">
            <tr>
                <th class="px-4 py-3">Invoice #</th>
                <th>Owner</th>
                <th>Title</th>
                <th>Amount</th>
                <th>Type</th>
                <th>WhatsApp</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
                <tr class="border-t border-ink-900/5">
                    <td class="px-4 py-3 font-mono text-xs">{{ $inv->invoice_number }}</td>
                    <td>
                        <div class="font-semibold">{{ $inv->owner->name ?? '—' }}</div>
                        <div class="text-xs text-ink-900/50">{{ $inv->owner->phone ?? '' }}</div>
                    </td>
                    <td>{{ $inv->title }}</td>
                    <td class="font-semibold">₹{{ number_format($inv->total_amount, 2) }}</td>
                    <td>
                        @if($inv->type === 'auto')
                            <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-bold">Auto</span>
                        @else
                            <span class="px-2 py-1 bg-amber-50 text-amber-600 rounded text-xs font-bold">Manual</span>
                        @endif
                    </td>
                    <td>
                        @if($inv->sent_via_whatsapp)
                            <span class="text-emerald-600 text-xs font-bold">✓ Sent</span>
                        @else
                            <span class="text-ink-900/40 text-xs">Not sent</span>
                        @endif
                    </td>
                    <td class="text-xs text-ink-900/60">{{ $inv->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.invoices.download', $inv) }}" class="text-blue-600 text-xs font-bold mr-3">Download</a>
                        <form method="POST" action="{{ route('admin.invoices.send', $inv) }}" class="inline">
                            @csrf
                            <button class="text-emerald-600 text-xs font-bold mr-3">Send WhatsApp</button>
                        </form>
                        <a href="{{ route('admin.invoices.edit', $inv) }}" class="text-ink-900 text-xs font-bold mr-3">Edit</a>
                        <form method="POST" action="{{ route('admin.invoices.destroy', $inv) }}" class="inline" onsubmit="return confirm('Delete this invoice?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 text-xs font-bold">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-8 text-center text-ink-900/50">No invoices yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $invoices->links() }}</div>

@endsection
