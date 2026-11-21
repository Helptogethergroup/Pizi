@extends('layouts.dashboard')
@section('title', 'Edit bill #' . $bill->bill_number)
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rent.show', $bill) }}" class="text-coral-500 font-bold">← Back to the bill</a>
</div>

<div class="max-w-3xl">
    <h1 class="font-display font-black text-3xl">Edit bill — {{ $bill->month_label }}</h1>
    <p class="text-ink-900/60 mt-1">
        {{ $bill->tenant?->name }} · {{ $bill->property?->name }}, Room {{ $bill->tenant?->room_number }}
        · Bill <span class="font-mono">{{ $bill->bill_number }}</span>
    </p>

    @if($errors->any())
        <div class="mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if($bill->paid_amount > 0)
        <div class="mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            ₹{{ number_format($bill->paid_amount, 0) }} is already paid on this bill. The total cannot go below that amount.
        </div>
    @endif

    <form method="POST" action="{{ route('owner.rent.update', $bill) }}" class="mt-6 bg-white p-6 rounded-2xl border border-ink-100 space-y-5">
        @csrf
        @method('PATCH')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach([
                ['rent_amount', 'Rent (₹)'],
                ['electricity', 'Electricity (₹)'],
                ['water', 'Water (₹)'],
                ['maintenance', 'Maintenance (₹)'],
                ['food_charges', 'Food charges (₹)'],
                ['other_charges', 'Other charges (₹)'],
                ['late_fee', 'Late fee (₹)'],
                ['discount', 'Discount (₹)'],
            ] as [$field, $label])
                <div>
                    <label class="text-xs font-bold uppercase text-ink-500">{{ $label }}</label>
                    <input name="{{ $field }}" type="number" step="0.01" min="0" value="{{ old($field, (float) $bill->{$field} == 0 ? '' : (float) $bill->{$field}) }}"
                           @if($field === 'rent_amount') required @endif
                           class="bill-amount w-full mt-1 px-4 py-3 rounded-xl border border-ink-200" data-sign="{{ $field === 'discount' ? -1 : 1 }}" placeholder="0">
                </div>
            @endforeach
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-500">Name of the other charge (shown on the bill)</label>
                <input name="other_charges_label" value="{{ old('other_charges_label', $bill->other_charges_label) }}" maxlength="100" placeholder="e.g. Laundry" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Due date *</label>
                <input name="due_date" type="date" required value="{{ old('due_date', $bill->due_date->format('Y-m-d')) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div class="flex items-end">
                <div class="w-full p-3 rounded-xl bg-cream border border-ink-100">
                    <div class="text-xs text-ink-500 uppercase font-bold">New total</div>
                    <div id="billTotal" class="font-display font-black text-2xl text-coral-600">₹0</div>
                </div>
            </div>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-500">Notes</label>
            <textarea name="notes" rows="3" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200" placeholder="Notes (optional)">{{ old('notes', $bill->notes) }}</textarea>
        </div>

        <p class="text-xs text-ink-500">The month of the bill cannot be changed. If it is wrong, delete this bill and make a new one.</p>

        <div class="flex gap-3">
            <button class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Save changes</button>
            <a href="{{ route('owner.rent.show', $bill) }}" class="px-6 py-3 bg-white border border-ink-200 rounded-xl font-bold">Cancel</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const inputs = document.querySelectorAll('.bill-amount');
        const out = document.getElementById('billTotal');
        function recalc() {
            let t = 0;
            inputs.forEach(i => { t += (parseFloat(i.value) || 0) * parseInt(i.dataset.sign, 10); });
            out.textContent = '₹' + Math.round(t).toLocaleString('en-IN');
        }
        inputs.forEach(i => i.addEventListener('input', recalc));
        recalc();
    })();
</script>

@endsection
