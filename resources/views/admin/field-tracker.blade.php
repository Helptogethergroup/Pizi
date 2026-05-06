@extends('layouts.dashboard')
@section('title', 'Field Executive Tracker')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">Field Executive Tracker</h1>
        <p class="text-ink-900/60 mt-1">Live view of field operations · {{ now()->format('l, d M Y') }}</p>
    </div>
    <button onclick="window.location.reload()" class="px-4 py-2 bg-ink-900 text-cream rounded-lg text-sm">🔄 Refresh</button>
</div>

{{-- Active visits (live) --}}
@if($activeVisits->count())
<div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
        </span>
        <h2 class="font-display font-bold text-xl">Live now ({{ $activeVisits->count() }})</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($activeVisits as $visit)
            <div class="bg-emerald-50 border-2 border-emerald-300 p-5 rounded-2xl">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-display font-bold text-lg">{{ $visit->fieldExecutive?->name }}</div>
                        <div class="text-xs text-emerald-700 font-semibold mt-0.5">
                            ⏱ Inside since {{ $visit->checked_in_at?->diffForHumans() }}
                        </div>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs bg-emerald-500 text-white font-bold">LIVE</span>
                </div>

                <div class="mt-3 pt-3 border-t border-emerald-200 text-sm">
                    <div class="text-xs text-ink-900/60 uppercase">Visiting</div>
                    <div class="font-semibold">{{ $visit->property?->name }}</div>
                    <div class="text-xs text-ink-900/60">📍 {{ $visit->property?->locality?->name }}</div>
                </div>

                <div class="mt-2 text-sm">
                    <div class="text-xs text-ink-900/60 uppercase">With tenant</div>
                    <div class="font-semibold">{{ $visit->lead?->name }} · 📞 {{ $visit->lead?->phone }}</div>
                </div>

                @if($visit->checkin_distance_m !== null)
                    <div class="mt-2 text-xs">
                        @if($visit->checkin_distance_m <= 100)
                            <span class="text-emerald-700">✅ Checked in within {{ $visit->checkin_distance_m }}m</span>
                        @else
                            <span class="text-rose-700">⚠️ Checked in {{ $visit->checkin_distance_m }}m away (admin override)</span>
                        @endif
                    </div>
                @endif

                @if($visit->checkin_lat && $visit->checkin_lng)
                    <a href="https://www.google.com/maps?q={{ $visit->checkin_lat }},{{ $visit->checkin_lng }}" target="_blank"
                       class="inline-block mt-3 text-xs text-coral-600 font-semibold">📍 View on Google Maps →</a>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Today's exec performance --}}
<div class="mb-8">
    <h2 class="font-display font-bold text-xl mb-3">Today's executive performance</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($execs as $exec)
            <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-display font-bold text-lg">{{ $exec->name }}</div>
                        <div class="text-xs text-ink-900/60">📞 {{ $exec->phone }}</div>
                    </div>
                    @if($exec->today_done >= $exec->today_total && $exec->today_total > 0)
                        <span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">✓ Done</span>
                    @elseif($exec->today_total === 0)
                        <span class="px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-600">No visits</span>
                    @else
                        <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">In progress</span>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-2 mt-4">
                    <div class="p-2 bg-ink-950 text-cream rounded text-center">
                        <div class="font-display font-black text-2xl">{{ $exec->today_total }}</div>
                        <div class="text-xs opacity-70">Total</div>
                    </div>
                    <div class="p-2 bg-emerald-100 text-emerald-900 rounded text-center">
                        <div class="font-display font-black text-2xl">{{ $exec->today_done }}</div>
                        <div class="text-xs">Done</div>
                    </div>
                    <div class="p-2 bg-coral-100 text-coral-900 rounded text-center">
                        <div class="font-display font-black text-2xl">{{ $exec->today_closed }}</div>
                        <div class="text-xs">Closed</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Today's all visits timeline --}}
<div class="mb-8">
    <h2 class="font-display font-bold text-xl mb-3">Today's visit timeline</h2>
    <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">Scheduled</th>
                    <th>Executive</th>
                    <th>Tenant</th>
                    <th>Property</th>
                    <th>Check-in</th>
                    <th>Outcome</th>
                </tr>
            </thead>
            <tbody>
            @forelse($todaysVisits as $v)
                <tr class="border-t border-ink-900/5">
                    <td class="px-4 py-3 text-xs">{{ $v->scheduled_at->format('h:i A') }}</td>
                    <td class="text-xs font-semibold">{{ $v->fieldExecutive?->name }}</td>
                    <td class="text-xs">{{ $v->lead?->name }}</td>
                    <td class="text-xs">{{ $v->property?->name }}</td>
                    <td class="text-xs">
                        @if($v->checked_in_at)
                            ✅ {{ $v->checked_in_at->format('h:i A') }}
                            @if($v->checkin_distance_m !== null)
                                <span class="text-ink-900/50">({{ $v->checkin_distance_m }}m)</span>
                            @endif
                        @else
                            <span class="text-ink-900/40">Not yet</span>
                        @endif
                    </td>
                    <td>
                        <span class="px-2 py-1 rounded-full text-xs {{ $v->outcome_badge }}">{{ $v->outcome }}</span>
                        @if($v->token_amount)
                            <span class="text-xs text-emerald-700 font-bold ml-1">₹{{ number_format($v->token_amount) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-ink-900/50">No visits scheduled today.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- This week's performance --}}
@if($weekStats->count())
<div>
    <h2 class="font-display font-bold text-xl mb-3">This week's performance</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($weekStats as $stat)
            <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
                <div class="font-display font-bold text-lg">{{ $stat->fieldExecutive?->name }}</div>
                <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                    <div>
                        <div class="font-display font-black text-2xl">{{ $stat->total }}</div>
                        <div class="text-xs text-ink-900/60">Visits</div>
                    </div>
                    <div>
                        <div class="font-display font-black text-2xl text-emerald-700">{{ $stat->closed }}</div>
                        <div class="text-xs text-ink-900/60">Closed</div>
                    </div>
                    <div>
                        <div class="font-display font-black text-xl text-coral-700">₹{{ number_format($stat->tokens ?? 0) }}</div>
                        <div class="text-xs text-ink-900/60">Tokens</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection