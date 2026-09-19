@extends('layouts.dashboard')
@section('title', 'Edit Agreement')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.agreements.show', $agreement) }}" class="text-coral-500 font-bold">← Back</a>
    <h1 class="font-display font-black text-3xl mt-2">Edit Agreement {{ $agreement->agreement_number }}</h1>
</div>

<form method="POST" action="{{ route('owner.agreements.update', $agreement) }}" class="space-y-5 max-w-3xl">
    @csrf @method('PATCH')

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-sack-dollar fa-fw"></i> Financial</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input name="monthly_rent" type="number" required value="{{ $agreement->monthly_rent }}" placeholder="Monthly Rent" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="security_deposit" type="number" required value="{{ $agreement->security_deposit }}" placeholder="Deposit" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="maintenance_fee" type="number" value="{{ $agreement->maintenance_fee }}" placeholder="Maintenance" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="rent_due_day" type="number" min="1" max="31" value="{{ $agreement->rent_due_day }}" placeholder="Due Day" class="px-4 py-3 rounded-xl border border-ink-200">
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-2">
            <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                <input type="checkbox" name="electricity_included" value="1" @checked($agreement->electricity_included) class="rounded">
                <span class="text-sm"><i class="fa-solid fa-bolt fa-fw"></i> Electricity</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                <input type="checkbox" name="water_included" value="1" @checked($agreement->water_included) class="rounded">
                <span class="text-sm"><i class="fa-solid fa-droplet fa-fw"></i> Water</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                <input type="checkbox" name="food_included" value="1" @checked($agreement->food_included) class="rounded">
                <span class="text-sm"><i class="fa-solid fa-utensils fa-fw"></i> Food</span>
            </label>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-calendar-days fa-fw"></i> Duration</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input name="start_date" type="date" required value="{{ $agreement->start_date->format('Y-m-d') }}" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="end_date" type="date" required value="{{ $agreement->end_date->format('Y-m-d') }}" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="lock_in_months" type="number" value="{{ $agreement->lock_in_months }}" placeholder="Lock-in Months" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="notice_period_days" type="number" value="{{ $agreement->notice_period_days }}" placeholder="Notice Days" class="px-4 py-3 rounded-xl border border-ink-200">
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-scroll fa-fw"></i> Terms</h2>
        <textarea name="additional_terms" rows="3" placeholder="Additional terms" class="w-full mb-3 px-4 py-3 rounded-xl border border-ink-200">{{ $agreement->additional_terms }}</textarea>
        <textarea name="house_rules" rows="3" placeholder="House rules" class="w-full px-4 py-3 rounded-xl border border-ink-200">{{ $agreement->house_rules }}</textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 text-white rounded-xl font-bold text-lg">Update</button>
        <a href="{{ route('owner.agreements.show', $agreement) }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

@endsection