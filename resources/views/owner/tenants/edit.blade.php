@extends('layouts.dashboard')
@section('title', 'Edit Tenant')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.tenants.show', $tenant) }}" class="text-coral-500 font-bold">← Back</a>
    <h1 class="font-display font-black text-3xl mt-2">Edit: {{ $tenant->name }}</h1>
</div>

<form method="POST" action="{{ route('owner.tenants.update', $tenant) }}" class="space-y-5 max-w-4xl">
    @csrf @method('PATCH')

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-house fa-fw"></i> Property</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <select name="property_id" required class="w-full px-4 py-3 rounded-xl border border-ink-200">
                    @foreach($properties as $p)
                        <option value="{{ $p->id }}" @selected($tenant->property_id == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <input name="room_number" value="{{ $tenant->room_number }}" placeholder="Room" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="bed_number" value="{{ $tenant->bed_number }}" placeholder="Bed" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="monthly_rent" type="number" value="{{ $tenant->monthly_rent }}" placeholder="Rent" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="security_deposit" type="number" value="{{ $tenant->security_deposit }}" placeholder="Deposit" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="move_in_date" type="date" value="{{ $tenant->move_in_date?->format('Y-m-d') }}" class="px-4 py-3 rounded-xl border border-ink-200">
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-user fa-fw"></i> Personal</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input name="name" required value="{{ $tenant->name }}" placeholder="Name" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="phone" required value="{{ $tenant->phone }}" placeholder="Phone" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="email" type="email" value="{{ $tenant->email }}" placeholder="Email" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="dob" type="date" value="{{ $tenant->dob?->format('Y-m-d') }}" class="px-4 py-3 rounded-xl border border-ink-200">
            <select name="gender" class="px-4 py-3 rounded-xl border border-ink-200">
                <option value="">Gender</option>
                <option value="male" @selected($tenant->gender==='male')>Male</option>
                <option value="female" @selected($tenant->gender==='female')>Female</option>
                <option value="other" @selected($tenant->gender==='other')>Other</option>
            </select>
            <input name="occupation" value="{{ $tenant->occupation }}" placeholder="Occupation" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="company_college" value="{{ $tenant->company_college }}" placeholder="Company/College" class="md:col-span-2 px-4 py-3 rounded-xl border border-ink-200">
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-location-dot fa-fw"></i> Address</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <textarea name="address_line" rows="2" placeholder="Address" class="md:col-span-2 px-4 py-3 rounded-xl border border-ink-200">{{ $tenant->address_line }}</textarea>
            <input name="city" value="{{ $tenant->city }}" placeholder="City" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="state" value="{{ $tenant->state }}" placeholder="State" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="pincode" value="{{ $tenant->pincode }}" placeholder="Pincode" class="px-4 py-3 rounded-xl border border-ink-200">
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Emergency</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input name="emergency_name" value="{{ $tenant->emergency_name }}" placeholder="Name" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="emergency_phone" value="{{ $tenant->emergency_phone }}" placeholder="Phone" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="emergency_relation" value="{{ $tenant->emergency_relation }}" placeholder="Relation" class="px-4 py-3 rounded-xl border border-ink-200">
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg">Update Tenant</button>
        <a href="{{ route('owner.tenants.show', $tenant) }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

@endsection