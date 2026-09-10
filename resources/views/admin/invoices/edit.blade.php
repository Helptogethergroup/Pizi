@extends('layouts.dashboard')
@section('title', 'Edit Invoice — Admin')
@section('content')

<h1 class="font-display font-black text-3xl mb-6">🧾 Edit Invoice {{ $invoice->invoice_number }}</h1>

<div class="bg-white p-6 rounded-2xl border border-ink-100 max-w-2xl">
    <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Owner</label>
            <div class="mt-1 px-4 py-3 rounded-xl bg-ink-900/5 text-ink-900/70">{{ $invoice->owner->name }} — {{ $invoice->owner->phone }}</div>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Title / Description</label>
            <input name="title" value="{{ $invoice->title }}" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Total Amount (₹, GST-inclusive)</label>
                <input name="total_amount" type="number" step="0.01" min="1" value="{{ $invoice->total_amount }}" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">GST Rate (%)</label>
                <input name="gst_rate" type="number" step="0.01" value="{{ $invoice->gst_rate }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Notes (optional)</label>
            <textarea name="notes" rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ $invoice->notes }}</textarea>
        </div>

        <button class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Save Changes</button>
        <a href="{{ route('admin.invoices.index') }}" class="ml-3 text-ink-900/60 font-bold">Cancel</a>
    </form>
</div>

@endsection
