@extends('layouts.dashboard')
@section('title', 'Properties — Admin')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="font-display font-black text-3xl">All properties</h1>
    <form class="flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Search…" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <select name="verified" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All</option>
            <option value="1" @selected(request('verified') === '1')>Verified</option>
            <option value="0" @selected(request('verified') === '0')>Unverified</option>
        </select>
        <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg">Filter</button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr><th class="px-4 py-3">Property</th><th>Owner</th><th>Locality</th><th>Rent</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($properties as $p)
            <tr class="border-t border-ink-900/5">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $p->name }}</div>
                    <div class="text-xs text-ink-900/50">{{ $p->view_count }} views · {{ $p->lead_count }} leads</div>
                </td>
                <td>{{ $p->owner?->name }}</td>
                <td>{{ $p->locality?->name }}, {{ $p->city?->name }}</td>
                <td class="font-semibold">{{ $p->rent_range }}</td>
                <td>
                    @if($p->is_verified)<span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">✓ Verified</span>@else<span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">Pending</span>@endif
                    @if($p->is_active)<span class="px-2 py-1 rounded-full text-xs bg-ink-900/5">Active</span>@endif
                </td>
                <td class="px-4 py-3 space-x-1">
                    <a href="{{ route('property.show', $p->slug) }}" target="_blank" class="text-xs px-2 py-1 rounded-lg border border-ink-900/15">View</a>
                    <form method="POST" action="{{ route('admin.properties.verify', $p) }}" class="inline">@csrf @method('PATCH')<button class="text-xs px-2 py-1 rounded-lg bg-emerald-600 text-white">{{ $p->is_verified ? 'Unverify' : 'Verify' }}</button></form>
                    <form method="POST" action="{{ route('admin.properties.feature', $p) }}" class="inline">@csrf @method('PATCH')<button class="text-xs px-2 py-1 rounded-lg bg-coral-500 text-white">{{ $p->is_featured ? 'Unfeature' : 'Feature' }}</button></form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $properties->links() }}</div>
@endsection
