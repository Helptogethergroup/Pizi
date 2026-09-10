@extends('layouts.dashboard')
@section('title', 'Billing & GST Info')
@section('content')

<h1 class="font-display font-black text-3xl mb-2">🧾 Billing & GST Info</h1>
<p class="text-ink-900/60 mb-6">All fields below are optional — fill them in only if you're a GST-registered business. This ensures your invoices show the correct business name/GSTIN, and lets admin generate accurate bills for you.</p>

@if(session('success'))
    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 px-4 py-3 rounded text-emerald-700 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white p-6 rounded-2xl border border-ink-100 max-w-2xl">
    <form method="POST" action="{{ route('owner.billing.update') }}" class="space-y-5">
        @csrf

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">GST Number <span class="text-ink-900/40 normal-case">(optional)</span></label>
            <input name="gst_number" value="{{ old('gst_number', $user->gst_number) }}" placeholder="e.g. 09AARCP2012K1Z7" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 uppercase">
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Business / Firm Name <span class="text-ink-900/40 normal-case">(optional)</span></label>
            <input name="billing_business_name" value="{{ old('billing_business_name', $user->billing_business_name) }}" placeholder="e.g. Sunrise PG Services" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Business Address</label>
            <textarea name="billing_address" rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ old('billing_address', $user->billing_address) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">State</label>
                <input name="billing_state" value="{{ old('billing_state', $user->billing_state) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Pincode</label>
                <input name="billing_pincode" value="{{ old('billing_pincode', $user->billing_pincode) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>

        <button class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Save</button>
    </form>
</div>

@endsection
