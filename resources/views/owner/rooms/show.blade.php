@extends('layouts.dashboard')
@section('title', 'Room ' . $room->room_number)
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rooms.index', ['property_id' => $room->property_id]) }}" class="text-coral-500 font-bold">← Back to rooms</a>
</div>

<div class="bg-white p-6 rounded-2xl border border-ink-100 mb-6">
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="font-display font-black text-3xl">Room {{ $room->room_number }}</h1>
            <div class="flex items-center gap-2 mt-2 flex-wrap">
                <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold capitalize">{{ $room->room_type }} ({{ $room->capacity }})</span>
                <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold capitalize">
                    @if($room->gender === 'male') 👨 Boys
                    @elseif($room->gender === 'female') 👩 Girls
                    @else 👥 Unisex @endif
                </span>
                @if($room->floor)<span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold">Floor: {{ $room->floor }}</span>@endif
                @if($room->status !== 'active')
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">{{ $room->status }}</span>
                @endif
            </div>
            <div class="flex items-center gap-2 mt-3 flex-wrap">
                @foreach($room->amenities_list as $a)
                    <span class="text-xs bg-ink-100 px-2 py-1 rounded-full font-bold">{{ $a }}</span>
                @endforeach
            </div>
            @if($room->monthly_rent > 0)
                <div class="mt-3 text-sm">
                    <span class="text-ink-500">Room Rent:</span>
                    <span class="font-bold text-coral-600">₹{{ number_format($room->monthly_rent, 0) }}/mo</span>
                </div>
            @endif
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('owner.rooms.edit', $room) }}" class="px-4 py-2 bg-ink-950 text-cream rounded-xl text-sm font-bold">✏ Edit</a>
            <form method="POST" action="{{ route('owner.rooms.destroy', $room) }}" id="deleteRoomForm" class="inline-block">
                @csrf @method('DELETE')
                <button type="button" onclick="confirmRoomDelete()" class="px-4 py-2 bg-rose-500 text-white rounded-xl text-sm font-bold">🗑</button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-blue-50 p-5 rounded-2xl border border-blue-200">
        <div class="text-xs text-blue-700 uppercase font-bold">Total Beds</div>
        <div class="font-display font-black text-3xl text-blue-700 mt-1">{{ $room->beds->count() }}</div>
    </div>
    <div class="bg-rose-50 p-5 rounded-2xl border border-rose-200">
        <div class="text-xs text-rose-700 uppercase font-bold">Occupied</div>
        <div class="font-display font-black text-3xl text-rose-700 mt-1">{{ $room->occupied_count }}</div>
    </div>
    <div class="bg-emerald-50 p-5 rounded-2xl border border-emerald-200">
        <div class="text-xs text-emerald-700 uppercase font-bold">Vacant</div>
        <div class="font-display font-black text-3xl text-emerald-700 mt-1">{{ $room->vacant_count }}</div>
    </div>
</div>

{{-- Add Bed --}}
<div class="bg-white p-6 rounded-2xl border border-ink-100 mb-6">
    <h2 class="font-display font-bold text-lg mb-4">➕ Add New Bed</h2>
    <form method="POST" action="{{ route('owner.rooms.beds.store', $room) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @csrf
        <input name="bed_number" required placeholder="Bed (A, B, 1, 2...)" class="px-4 py-3 rounded-xl border border-ink-200">
        <select name="bed_type" class="px-4 py-3 rounded-xl border border-ink-200">
            <option value="single">Single</option>
            <option value="bunk_top">Bunk Top</option>
            <option value="bunk_bottom">Bunk Bottom</option>
            <option value="double">Double</option>
        </select>
        <input name="monthly_rent" type="number" placeholder="Rent ₹" class="px-4 py-3 rounded-xl border border-ink-200">
        <button class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">+ Add Bed</button>
    </form>
</div>

{{-- Beds Grid --}}
<h2 class="font-display font-bold text-xl mb-4">🛏️ Beds ({{ $room->beds->count() }})</h2>

