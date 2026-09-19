@extends('layouts.dashboard')
@section('title', 'Room ' . $room->room_number . ' — Admin')
@section('content')

<div class="mb-6">
    <a href="{{ route('admin.rooms.index') }}" class="text-coral-500 font-bold">← Back to all rooms</a>
</div>

<div class="bg-white p-6 rounded-2xl border border-ink-100 mb-6">
    <h1 class="font-display font-black text-3xl">Room {{ $room->room_number }}</h1>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-ink-100">
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">Property</div>
            <div class="font-bold">{{ $room->property?->name }}</div>
        </div>
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">Owner</div>
            <div class="font-bold">{{ $room->owner?->name }}</div>
        </div>
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">Type</div>
            <div class="font-bold capitalize">{{ $room->room_type }}</div>
        </div>
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">Rent</div>
            <div class="font-bold text-coral-600">₹{{ number_format($room->monthly_rent, 0) }}</div>
        </div>
    </div>
</div>

<h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-bed fa-fw"></i> Beds ({{ $room->beds->count() }})</h2>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    @foreach($room->beds as $bed)
        <div class="bg-white p-4 rounded-2xl border-2 
            @if($bed->status === 'occupied') border-rose-300
            @elseif($bed->status === 'vacant') border-emerald-300
            @else border-amber-300 @endif">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-black text-xl">Bed {{ $bed->bed_number }}</h3>
                <span class="text-xs font-bold
                    @if($bed->status === 'occupied') text-rose-700
                    @elseif($bed->status === 'vacant') text-emerald-700
                    @else text-amber-700 @endif">
                    {{ $bed->status_label }}
                </span>
            </div>
            @if($bed->tenant)
                <div class="mt-2 text-sm">
                    <div class="font-bold"><i class="fa-solid fa-user fa-fw"></i> {{ $bed->tenant->name }}</div>
                    <div class="text-ink-700"><i class="fa-solid fa-phone fa-fw"></i> {{ $bed->tenant->phone }}</div>
                    @if($bed->occupied_since)
                        <div class="text-xs text-ink-500">Since: {{ $bed->occupied_since->format('d M Y') }}</div>
                    @endif
                    <a href="{{ route('admin.tenants.show', $bed->tenant) }}" class="text-xs text-coral-500 font-bold mt-1 inline-block">View Tenant →</a>
                </div>
            @endif
        </div>
    @endforeach
</div>

<form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" onsubmit="return confirm('Delete room?')">
    @csrf @method('DELETE')
    <button class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete Room</button>
</form>

@endsection