@extends('layouts.dashboard')
@section('title', 'New Invoice — Admin')
@section('content')

<h1 class="font-display font-black text-3xl mb-6"><i class="fa-solid fa-receipt fa-fw"></i> New Manual Invoice</h1>

<div class="bg-white p-6 rounded-2xl border border-ink-100 max-w-2xl">
    <form method="POST" action="{{ route('admin.invoices.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Owner</label>
            <select name="owner_id" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                <option value="">Select owner…</option>
                @foreach($owners as $owner)
                    <option value="{{ $owner->id }}">{{ $owner->name }} — {{ $owner->phone }}{{ $owner->gst_number ? ' (GST: '.$owner->gst_number.')' : '' }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Title / Description</label>
            <input name="title" required placeholder="e.g. Premium Listing Package" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Total Amount (₹, GST-inclusive)</label>
                <input name="total_amount" type="number" step="0.01" min="1" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">GST Rate (%)</label>
                <input name="gst_rate" type="number" step="0.01" value="18" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Notes (optional)</label>
            <textarea name="notes" rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200"></textarea>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="send_whatsapp" value="1" checked class="w-4 h-4">
            <span class="text-sm">Send via WhatsApp immediately</span>
        </label>

        <button class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Create Invoice</button>
    </form>
</div>

@endsection
