@extends('layouts.dashboard')
@section('title', 'Properties — Admin')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">All Properties</h1>
        <p class="text-ink-900/60 mt-1">Manage all PGs on the platform</p>
    </div>
    <a href="{{ route('admin.properties.create') }}" class="inline-flex items-center gap-2 px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold transition shadow-lg shadow-coral-500/30">
        + Add New Property
    </a>
</div>

{{-- Filter Tabs --}}
<div class="bg-white rounded-xl border border-ink-100 p-2 mb-4 flex gap-1 overflow-x-auto scrollbar-hide">
    @php $currentFilter = $filter ?? request('filter', 'all'); @endphp
    @foreach([
        'all' => '📋 All Properties',
        'my' => '🏠 My PGs (Admin)',
        'owners' => '👤 Owners\' PGs',
        'unassigned' => '⚠️ Unassigned',
    ] as $key => $label)
        <a href="{{ route('admin.properties.index', ['filter' => $key]) }}"
           class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap {{ $currentFilter === $key ? 'bg-coral-500 text-white' : 'text-ink-700 hover:bg-cream' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- Search --}}
<form method="GET" class="mb-4">
    <input type="hidden" name="filter" value="{{ $currentFilter }}">
    <div class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Search property name..." class="flex-1 px-4 py-3 rounded-xl border border-ink-200">
        <button class="px-5 py-3 bg-ink-950 text-cream rounded-xl font-bold">Search</button>
    </div>
</form>

@if($properties->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3">📋</div>
        <p class="text-ink-700">No properties found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($properties as $property)
            <div class="bg-white p-4 rounded-2xl border border-ink-100">
                <div class="flex items-center gap-4 flex-wrap">

                    {{-- Cover image --}}
                    <div class="w-20 h-20 rounded-xl overflow-hidden bg-cream flex-shrink-0">
                        @if($property->cover_image)
                            <img src="{{ str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-3xl">🏠</div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            @if($property->is_verified)
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">✓ Verified</span>
                            @endif
                            @if($property->is_featured)
                                <span class="text-xs bg-coral-500 text-white px-2 py-0.5 rounded-full font-bold">⭐ Featured</span>
                            @endif
                            @if(!$property->is_active)
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">⏸ Paused</span>
                            @endif
                        </div>

                        <h3 class="font-display font-bold text-base text-ink-950">{{ $property->name }}</h3>
                        <div class="flex items-center gap-3 text-xs text-ink-700 mt-0.5 flex-wrap">
                            <span>📍 {{ $property->locality?->name }}, {{ $property->city?->name }}</span>
                            <span class="font-bold text-coral-600">₹{{ number_format($property->rent_min) }} - ₹{{ number_format($property->rent_max) }}</span>
                        </div>

                        {{-- 🔥 INLINE OWNER ASSIGN FORM --}}
                        <form method="POST" action="{{ route('admin.properties.assign', $property) }}" class="mt-2 flex items-center gap-2 flex-wrap">
                            @csrf @method('PATCH')
                            <label class="text-xs font-bold text-ink-500 uppercase">Owner:</label>
                            <select name="owner_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-ink-200 text-xs font-bold bg-white">
                                <option value="">— Unassigned —</option>
                                @foreach($allOwners as $o)
                                    <option value="{{ $o->id }}" @selected($property->owner_id == $o->id)>
                                        @if($o->id === auth()->id())
                                            🏠 Me (Admin)
                                        @elseif($o->role === 'admin')
                                            🛡️ {{ $o->name }} (Admin)
                                        @else
                                            👤 {{ $o->name }} {{ $o->phone ? '('.$o->phone.')' : '' }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @if(!$property->owner_id)
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">⚠️ No owner</span>
                            @endif
                        </form>
                    </div>

                    {{-- Compact Action Buttons --}}
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <a href="{{ route('property.show', $property->slug) }}" target="_blank" class="px-3 py-2 bg-ink-100 hover:bg-ink-200 text-ink-950 rounded-lg text-xs font-bold whitespace-nowrap">
                            👁 View
                        </a>
                        <a href="{{ route('owner.properties.edit', $property->slug) }}"
                           class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold whitespace-nowrap">
                            ✏ Edit
                        </a>

                        <form method="POST" action="{{ route('admin.properties.verify', $property) }}" class="inline-block">
                            @csrf @method('PATCH')
                            <button class="px-3 py-2 {{ $property->is_verified ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-500 hover:bg-emerald-600' }} text-white rounded-lg text-xs font-bold whitespace-nowrap">
                                {{ $property->is_verified ? '↺ Unverify' : '✓ Verify' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.properties.feature', $property) }}" class="inline-block">
                            @csrf @method('PATCH')
                            <button class="px-3 py-2 {{ $property->is_featured ? 'bg-slate-600 hover:bg-slate-700' : 'bg-coral-500 hover:bg-coral-600' }} text-white rounded-lg text-xs font-bold whitespace-nowrap">
                                {{ $property->is_featured ? '★ Unfeature' : '⭐ Feature' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.properties.toggle', $property) }}" class="inline-block">
                            @csrf @method('PATCH')
                            <button class="px-3 py-2 {{ $property->is_active ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-500 hover:bg-emerald-600' }} text-white rounded-lg text-xs font-bold whitespace-nowrap">
                                {{ $property->is_active ? '⏸ Pause' : '▶ Activate' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" onsubmit="return confirm('Delete this property?')" class="inline-block">
                            @csrf @method('DELETE')
                            <button class="px-3 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-xs font-bold whitespace-nowrap">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $properties->links() }}</div>
@endif

@endsection