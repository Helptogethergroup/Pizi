@extends('layouts.dashboard')
@section('title', 'Deleted Rooms')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rooms.index') }}" class="text-coral-500 font-bold">← Back to rooms</a>
    <h1 class="font-display font-black text-3xl mt-2">🗑️ Deleted Rooms</h1>
    <p class="text-ink-900/60 mt-1">Rooms deleted in the last 30 days can be restored here.</p>
</div>

@if($properties->count() > 1)
    <form method="GET" class="mb-6">
        <select name="property_id" onchange="this.form.submit()" class="px-4 py-3 rounded-xl border border-ink-200">
            <option value="">— All properties —</option>
            @foreach($properties as $p)
                <option value="{{ $p->id }}" @selected($selectedPropertyId == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </form>
@endif

@if($trashedRooms->isEmpty())
    <div class="bg-white p-8 rounded-2xl border border-ink-100 text-center">
        <p class="text-ink-700">No deleted rooms. Trash is empty.</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($trashedRooms as $room)
            <div class="bg-white p-5 rounded-2xl border border-ink-100 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <div class="font-display font-black text-xl">Room {{ $room->room_number }}</div>
                    <div class="text-sm text-ink-700">{{ $room->property?->name }}</div>
                    <div class="text-xs text-ink-500 mt-1">Deleted {{ $room->deleted_at->diffForHumans() }} — will be permanently removed {{ $room->deleted_at->addDays(30)->format('d M Y') }}</div>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('owner.rooms.restore', $room->id) }}" class="inline-block">
                        @csrf
                        <button class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold">↩ Restore</button>
                    </form>
                    <form method="POST" action="{{ route('owner.rooms.force-delete', $room->id) }}" onsubmit="return confirm('Permanently delete this room? This CANNOT be undone.')" class="inline-block">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-xl text-sm font-bold">Delete Forever</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection