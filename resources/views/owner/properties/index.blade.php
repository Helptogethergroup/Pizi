@extends('layouts.dashboard')
@section('title', 'My Properties')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="font-display font-black text-3xl">My properties</h1>
    <a href="{{ route('owner.properties.create') }}" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold">+ Add new</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($properties as $p)
        <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
            <div class="aspect-[4/3] bg-ink-900/5">
                <img src="{{ $p->cover_url }}" class="w-full h-full object-cover">
            </div>
            <div class="p-5">
                <div class="flex items-center gap-2 mb-2">
                    @if($p->is_verified)<span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">✓ Verified</span>
                    @else<span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">Pending</span>@endif
                    @if(!$p->is_active)<span class="px-2 py-0.5 rounded-full text-xs bg-rose-100 text-rose-700">Inactive</span>@endif
                </div>
                <h3 class="font-display font-bold text-lg">{{ $p->name }}</h3>
                <p class="text-sm text-ink-900/60">{{ $p->locality?->name }}</p>
                <div class="flex justify-between items-center mt-4 text-sm">
                    <span class="font-bold">{{ $p->rent_range }}</span>
                    <span class="text-ink-900/50">{{ $p->view_count }} views · {{ $p->lead_count }} leads</span>
                </div>
                <div class="mt-4">
                    <a href="{{ route('owner.properties.edit', $p) }}" class="block text-center text-xs py-2 rounded-lg bg-ink-900 text-cream font-semibold">Edit</a>
                </div>
                <p class="text-[11px] text-ink-900/40 mt-2 text-center">To pause or remove a listing, contact support.</p>
            </div>
        </div>
    @empty
        <p class="col-span-3 text-center text-ink-900/50 py-20">No properties yet. <a href="{{ route('owner.properties.create') }}" class="text-coral-600 font-semibold">Add your first listing</a>.</p>
    @endforelse
</div>
<div class="mt-10">{{ $properties->links() }}</div>
@endsection
