@extends('layouts.dashboard')
@section('title', 'Tele-caller Dashboard')
@section('content')
<h1 class="font-display font-black text-4xl">Hi, {{ auth()->user()->name }}.</h1>
<p class="text-ink-900/60">Your sales engine for today.</p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
    @foreach([
        ['Total assigned', $stats['total_assigned'], 'bg-ink-900 text-cream'],
        ['New (uncontacted)', $stats['new'], 'bg-coral-500 text-white'],
        ['Follow-ups today', $stats['follow_ups_today'], 'bg-amber-100 text-amber-900'],
        ['Closed (won)', $stats['closed_won'], 'bg-emerald-100 text-emerald-900'],
    ] as [$l, $v, $c])
        <div class="p-5 rounded-2xl {{ $c }}">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ $l }}</div>
            <div class="font-display font-black text-3xl mt-2">{{ $v }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-10">
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4">🔥 New leads — call now</h2>
        @forelse($newLeads as $lead)
            <a href="{{ route('telecaller.leads.show', $lead) }}" class="flex items-center justify-between py-3 border-t border-ink-900/5 first:border-t-0 hover:bg-ink-900/5 px-2 rounded">
                <div>
                    <div class="font-semibold">{{ $lead->name }}</div>
                    <div class="text-xs text-ink-900/50">📞 {{ $lead->phone }} · {{ $lead->property?->name ?? 'General' }}</div>
                </div>
                <div class="text-xs text-ink-900/40">{{ $lead->created_at->diffForHumans() }}</div>
            </a>
        @empty
            <p class="text-ink-900/50 text-sm py-6 text-center">No new leads. Great hustle!</p>
        @endforelse
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4">📅 Follow-ups today</h2>
        @forelse($todaysFollowUps as $lead)
            <a href="{{ route('telecaller.leads.show', $lead) }}" class="flex items-center justify-between py-3 border-t border-ink-900/5 first:border-t-0 hover:bg-ink-900/5 px-2 rounded">
                <div>
                    <div class="font-semibold">{{ $lead->name }}</div>
                    <div class="text-xs text-ink-900/50">📞 {{ $lead->phone }}</div>
                </div>
                <span class="px-2 py-1 rounded-full text-xs {{ $lead->statusBadge() }}">{{ str_replace('_',' ',$lead->status) }}</span>
            </a>
        @empty
            <p class="text-ink-900/50 text-sm py-6 text-center">No follow-ups scheduled today.</p>
        @endforelse
    </div>
</div>
@endsection
