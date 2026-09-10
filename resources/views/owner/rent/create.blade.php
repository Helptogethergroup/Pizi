@extends('layouts.dashboard')
@section('title', 'New Rent Bill')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rent.index') }}" class="text-coral-500 font-bold">← Back</a>
    <h1 class="font-display font-black text-3xl mt-2">Create Rent Bill</h1>
</div>

<form method="POST" action="{{ route('owner.rent.store') }}" class="space-y-5 max-w-3xl">
    @csrf

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">Tenant & Period</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-500">Tenant *</label>
                <select name="tenant_id" required onchange="autoFillRent(this)" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="">— Select tenant —</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" data-rent="{{ $t->monthly_rent }}" @selected($selectedTenant && $selectedTenant->id == $t->id)>
                            {{ $t->name }} — {{ $t->property?->name }} (₹{{ number_format($t->monthly_rent) }}/mo)
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Month *</label>
                <input name="month" type="month" required value="{{ now()->format('Y-m') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Due Date *</label>
                <input name="due_date" type="date" required value="{{ now()->day(5)->format('Y-m-d') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">Charges</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Rent *</label>
                <input id="rentInput" name="rent_amount" type="number" step="0.01" required value="{{ $selectedTenant?->monthly_rent ?? '' }}" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Electricity</label>
                <input name="electricity" type="number" step="0.01" value="0" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Water</label>
                <input name="water" type="number" step="0.01" value="0" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Maintenance</label>
                <input name="maintenance" type="number" step="0.01" value="0" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Food</label>
                <input name="food_charges" type="number" step="0.01" value="0" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Other</label>
                <input name="other_charges" type="number" step="0.01" value="0" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div class="col-span-2 md:col-span-3">
                <label class="text-xs font-bold uppercase text-ink-500">Other Charges Label</label>
                <input name="other_charges_label" placeholder="e.g. Cleaning fee, Parking" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-amber-700">Late Fee</label>
                <input name="late_fee" type="number" step="0.01" value="0" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-amber-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-emerald-700">Discount (-)</label>
                <input name="discount" type="number" step="0.01" value="0" oninput="calculateTotal()" class="w-full mt-1 px-4 py-3 rounded-xl border border-emerald-200">
            </div>
        </div>

        <div class="mt-5 p-4 bg-gradient-to-br from-ink-950 to-ink-900 rounded-xl text-cream">
            <div class="text-xs uppercase font-bold opacity-70">Total Amount</div>
            <div id="totalDisplay" class="font-display font-black text-3xl mt-1">₹0</div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <label class="text-xs font-bold uppercase text-ink-500">Notes (Optional)</label>
        <textarea name="notes" rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200"></textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg shadow-lg shadow-coral-500/30">✓ Create Bill</button>
        <a href="{{ route('owner.rent.index') }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

<script>
function autoFillRent(select) {
    const rent = select.options[select.selectedIndex].dataset.rent;
    if (rent) {
        document.getElementById('rentInput').value = rent;
        calculateTotal();
    }
}

function calculateTotal() {
    const get = name => parseFloat(document.querySelector(`[name="${name}"]`).value) || 0;
    const total = get('rent_amount') + get('electricity') + get('water') 
                + get('maintenance') + get('food_charges') + get('other_charges')
                + get('late_fee') - get('discount');
    document.getElementById('totalDisplay').textContent = '₹' + total.toLocaleString('en-IN', {maximumFractionDigits: 2});
}

calculateTotal();
</script>

@endsection