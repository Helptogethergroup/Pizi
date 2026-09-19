@extends('layouts.tenant')
@section('title', 'Pay Rent')
@section('content')

<a href="{{ route('tenant.rent.history') }}" class="text-coral-600 text-sm font-bold">← Back to Rent</a>
<h1 class="font-display font-black text-3xl text-ink-950 mt-2">Pay Rent</h1>

<div class="mt-6 max-w-2xl bg-white rounded-2xl border border-ink-900/10 p-6">
    <h2 class="font-display font-bold text-xl mb-4">Bill Details</h2>
    <div class="space-y-2 text-sm">
        <div class="flex justify-between"><span>Month:</span> <strong>{{ \Carbon\Carbon::parse($bill->month ?? $bill->due_date)->format('M Y') }}</strong></div>
        <div class="flex justify-between"><span>Amount Due:</span> <strong>₹{{ number_format($bill->due_amount ?? $bill->amount_due ?? 0) }}</strong></div>
        <div class="flex justify-between"><span>Due Date:</span> <strong>{{ \Carbon\Carbon::parse($bill->due_date)->format('d M Y') }}</strong></div>
    </div>

    <div class="mt-6 p-5 bg-amber-50 border border-amber-200 rounded-xl text-sm">
        <i class="fa-solid fa-lightbulb fa-fw"></i> Online payment integration (Razorpay) abhi setup ho raha hai. Tab tak owner ko cash/UPI me pay kar do aur receipt collect karo.
    </div>

    <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '918006680092') }}?text={{ urlencode('Hi, I want to pay rent for ' . \Carbon\Carbon::parse($bill->month ?? $bill->due_date)->format('M Y')) }}" target="_blank" class="block mt-4 text-center py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold">
        <i class="fa-solid fa-comment-dots fa-fw"></i> Contact Owner on WhatsApp
    </a>
</div>

@endsection