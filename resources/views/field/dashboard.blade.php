@extends('layouts.dashboard')
@section('title', 'Field Dashboard')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl">Hi, {{ auth()->user()->name }} <i class="fa-solid fa-hand fa-fw"></i></h1>
    <p class="text-ink-900/60 mt-1">Your visits and verification tasks</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
        <div class="text-3xl mb-2"><i class="fa-solid fa-calendar-days fa-fw"></i></div>
        <div class="text-xs text-ink-900/60 uppercase font-bold">Today's Visits</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['today_count'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-emerald-200">
        <div class="text-3xl mb-2"><i class="fa-solid fa-circle-check fa-fw"></i></div>
        <div class="text-xs text-emerald-700 uppercase font-bold">Today Completed</div>
        <div class="font-display font-black text-3xl text-emerald-700 mt-1">{{ $stats['today_completed'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-amber-200">
        <div class="text-3xl mb-2"><i class="fa-solid fa-hourglass-half fa-fw"></i></div>
        <div class="text-xs text-amber-700 uppercase font-bold">Pending Total</div>
        <div class="font-display font-black text-3xl text-amber-700 mt-1">{{ $stats['pending_total'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-coral-200">
        <div class="text-3xl mb-2"><i class="fa-solid fa-trophy fa-fw"></i></div>
        <div class="text-xs text-coral-700 uppercase font-bold">Total Done</div>
        <div class="font-display font-black text-3xl text-coral-700 mt-1">{{ $stats['completed_total'] }}</div>
    </div>
</div>

{{-- MISSED VISITS — scheduled time already passed, still not started --}}
@if($missedVisits->isNotEmpty())
<div class="bg-rose-50 border-2 border-rose-300 rounded-2xl mb-6">
    <div class="p-5 border-b border-rose-200 flex items-center gap-2">
        <span class="text-xl"><i class="fa-solid fa-circle fa-fw" style="color:#ef4444"></i></span>
        <h2 class="font-display font-bold text-xl text-rose-900">Missed — {{ $missedVisits->count() }} visit{{ $missedVisits->count() > 1 ? 's' : '' }} past scheduled time</h2>
    </div>
    <div class="divide-y divide-rose-200">
        @foreach($missedVisits as $visit)
            @php $lead = $visit->related_lead; @endphp
            <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
                <a href="{{ route('field.visits.show', $visit) }}" class="flex-1 min-w-0">
                    <h3 class="font-bold text-ink-950">{{ $visit->property->name }}</h3>
                    <p class="text-sm text-rose-800">Was scheduled {{ $visit->scheduled_at->format('h:i A') }} — {{ $visit->scheduled_at->diffForHumans() }}</p>
                </a>
                @if($lead?->phone)
                    <a href="tel:{{ $lead->phone }}" class="shrink-0 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call</a>
                @endif
                <a href="{{ route('field.visits.show', $visit) }}" class="shrink-0 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-bold">Start Now →</a>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="bg-white rounded-2xl border border-ink-900/10 mb-6">
    <div class="p-5 border-b border-ink-100 flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-display font-bold text-xl"><i class="fa-solid fa-calendar-days fa-fw"></i> Today's Schedule</h2>
        <div class="flex items-center gap-3">
            @if($todayVisits->isNotEmpty())
                <a href="{{ $todayVisits->pluck('property')->filter(fn($p) => $p->latitude && $p->longitude)->count() ? 'https://www.google.com/maps/dir/' . $todayVisits->pluck('property')->filter(fn($p) => $p->latitude && $p->longitude)->map(fn($p) => $p->latitude . ',' . $p->longitude)->implode('/') : '#' }}"
                   target="_blank" class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-map fa-fw"></i> View Route</a>
            @endif
            <span class="text-sm text-ink-900/60">{{ today()->format('D, d M Y') }}</span>
        </div>
    </div>

    @if($todayVisits->isEmpty())
        <div class="p-8 text-center text-ink-900/50">
            <div class="text-5xl mb-3"><i class="fa-solid fa-champagne-glasses fa-fw"></i></div>
            <p>No visits today. Enjoy your day!</p>
        </div>
    @else
        <div class="divide-y divide-ink-100">
            @foreach($todayVisits as $visit)
                @php $lead = $visit->related_lead; @endphp
                <div class="p-5 hover:bg-cream transition">
                    <div class="flex items-start justify-between gap-3">
                        <a href="{{ route('field.visits.show', $visit) }}" class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-xs font-bold uppercase px-2 py-0.5 rounded
                                    @if($visit->status === 'completed') bg-emerald-100 text-emerald-700
                                    @elseif($visit->status === 'in_progress') bg-amber-100 text-amber-700
                                    @elseif($visit->status === 'scheduled') bg-blue-100 text-blue-700
                                    @else bg-rose-100 text-rose-700 @endif">
                                    {{ str_replace('_', ' ', $visit->status) }}
                                </span>
                                <span class="text-xs text-ink-500 capitalize">{{ str_replace('_', ' ', $visit->visit_type) }}</span>
                            </div>
                            <h3 class="font-bold text-lg text-ink-950">{{ $visit->property->name }}</h3>
                            <p class="text-sm text-ink-700 mt-1"><i class="fa-solid fa-location-dot fa-fw"></i> {{ $visit->property->locality?->name }}, {{ $visit->property->city?->name }}</p>
                        </a>
                        <div class="text-right whitespace-nowrap flex flex-col items-end gap-2">
                            <div>
                                <div class="text-xs text-ink-500 uppercase font-bold">Time</div>
                                <div class="font-bold text-ink-950">{{ $visit->scheduled_at->format('h:i A') }}</div>
                            </div>
                            @if($lead?->phone)
                                <a href="tel:{{ $lead->phone }}" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if($upcomingVisits->count())
<div class="bg-white rounded-2xl border border-ink-900/10 mb-6">
    <div class="p-5 border-b border-ink-100">
        <h2 class="font-display font-bold text-xl"><i class="fa-solid fa-calendar-days fa-fw"></i> Upcoming</h2>
    </div>
    <div class="divide-y divide-ink-100">
        @foreach($upcomingVisits as $visit)
            <a href="{{ route('field.visits.show', $visit) }}" class="block p-4 hover:bg-cream transition">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-ink-950">{{ $visit->property->name }}</h3>
                        <p class="text-sm text-ink-700"><i class="fa-solid fa-location-dot fa-fw"></i> {{ $visit->property->locality?->name }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-ink-950">{{ $visit->scheduled_at->format('d M') }}</div>
                        <div class="text-xs text-ink-500">{{ $visit->scheduled_at->format('h:i A') }}</div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- PERFORMANCE --}}
<div class="bg-white rounded-2xl border border-ink-900/10">
    <div class="p-5 border-b border-ink-100">
        <h2 class="font-display font-bold text-xl"><i class="fa-solid fa-chart-column fa-fw"></i> My Performance</h2>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 p-5">
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">This Week</div>
            <div class="font-display font-black text-2xl mt-1">{{ $performance['week_completed'] }} <span class="text-sm font-normal text-ink-500">visits</span></div>
        </div>
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">This Month</div>
            <div class="font-display font-black text-2xl mt-1">{{ $performance['month_completed'] }} <span class="text-sm font-normal text-ink-500">visits</span></div>
        </div>
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">Properties Verified</div>
            <div class="font-display font-black text-2xl mt-1">{{ $performance['properties_verified'] }}</div>
        </div>
        <div>
            <div class="text-xs text-ink-500 uppercase font-bold">Avg Time / Visit</div>
            <div class="font-display font-black text-2xl mt-1">
                @if($performance['avg_minutes_per_visit'])
                    {{ $performance['avg_minutes_per_visit'] }} <span class="text-sm font-normal text-ink-500">min</span>
                @else
                    —
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
