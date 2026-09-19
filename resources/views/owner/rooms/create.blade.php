@extends('layouts.dashboard')
@section('title', 'Add Room')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rooms.index') }}" class="text-coral-500 font-bold">← Back</a>
    <h1 class="font-display font-black text-3xl mt-2">Add Room</h1>
</div>

<form method="POST" action="{{ route('owner.rooms.store') }}" class="space-y-5 max-w-3xl">
    @csrf

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-house fa-fw"></i> Basic Info</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-500">Property *</label>
                <select name="property_id" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="">— Select —</option>
                    @foreach($properties as $p)
                        <option value="{{ $p->id }}" @selected($selectedPropertyId == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Room Number *</label>
                <input name="room_number" required placeholder="e.g. 101, A-1, G-12" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Floor</label>
                <input name="floor" placeholder="e.g. Ground, 1st, 2nd" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Room Type *</label>
                <select name="room_type" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="single">Single (1 bed)</option>
                    <option value="double" selected>Double (2 beds)</option>
                    <option value="triple">Triple (3 beds)</option>
                    <option value="quad">Quad (4 beds)</option>
                    <option value="quint">Quint (5 beds)</option>
                    <option value="dorm">Dorm (8 beds)</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Gender *</label>
                <select name="gender" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="male">Boys</option>
                    <option value="female">Girls</option>
                    <option value="unisex">Unisex</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-sack-dollar fa-fw"></i> Pricing</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Monthly Rent ₹</label>
                <input name="monthly_rent" type="number" min="0" placeholder="e.g. 16000 (total room)" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                <p class="text-xs text-ink-500 mt-1">Per-bed rent will be auto-calculated.</p>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Security Deposit ₹</label>
                <input name="security_deposit" type="number" min="0" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-wand-magic-sparkles fa-fw"></i> Amenities</h2>
        <p class="text-xs text-ink-500 mb-3">Is room mein kya-kya available hai, select kar.</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @forelse($amenities as $a)
                <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                    <input type="checkbox" name="amenities[]" value="{{ $a->id }}" class="rounded">
                    <span>{{ $a->icon }} {{ $a->name }}</span>
                </label>
            @empty
                <p class="text-sm text-ink-500 col-span-full">Koi amenity nahi mili. Admin panel se amenities add karo pehle.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-emerald-50 p-6 rounded-2xl border border-emerald-200">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="auto_create_beds" value="1" checked class="mt-1 rounded w-5 h-5">
            <div>
                <div class="font-bold text-emerald-900"><i class="fa-solid fa-wand-magic-sparkles fa-fw"></i> Auto-create beds</div>
                <p class="text-sm text-emerald-800 mt-1">Automatically create beds based on room type (A, B, C, D...). You can edit them later.</p>
            </div>
        </label>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <label class="text-xs font-bold uppercase text-ink-500">Notes (optional)</label>
        <textarea name="notes" rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200"></textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg shadow-lg shadow-coral-500/30">✓ Create Room</button>
        <a href="{{ route('owner.rooms.index') }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

@endsection