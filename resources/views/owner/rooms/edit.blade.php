@extends('layouts.dashboard')
@section('title', 'Edit Room')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rooms.show', $room) }}" class="text-coral-500 font-bold">← Back</a>
    <h1 class="font-display font-black text-3xl mt-2">Edit Room {{ $room->room_number }}</h1>
</div>

<form method="POST" action="{{ route('owner.rooms.update', $room) }}" class="space-y-5 max-w-3xl">
    @csrf @method('PATCH')

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">Basic</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input name="room_number" required value="{{ $room->room_number }}" placeholder="Room Number" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="floor" value="{{ $room->floor }}" placeholder="Floor" class="px-4 py-3 rounded-xl border border-ink-200">
            <select name="room_type" required class="px-4 py-3 rounded-xl border border-ink-200">
                <option value="single" @selected($room->room_type==='single')>Single</option>
                <option value="double" @selected($room->room_type==='double')>Double</option>
                <option value="triple" @selected($room->room_type==='triple')>Triple</option>
                <option value="quad" @selected($room->room_type==='quad')>Quad</option>
                <option value="quint" @selected($room->room_type==='quint')>Quint</option>
                <option value="dorm" @selected($room->room_type==='dorm')>Dorm</option>
            </select>
            <select name="gender" required class="px-4 py-3 rounded-xl border border-ink-200">
                <option value="male" @selected($room->gender==='male')>👨 Boys</option>
                <option value="female" @selected($room->gender==='female')>👩 Girls</option>
                <option value="unisex" @selected($room->gender==='unisex')>👥 Unisex</option>
            </select>
            <input name="monthly_rent" type="number" value="{{ $room->monthly_rent }}" placeholder="Rent" class="px-4 py-3 rounded-xl border border-ink-200">
            <input name="security_deposit" type="number" value="{{ $room->security_deposit }}" placeholder="Deposit" class="px-4 py-3 rounded-xl border border-ink-200">
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">Amenities</h2>
        @php
            // Room ke abhi jitne amenities linked hain unki ID list
            $selectedAmenityIds = $room->amenities->pluck('id')->toArray();
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @forelse($amenities as $a)
                <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                    <input type="checkbox" name="amenities[]" value="{{ $a->id }}"
                        @checked(in_array($a->id, $selectedAmenityIds)) class="rounded">
                    <span>{{ $a->icon }} {{ $a->name }}</span>
                </label>
            @empty
                <p class="text-sm text-ink-500 col-span-full">Koi amenity nahi mili. Admin panel se amenities add karo pehle.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <label class="text-xs font-bold uppercase text-ink-500">Status</label>
        <select name="status" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            <option value="active" @selected($room->status==='active')>✓ Active</option>
            <option value="maintenance" @selected($room->status==='maintenance')>🔧 Maintenance</option>
            <option value="closed" @selected($room->status==='closed')>❌ Closed</option>
        </select>
        <textarea name="notes" rows="2" placeholder="Notes" class="w-full mt-3 px-4 py-3 rounded-xl border border-ink-200">{{ $room->notes }}</textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 text-white rounded-xl font-bold text-lg">Update Room</button>
        <a href="{{ route('owner.rooms.show', $room) }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

@endsection