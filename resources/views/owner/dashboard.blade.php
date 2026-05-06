@extends('layouts.dashboard')
@section('title', 'My Dashboard')
@section('content')
<h1 class="font-display font-black text-4xl">Welcome, {{ auth()->user()->name }}.</h1>
<p class="text-ink-900/60">Here's how your listings are doing.</p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
    @foreach([
        ['Total properties', $stats['properties'], 'bg-ink-900 text-cream'],
        ['Active', $stats['active'], 'bg-emerald-100 text-emerald-900'],
        ['Total views', number_format($stats['total_views']), 'bg-coral-50 text-coral-900'],
        ['Total leads', $stats['total_leads'], 'bg-violet-100 text-violet-900'],
    ] as [$l, $v, $c])
        <div class="p-5 rounded-2xl {{ $c }}">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ $l }}</div>
            <div class="font-display font-black text-3xl mt-2">{{ $v }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-10">
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-ink-900/10">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display font-bold text-xl">Your properties</h2>
            <a href="{{ route('owner.properties.create') }}" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold text-sm">+ Add new</a>
        </div>
        @forelse($properties as $p)
            <div class="flex items-center justify-between py-3 border-t border-ink-900/5">
                <div>
                    <div class="font-semibold">{{ $p->name }}</div>
                    <div class="text-xs text-ink-900/50">{{ $p->locality?->name }} · {{ $p->view_count }} views</div>
                </div>
                <a href="{{ route('owner.properties.edit', $p) }}" class="text-coral-600 text-sm font-semibold">Edit →</a>
            </div>
        @empty
            <p class="text-ink-900/50 text-center py-12">No properties yet. <a href="{{ route('owner.properties.create') }}" class="text-coral-600 font-semibold">Add your first one</a>.</p>
        @endforelse
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4">Recent leads</h2>
        @forelse($recentLeads as $lead)
            <div class="py-2 border-t border-ink-900/5 first:border-t-0">
                <div class="font-semibold text-sm">{{ $lead->name }}</div>
                <div class="text-xs text-ink-900/50">📞 {{ $lead->phone }} · {{ $lead->property?->name }}</div>
                <div class="text-xs text-ink-900/40 mt-1">{{ $lead->created_at->diffForHumans() }}</div>
            </div>
        @empty
            <p class="text-ink-900/50 text-sm">No leads yet.</p>
        @endforelse
    </div>
</div>
@endsection
