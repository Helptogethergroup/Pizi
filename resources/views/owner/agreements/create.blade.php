@extends('layouts.dashboard')
@section('title', 'New Agreement')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.agreements.index') }}" class="text-coral-500 font-bold">← Back</a>
    <h1 class="font-display font-black text-3xl mt-2">Create Rent Agreement</h1>
</div>

<form method="POST" action="{{ route('owner.agreements.store') }}" class="space-y-5 max-w-3xl">
    @csrf

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">👤 Tenant</h2>
        <select name="tenant_id" required class="w-full px-4 py-3 rounded-xl border border-ink-200">
            <option value="">— Select tenant —</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" 
                    data-rent="{{ $t->monthly_rent }}" 
                    data-deposit="{{ $t->security_deposit }}"
                    data-movein="{{ $t->move_in_date?->format('Y-m-d') }}"
                    @selected($selectedTenant && $selectedTenant->id == $t->id)>
                    {{ $t->name }} ({{ $t->property?->name }}{{ $t->room_number ? ' - Room '.$t->room_number : '' }})
                </option>
            @endforeach
        </select>
        <p class="text-xs text-ink-500 mt-2">Tenant details (room, rent) will auto-fill from tenant profile.</p>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">💰 Financial Terms</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Monthly Rent *</label>
                <input name="monthly_rent" id="rentInput" type="number" required step="0.01" value="{{ $selectedTenant?->monthly_rent ?? '' }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Security Deposit *</label>
                <input name="security_deposit" id="depositInput" type="number" required step="0.01" value="{{ $selectedTenant?->security_deposit ?? '' }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Maintenance Fee</label>
                <input name="maintenance_fee" type="number" step="0.01" value="0" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Rent Due Day *</label>
                <input name="rent_due_day" type="number" min="1" max="31" value="5" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                <p class="text-xs text-ink-500 mt-1">Day of each month</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-2">
            <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                <input type="checkbox" name="electricity_included" value="1" class="rounded">
                <span class="text-sm">⚡ Electricity included</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                <input type="checkbox" name="water_included" value="1" checked class="rounded">
                <span class="text-sm">💧 Water included</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                <input type="checkbox" name="food_included" value="1" class="rounded">
                <span class="text-sm">🍱 Food included</span>
            </label>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">📅 Duration</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Start Date *</label>
                <input name="start_date" id="startDate" type="date" required value="{{ $selectedTenant?->move_in_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200" onchange="updateEndDate()">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">End Date *</label>
                <input name="end_date" id="endDate" type="date" required value="{{ now()->addMonths(11)->format('Y-m-d') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                <p class="text-xs text-ink-500 mt-1">Default: 11 months (standard)</p>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Lock-in Period (months)</label>
                <input name="lock_in_months" type="number" min="0" value="3" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Notice Period (days)</label>
                <input name="notice_period_days" type="number" min="0" value="30" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">📜 Agreement Template</h2>
        <select name="terms_template" class="w-full px-4 py-3 rounded-xl border border-ink-200 mb-3">
            <option value="delhi_standard">Delhi Standard Template (recommended)</option>
            <option value="custom">Custom Terms</option>
        </select>

        <div class="space-y-3">
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Additional Terms (optional)</label>
                <textarea name="additional_terms" rows="3" placeholder="Any custom clauses to add..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200"></textarea>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">House Rules (optional)</label>
                <textarea name="house_rules" rows="3" placeholder="No smoking, gates close at 10 PM, etc." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200"></textarea>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg shadow-lg shadow-coral-500/30">✓ Create Agreement</button>
        <a href="{{ route('owner.agreements.index') }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

<script>
document.querySelector('[name="tenant_id"]').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.dataset.rent) document.getElementById('rentInput').value = opt.dataset.rent;
    if (opt.dataset.deposit) document.getElementById('depositInput').value = opt.dataset.deposit;
    if (opt.dataset.movein) {
        document.getElementById('startDate').value = opt.dataset.movein;
        updateEndDate();
    }
});

function updateEndDate() {
    const start = document.getElementById('startDate').value;
    if (start) {
        const end = new Date(start);
        end.setMonth(end.getMonth() + 11);
        document.getElementById('endDate').value = end.toISOString().split('T')[0];
    }
}
</script>

@endsection