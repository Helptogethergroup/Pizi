@extends('layouts.dashboard')
@section('title', 'All Rooms — Admin')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl"><i class="fa-solid fa-bed fa-fw"></i> All Rooms & Beds</h1>
    <p class="text-ink-900/60 mt-1">Platform-wide room inventory</p>
</div>

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

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Room number, property..." class="flex-1 min-w-[180px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <select name="owner_id" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Owners</option>
        @foreach($owners as $o)
            <option value="{{ $o->id }}" @selected(request('owner_id') == $o->id)>{{ $o->name }}</option>
        @endforeach
    </select>
    <select name="property_id" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Properties</option>
        @foreach($properties as $p)
            <option value="{{ $p->id }}" @selected(request('property_id') == $p->id)>{{ $p->name }}</option>
        @endforeach
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>
</form>

@if($rooms->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-bed fa-fw"></i></div>
        <p class="text-ink-700">No rooms found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($rooms as $room)
            <a href="{{ route('admin.rooms.show', $room) }}" class="block bg-white p-4 rounded-2xl border border-ink-100 hover:border-coral-300 transition">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-bold">Room {{ $room->room_number }}</h3>
                            <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold capitalize">{{ $room->room_type }}</span>
                            <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold capitalize">{{ $room->gender }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-ink-700 flex-wrap">
                            <span><i class="fa-solid fa-house fa-fw"></i> {{ $room->property?->name }}</span>
                            <span><i class="fa-solid fa-user fa-fw"></i> Owner: <strong>{{ $room->owner?->name }}</strong></span>
                            @if($room->floor)<span><i class="fa-solid fa-location-dot fa-fw"></i> Floor: {{ $room->floor }}</span>@endif
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="font-bold">{{ $room->beds->where('status', 'occupied')->count() }}/{{ $room->beds->count() }}</div>
                        <div class="text-xs text-ink-700">occupied</div>
                    </div>

                    <div class="grid grid-cols-4 gap-1">
                        @foreach($room->beds as $bed)
                            <div class="w-6 h-6 rounded text-xs flex items-center justify-center font-bold
                                @if($bed->status === 'occupied') bg-rose-100 text-rose-700
                                @elseif($bed->status === 'vacant') bg-emerald-100 text-emerald-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ $bed->bed_number }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $rooms->links() }}</div>
@endif

@endsection