@if($room->beds->isEmpty())
    <div class="bg-white p-8 rounded-2xl border border-ink-100 text-center">
        <p class="text-ink-700">No beds added yet. Use the form above.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($room->beds as $bed)
            <div class="bg-white p-5 rounded-2xl border-2 
                @if($bed->status === 'occupied') border-rose-300
                @elseif($bed->status === 'vacant') border-emerald-300
                @elseif($bed->status === 'reserved') border-amber-300
                @else border-ink-200 @endif">
                
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-display font-black text-2xl">Bed {{ $bed->bed_number }}</h3>
                            <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold capitalize">{{ str_replace('_', ' ', $bed->bed_type) }}</span>
                        </div>
                        <span class="text-xs font-bold mt-1 inline-block
                            @if($bed->status === 'occupied') text-rose-700
                            @elseif($bed->status === 'vacant') text-emerald-700
                            @elseif($bed->status === 'reserved') text-amber-700
                            @else text-ink-700 @endif">
                            {{ $bed->status_label }}
                        </span>
                    </div>
                    @if($bed->monthly_rent > 0)
                        <div class="text-right">
                            <div class="text-xs text-ink-500">Rent</div>
                            <div class="font-bold text-coral-600">₹{{ number_format($bed->monthly_rent, 0) }}</div>
                        </div>
                    @endif
                </div>

                @if($bed->status === 'occupied' && $bed->tenant)
                    <div class="p-3 bg-rose-50 rounded-xl mb-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <div class="font-bold text-sm">👤 {{ $bed->tenant->name }}</div>
                                <div class="text-xs text-ink-700">📞 {{ $bed->tenant->phone }}</div>
                                @if($bed->occupied_since)
                                    <div class="text-xs text-ink-700">Since: {{ $bed->occupied_since->format('d M Y') }}</div>
                                @endif
                            </div>
                            <a href="{{ route('owner.tenants.show', $bed->tenant) }}" class="text-xs text-coral-500 font-bold">View →</a>
                        </div>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <form method="POST" action="{{ route('owner.rooms.beds.unassign', $bed) }}" onsubmit="return confirm('Unassign this bed?')" class="inline-block">
                            @csrf @method('PATCH')
                            <button class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold">↩ Unassign</button>
                        </form>
                        <form method="POST" action="{{ route('owner.rooms.beds.destroy', $bed) }}" onsubmit="return confirm('Delete bed?')" class="inline-block">
                            @csrf @method('DELETE')
                            <button class="px-3 py-2 bg-rose-500 text-white rounded-lg text-xs font-bold" disabled>🗑 (Unassign first)</button>
                        </form>
                    </div>

                @elseif($bed->status === 'vacant')
                    @if($activeTenants->count())
                        <form method="POST" action="{{ route('owner.rooms.beds.assign', $bed) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <select name="tenant_id" required class="flex-1 min-w-0 px-3 py-2 rounded-lg border border-ink-200 text-sm">
                                <option value="">— Select tenant —</option>
                                @foreach($activeTenants as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->phone }})</option>
                                @endforeach
                            </select>
                            <button class="px-4 py-2 bg-coral-500 hover:bg-coral-600 text-white rounded-lg text-sm font-bold">Assign</button>
                        </form>
                    @else
                        <p class="text-xs text-ink-500 mb-2">No unassigned tenants. <a href="{{ route('owner.tenants.create') }}" class="text-coral-500 font-bold">Add tenant →</a></p>
                    @endif

                    <div class="flex gap-2 mt-2 flex-wrap">
                        <form method="POST" action="{{ route('owner.rooms.beds.status', $bed) }}" class="inline-block">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="reserved">
                            <button class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold">⏳ Reserve</button>
                        </form>
                        <form method="POST" action="{{ route('owner.rooms.beds.status', $bed) }}" class="inline-block">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="maintenance">
                            <button class="px-3 py-2 bg-ink-500 hover:bg-ink-600 text-white rounded-lg text-xs font-bold">🔧 Maintenance</button>
                        </form>
                        <form method="POST" action="{{ route('owner.rooms.beds.destroy', $bed) }}" onsubmit="return confirm('Delete bed?')" class="inline-block">
                            @csrf @method('DELETE')
                            <button class="px-3 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-xs font-bold">🗑</button>
                        </form>
                    </div>

                @else
                    <form method="POST" action="{{ route('owner.rooms.beds.status', $bed) }}" class="inline-block">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="vacant">
                        <button class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold">✅ Mark Vacant</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endif

@endsection

<script>
function confirmRoomDelete() {
    const roomNumber = @json($room->room_number);
    const typed = prompt('This will move Room "' + roomNumber + '" to Trash (recoverable for 30 days).\n\nType the room number to confirm: ' + roomNumber);

    if (typed === null) return; // cancelled

    if (typed.trim() !== roomNumber) {
        alert('Room number did not match. Delete cancelled.');
        return;
    }

    document.getElementById('deleteRoomForm').submit();
}
</script>