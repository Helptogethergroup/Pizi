@extends('layouts.dashboard')
@section('title', 'Properties — Admin')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">All Properties</h1>
        <p class="text-ink-900/60 mt-1">Manage every listing on the platform</p>
    </div>
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="p-4 rounded-2xl bg-ink-950 text-cream">
        <div class="text-xs uppercase opacity-70">Total</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-emerald-500 text-white">
        <div class="text-xs uppercase opacity-90">Active</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['active'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-amber-100 text-amber-900">
        <div class="text-xs uppercase opacity-80">Pending</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['pending'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-coral-500 text-white">
        <div class="text-xs uppercase opacity-90">Featured</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['featured'] }}</div>
    </div>
</div>

{{-- Filters --}}
<form class="flex flex-wrap gap-2 mb-4 bg-white p-3 rounded-xl border border-ink-900/10">
    <input name="search" value="{{ request('search') }}" placeholder="Search property name…" class="flex-1 min-w-[200px] px-3 py-2 rounded-lg border border-ink-900/15">
    <select name="verified" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <option value="">All verification</option>
        <option value="1" @selected(request('verified') === '1')>✓ Verified</option>
        <option value="0" @selected(request('verified') === '0')>⏳ Pending</option>
    </select>
    <select name="status" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <option value="">All status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="disabled" @selected(request('status') === 'disabled')>Disabled</option>
    </select>
    <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg font-semibold">Filter</button>
    @if(request()->hasAny(['search', 'verified', 'status']))
        <a href="{{ route('admin.properties.index') }}" class="px-4 py-2 bg-cream text-ink-900 rounded-lg font-semibold border border-ink-900/15">× Clear</a>
    @endif
</form>

{{-- Properties table --}}
<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Property</th>
                <th>Owner</th>
                <th>Location</th>
                <th>Rent</th>
                <th>Status</th>
                <th class="text-right pr-4">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($properties as $p)
            <tr class="border-t border-ink-900/5 hover:bg-cream/40">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        @if($p->cover_image)
                            <img src="{{ str_starts_with($p->cover_image, 'http') ? $p->cover_image : asset('storage/' . $p->cover_image) }}" class="w-12 h-12 rounded-lg object-cover">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-ink-900/10 flex items-center justify-center text-xl">🏠</div>
                        @endif
                        <div>
                            <div class="font-semibold">{{ $p->name }}</div>
                            <div class="text-xs text-ink-900/50">{{ $p->view_count }} views · {{ $p->lead_count }} leads</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="font-semibold">{{ $p->owner?->name ?? '—' }}</div>
                    <div class="text-xs text-ink-900/50">{{ $p->owner?->email }}</div>
                </td>
                <td>
                    <div>{{ $p->locality?->name }}</div>
                    <div class="text-xs text-ink-900/50">{{ $p->city?->name }}</div>
                </td>
                <td class="font-semibold">{{ $p->rent_range ?? '₹' . number_format($p->rent_min) . '-' . number_format($p->rent_max) }}</td>
                <td>
                    <div class="flex flex-col gap-1">
                        @if($p->is_verified)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700 inline-block w-fit">✓ Verified</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700 inline-block w-fit">⏳ Pending</span>
                        @endif
                        @if($p->is_active)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-ink-900/5 inline-block w-fit">Active</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs bg-rose-100 text-rose-700 inline-block w-fit">Disabled</span>
                        @endif
                        @if($p->is_featured)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-coral-500 text-white inline-block w-fit">⭐ Featured</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex flex-wrap gap-1 justify-end">
                        <a href="{{ route('property.show', $p->slug) }}" target="_blank" class="text-xs px-2 py-1 rounded-lg border border-ink-900/15 hover:bg-cream">👁 View</a>
                        <a href="{{ route('owner.properties.edit', $p) }}" class="text-xs px-2 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600">✏️ Edit</a>

                        <form method="POST" action="{{ route('admin.properties.verify', $p) }}" class="inline">@csrf @method('PATCH')
                            <button class="text-xs px-2 py-1 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">{{ $p->is_verified ? 'Unverify' : '✓ Verify' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.properties.feature', $p) }}" class="inline">@csrf @method('PATCH')
                            <button class="text-xs px-2 py-1 rounded-lg bg-coral-500 text-white hover:bg-coral-600">{{ $p->is_featured ? 'Unfeature' : '⭐ Feature' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.properties.toggle', $p) }}" class="inline">@csrf @method('PATCH')
                            <button class="text-xs px-2 py-1 rounded-lg {{ $p->is_active ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-500 hover:bg-emerald-600' }} text-white">{{ $p->is_active ? '🚫 Disable' : '✅ Enable' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.properties.destroy', $p) }}" class="inline" onsubmit="return confirm('Delete this property permanently? This cannot be undone.');">@csrf @method('DELETE')
                            <button class="text-xs px-2 py-1 rounded-lg bg-rose-500 text-white hover:bg-rose-600">🗑 Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-12 text-center text-ink-900/50">No properties found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6">{{ $properties->links() }}</div>

@endsection