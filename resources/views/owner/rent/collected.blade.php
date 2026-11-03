@extends('layouts.dashboard')
@section('title', 'Add collected rent')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">Add collected rent</h1>
        <p class="text-ink-900/60 mt-1">Rent you already took in cash or UPI: tick the tenants, check the amount and save. Bills are made for you.</p>
    </div>
    <a href="{{ route('owner.rent.index') }}" class="px-4 py-2.5 bg-white border border-ink-200 rounded-xl text-sm font-bold">← Back to Rent Collection</a>
</div>

@if(session('success'))<div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 font-semibold">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

{{-- 1. Which month and which PG --}}
<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap items-center">
    <label class="text-sm font-bold text-ink-700">Rent month</label>
    <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    @if($properties->count() > 1)
        <select name="property_id" onchange="this.form.submit()" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm font-semibold">
            <option value="">All PGs ({{ $properties->sum('count') }} tenants)</option>
            @foreach($properties as $p)
                <option value="{{ $p->id }}" @selected($propertyId === $p->id)>{{ $p->name }} ({{ $p->count }} tenants)</option>
            @endforeach
        </select>
    @endif
</form>

@if($tenants->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center text-ink-700">No active tenants found.</div>
@else
<form method="POST" action="{{ route('owner.rent.collected.store') }}" id="collectForm">
    @csrf
    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="property_id" value="{{ $propertyId ?: '' }}">

    {{-- 2. How and when it was collected --}}
    <div class="bg-white p-4 rounded-xl border border-ink-100 mb-4 flex gap-4 flex-wrap items-end">
        <div>
            <label class="block text-xs font-bold text-ink-700 uppercase mb-1">Collected on</label>
            <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-ink-700 uppercase mb-1">Paid by (for all ticked)</label>
            <select name="payment_method" id="defMethod" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm font-semibold">
                <option value="cash" @selected(old('payment_method') === 'cash')>💵 Cash</option>
                <option value="upi" @selected(old('payment_method') === 'upi')>📱 UPI</option>
                <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>🏦 Bank transfer</option>
                <option value="other" @selected(old('payment_method') === 'other')>Other</option>
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-700 pb-2.5"><input type="checkbox" name="notify" value="1" class="rounded"> Also send "payment received" on WhatsApp</label>
    </div>

    <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
        <div class="flex gap-2">
            <button type="button" id="pickAll" class="px-3 py-2 bg-white border border-ink-200 rounded-lg text-xs font-bold">Tick all unpaid</button>
            <button type="button" id="pickNone" class="px-3 py-2 bg-white border border-ink-200 rounded-lg text-xs font-bold">Clear</button>
        </div>
        <div class="text-sm font-bold text-ink-700"><span id="picked">0</span> ticked · ₹<span id="pickedTotal">0</span></div>
    </div>

    <div class="space-y-2">
        @foreach($tenants as $t)
            @php
                $bill = $bills->get($t->id);
                $state = 'none'; $due = null;
                if ($bill) {
                    if ($bill->trashed()) { $state = 'archived'; }
                    elseif ((float) $bill->due_amount <= 0.009) { $state = 'paid'; }
                    else { $state = $bill->status === 'partial' ? 'partial' : 'pending'; $due = (float) $bill->due_amount; }
                }
                $open = in_array($state, ['none', 'pending', 'partial'], true);
                $default = $due ?? (float) $t->monthly_rent;
            @endphp
            <div class="bg-white p-3 rounded-xl border {{ $open ? 'border-ink-100' : 'border-ink-100 opacity-60' }} flex items-center gap-3 flex-wrap">
                <div class="w-6">
                    @if($open)
                        <input type="checkbox" name="rows[{{ $t->id }}][on]" value="1" class="row-on w-5 h-5 rounded" data-row="{{ $t->id }}" @checked(old("rows.{$t->id}.on"))>
                    @endif
                </div>
                <div class="flex-1 min-w-[180px]">
                    <div class="font-bold">{{ $t->name }}</div>
                    <div class="text-xs text-ink-700 flex gap-3 flex-wrap">
                        @if($propertyId === 0 && $t->property?->name)<span class="font-semibold text-ink-900"><i class="fa-solid fa-building fa-fw"></i> {{ $t->property->name }}</span>@endif
                        @if($t->room_number)<span><i class="fa-solid fa-door-open fa-fw"></i> Room {{ $t->room_number }}@if($t->bed_number) · Bed {{ $t->bed_number }}@endif</span>@endif
                        <span><i class="fa-solid fa-mobile-screen fa-fw"></i> {{ $t->phone }}</span>
                        <span>Rent ₹{{ number_format($t->monthly_rent, 0) }}</span>
                    </div>
                </div>
                <div class="text-xs">
                    @if($state === 'paid')<span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-bold">✓ Already paid</span>
                    @elseif($state === 'archived')<span class="px-2 py-1 rounded-full bg-ink-100 text-ink-700 font-bold">In Bill Archive</span>
                    @elseif($state === 'partial')<span class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-bold">Part paid · due ₹{{ number_format($due, 0) }}</span>
                    @elseif($state === 'pending')<span class="px-2 py-1 rounded-full bg-amber-100 text-amber-700 font-bold">Bill due ₹{{ number_format($due, 0) }}</span>
                    @else<span class="px-2 py-1 rounded-full bg-ink-100 text-ink-700 font-bold">No bill yet</span>@endif
                </div>
                @if($open)
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold">₹</span>
                        <input type="number" step="0.01" min="0" name="rows[{{ $t->id }}][amount]" value="{{ old("rows.{$t->id}.amount", $default) }}" data-row="{{ $t->id }}" class="row-amt w-28 px-3 py-2 rounded-lg border border-ink-200 text-sm text-right">
                        <select name="rows[{{ $t->id }}][method]" class="px-2 py-2 rounded-lg border border-ink-200 text-xs">
                            <option value="">Same as above</option>
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank</option>
                        </select>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="sticky bottom-0 bg-cream/90 backdrop-blur py-4 mt-4">
        <button class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold shadow-lg shadow-coral-500/30">Save collected rent</button>
        <span class="text-xs text-ink-700 ml-3">A bill is made for tenants who have none. Tenants get no "new bill" message.</span>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('collectForm');
    var ons = [].slice.call(form.querySelectorAll('.row-on'));
    function sum() {
        var n = 0, total = 0;
        ons.forEach(function (c) {
            if (!c.checked) return;
            n++;
            var a = form.querySelector('.row-amt[data-row="' + c.getAttribute('data-row') + '"]');
            total += parseFloat(a && a.value) || 0;
        });
        document.getElementById('picked').textContent = n;
        document.getElementById('pickedTotal').textContent = Math.round(total).toLocaleString('en-IN');
    }
    ons.forEach(function (c) { c.addEventListener('change', sum); });
    [].slice.call(form.querySelectorAll('.row-amt')).forEach(function (a) {
        a.addEventListener('input', function () {
            var c = form.querySelector('.row-on[data-row="' + a.getAttribute('data-row') + '"]');
            if (c && !c.checked && parseFloat(a.value) > 0) { c.checked = true; }
            sum();
        });
    });
    document.getElementById('pickAll').addEventListener('click', function () { ons.forEach(function (c) { c.checked = true; }); sum(); });
    document.getElementById('pickNone').addEventListener('click', function () { ons.forEach(function (c) { c.checked = false; }); sum(); });
    form.addEventListener('submit', function (e) {
        var n = ons.filter(function (c) { return c.checked; }).length;
        if (!n) { e.preventDefault(); alert('Tick at least one tenant.'); return; }
        if (!confirm('Save ' + n + ' payment(s) as already collected?')) e.preventDefault();
    });
    sum();
})();
</script>
@endif
@endsection
