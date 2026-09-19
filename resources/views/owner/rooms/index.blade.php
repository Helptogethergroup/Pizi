@extends('layouts.dashboard')
@section('title', 'Rooms & Beds')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl"><i class="fa-solid fa-bed fa-fw"></i> Rooms & Beds</h1>
        <p class="text-ink-900/60 mt-1">Manage room inventory and bed allocations</p>
    </div>
    <div class="flex gap-2">
        <button id="toggleSelectBtn" onclick="toggleSelectMode()" class="px-5 py-2.5 bg-white border border-ink-200 hover:bg-cream text-ink-700 rounded-xl text-sm font-bold"><i class="fa-solid fa-square-check fa-fw"></i> Select</button>
        <a href="{{ route('owner.rooms.trash') }}" class="px-5 py-2.5 bg-white border border-ink-200 hover:bg-cream text-ink-700 rounded-xl text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Trash</a>
        <a href="{{ route('owner.rooms.create', ['property_id' => $selectedProperty?->id]) }}" class="px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-coral-500/30">+ Add Room</a>
    </div>
</div>

@if($properties->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-house fa-fw"></i></div>
        <p class="text-ink-700 mb-4">Add a property first to manage rooms.</p>
        <a href="{{ route('owner.properties.create') }}" class="inline-block px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ Add Property</a>
    </div>
@else

{{-- Property Selector --}}
<div class="bg-white rounded-xl border border-ink-100 p-2 mb-4 flex gap-1 overflow-x-auto scrollbar-hide">
    @foreach($properties as $p)
        <a href="{{ route('owner.rooms.index', ['property_id' => $p->id]) }}"
           class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap {{ $selectedProperty?->id == $p->id ? 'bg-coral-500 text-white' : 'text-ink-700 hover:bg-cream' }}">
            <i class="fa-solid fa-house fa-fw"></i> {{ $p->name }} ({{ $p->rooms_count }} rooms)
        </a>
    @endforeach
</div>

@if($selectedProperty)

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-ink-100">
        <div class="text-xs text-ink-500 uppercase font-bold">Total Rooms</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total_rooms'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-blue-200">
        <div class="text-xs text-blue-700 uppercase font-bold">Total Beds</div>
        <div class="font-display font-black text-3xl text-blue-700 mt-1">{{ $stats['total_beds'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-rose-200">
        <div class="text-xs text-rose-700 uppercase font-bold">Occupied</div>
        <div class="font-display font-black text-3xl text-rose-700 mt-1">{{ $stats['occupied'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-emerald-200">
        <div class="text-xs text-emerald-700 uppercase font-bold">Vacant</div>
        <div class="font-display font-black text-3xl text-emerald-700 mt-1">{{ $stats['vacant'] }}</div>
    </div>
</div>

@if($rooms->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-bed fa-fw"></i></div>
        <p class="text-ink-700 mb-4">No rooms in this property yet.</p>
        <a href="{{ route('owner.rooms.create', ['property_id' => $selectedProperty->id]) }}" class="inline-block px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ Add First Room</a>
    </div>
@else
    <form id="bulkDeleteForm" method="POST" action="{{ route('owner.rooms.bulkDestroy') }}" onsubmit="return confirm('Delete the selected rooms? Rooms with occupied beds will be skipped automatically.')">
        @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($rooms as $room)
            <div class="relative">
                <label class="room-select-checkbox hidden absolute top-3 left-3 z-10 w-6 h-6 rounded-md bg-white border-2 border-coral-400 items-center justify-center cursor-pointer">
                    <input type="checkbox" name="room_ids[]" value="{{ $room->id }}" class="w-4 h-4 accent-coral-500" onclick="event.stopPropagation(); updateSelectedCount();">
                </label>
            <a href="{{ route('owner.rooms.show', $room) }}" class="room-card bg-white rounded-2xl border border-ink-100 hover:border-coral-300 transition overflow-hidden block">
                <div class="p-4 border-b border-ink-100">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <h3 class="font-display font-bold text-xl">Room {{ $room->room_number }}</h3>
                        <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold capitalize">{{ $room->room_type }}</span>
                    </div>
                    @if($room->floor)<div class="text-xs text-ink-500 mt-1">Floor: {{ $room->floor }}</div>@endif
                    <div class="flex items-center gap-2 mt-2 flex-wrap">
                        @foreach($room->amenities_list as $a)
                            <span class="text-xs">{{ $a }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="p-4">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-ink-700">Occupancy</span>
                        <span class="font-bold">{{ $room->occupied_count }}/{{ $room->beds->count() }}</span>
                    </div>
                    <div class="w-full bg-ink-100 rounded-full h-2">
                        <div class="bg-gradient-to-r from-emerald-500 to-coral-500 h-2 rounded-full" style="width: {{ $room->occupancy_percent }}%"></div>
                    </div>

                    @if($room->beds->count())
                        <div class="grid grid-cols-4 gap-1.5 mt-3">
                            @foreach($room->beds as $bed)
                                <div class="aspect-square rounded-lg flex items-center justify-center text-xs font-bold
                                    @if($bed->status === 'occupied') bg-rose-100 text-rose-700
                                    @elseif($bed->status === 'vacant') bg-emerald-100 text-emerald-700
                                    @elseif($bed->status === 'reserved') bg-amber-100 text-amber-700
                                    @else bg-ink-100 text-ink-700 @endif" 
                                    title="{{ $bed->bed_number }} - {{ $bed->status }}">
                                    {{ $bed->bed_number }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-ink-500 mt-3">No beds added yet.</p>
                    @endif

                    @if($room->monthly_rent > 0)
                        <div class="mt-3 pt-3 border-t border-ink-100">
                            <span class="text-xs text-ink-500">Room Rent:</span>
                            <span class="font-bold text-coral-600">₹{{ number_format($room->monthly_rent, 0) }}/mo</span>
                        </div>
                    @endif
                </div>
            </a>
            </div>
        @endforeach
    </div>
    </form>

    <div id="bulkDeleteBar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 bg-ink-900 text-cream px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-4 z-50">
        <span id="selectedCount" class="font-bold text-sm">0 selected</span>
        <button type="button" onclick="submitBulkDelete()" class="px-4 py-2 bg-rose-500 hover:bg-rose-600 rounded-xl text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete Selected</button>
        <button type="button" onclick="toggleSelectMode()" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-sm font-bold">Cancel</button>
    </div>

    <script>
        let selectMode = false;
        function toggleSelectMode() {
            selectMode = !selectMode;
            document.querySelectorAll('.room-select-checkbox').forEach(el => {
                el.classList.toggle('hidden', !selectMode);
                el.classList.toggle('flex', selectMode);
            });
            document.querySelectorAll('.room-card').forEach(el => {
                if (selectMode) { el.classList.add('pointer-events-none'); } else { el.classList.remove('pointer-events-none'); }
            });
            document.getElementById('toggleSelectBtn').textContent = selectMode ? '✕ Cancel' : '☑️ Select';
            document.getElementById('bulkDeleteBar').classList.toggle('hidden', !selectMode);
            updateSelectedCount();
        }
        function updateSelectedCount() {
            const n = document.querySelectorAll('input[name="room_ids[]"]:checked').length;
            document.getElementById('selectedCount').textContent = n + ' selected';
        }
        function submitBulkDelete() {
            const n = document.querySelectorAll('input[name="room_ids[]"]:checked').length;
            if (n === 0) { alert('Select at least one room first.'); return; }
            document.getElementById('bulkDeleteForm').requestSubmit();
        }
    </script>
@endif

@endif
@endif

@endsection