@extends('layouts.fieldexec')
@section('title', 'Today')
@section('content')

<div class="mb-2">
    <p class="text-ink-900/60 text-sm">Hi <strong>{{ auth()->user()->name }}</strong> 👋</p>
    <h1 class="font-display font-black text-2xl">{{ now()->format('l, d F') }}</h1>
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-3 gap-3 mt-4">
    <div class="p-4 rounded-2xl bg-ink-950 text-cream">
        <div class="text-3xl font-display font-black">{{ $stats['today_total'] }}</div>
        <div class="text-xs text-cream/70 mt-1">Today</div>
    </div>
    <div class="p-4 rounded-2xl bg-emerald-500 text-white">
        <div class="text-3xl font-display font-black">{{ $stats['today_done'] }}</div>
        <div class="text-xs text-white/80 mt-1">Done</div>
    </div>
    <div class="p-4 rounded-2xl bg-amber-100 text-amber-900">
        <div class="text-3xl font-display font-black">{{ $stats['today_pending'] }}</div>
        <div class="text-xs mt-1">Pending</div>
    </div>
</div>

{{-- Monthly --}}
<div class="grid grid-cols-2 gap-3 mt-3">
    <div class="p-4 rounded-2xl bg-coral-500 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">This month closed</div>
        <div class="text-3xl font-display font-black mt-1">{{ $stats['this_month_closed'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-white border border-ink-900/10">
        <div class="text-xs uppercase tracking-wide text-ink-900/60">Tokens collected</div>
        <div class="text-2xl font-display font-black mt-1">₹{{ number_format($stats['tokens_collected']) }}</div>
    </div>
</div>

{{-- Today's visits --}}
<div class="mt-6">
    <h2 class="font-display font-bold text-xl mb-3">📅 Today's schedule</h2>

    @forelse($todayVisits as $visit)
        <a href="{{ route('fieldexec.visits.show', $visit) }}" class="block bg-white p-4 rounded-2xl border border-ink-900/10 mb-3 active:scale-[0.98] transition">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1">
                    <div class="text-xs text-ink-900/50 font-mono">⏰ {{ $visit->scheduled_at->format('h:i A') }}</div>
                    <div class="font-display font-bold text-lg mt-0.5">{{ $visit->lead?->name ?? '—' }}</div>
                    <div class="text-sm text-ink-900/60">📞 {{ $visit->lead?->phone }}</div>
                </div>
                <span class="px-2 py-1 rounded-full text-xs {{ $visit->outcome_badge }}">{{ $visit->outcome }}</span>
            </div>

            <div class="border-t border-ink-900/5 pt-3 mt-3">
                <div class="text-xs text-ink-900/50">PROPERTY</div>
                <div class="text-sm font-semibold mt-0.5">{{ $visit->property?->name }}</div>
                <div class="text-xs text-ink-900/60">📍 {{ $visit->property?->locality?->name }}</div>
            </div>

            @if($visit->outcome === 'pending')
                <div class="mt-3 text-coral-600 font-bold text-sm">Open visit details →</div>
            @endif
        </a>
    @empty
        <div class="bg-white p-12 rounded-2xl border border-ink-900/10 text-center">
            <div class="text-5xl mb-3">🏖️</div>
            <p class="font-display font-bold text-lg">No visits today!</p>
            <p class="text-sm text-ink-900/60 mt-1">Enjoy your day. Check upcoming below.</p>
        </div>
    @endforelse
</div>

{{-- Upcoming --}}
@if($upcomingVisits->count())
<div class="mt-8">
    <h2 class="font-display font-bold text-xl mb-3">📋 Upcoming</h2>
    @foreach($upcomingVisits as $visit)
        <a href="{{ route('fieldexec.visits.show', $visit) }}" class="block bg-white p-4 rounded-xl border border-ink-900/10 mb-2">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs text-ink-900/50">{{ $visit->scheduled_at->format('d M, h:i A') }}</div>
                    <div class="font-semibold">{{ $visit->lead?->name }}</div>
                    <div class="text-xs text-ink-900/60">{{ $visit->property?->name }}</div>
                </div>
                <div class="text-coral-500">→</div>
            </div>
        </a>
    @endforeach
</div>
@endif

@endsection