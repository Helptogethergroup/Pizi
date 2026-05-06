@extends('layouts.fieldexec')
@section('title', 'All Visits')
@section('content')

{{-- Filter pills --}}
<div class="flex gap-2 overflow-x-auto pb-2 mb-4 -mx-4 px-4">
    @foreach([
        'all' => 'All',
        'today' => 'Today',
        'pending' => 'Pending',
        'closed' => 'Closed',
        'this_week' => 'This week',
    ] as $key => $label)
        <a href="?filter={{ $key }}" class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap {{ $filter === $key ? 'bg-ink-950 text-cream' : 'bg-white text-ink-900/70 border border-ink-900/10' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@forelse($visits as $visit)
    <a href="{{ route('fieldexec.visits.show', $visit) }}" class="block bg-white p-4 rounded-xl border border-ink-900/10 mb-3">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="text-xs text-ink-900/50">{{ $visit->scheduled_at->format('d M, h:i A') }}</div>
                <div class="font-display font-bold mt-0.5">{{ $visit->lead?->name }}</div>
                <div class="text-xs text-ink-900/60">{{ $visit->property?->name }}</div>
                <div class="text-xs text-ink-900/50 mt-1">📍 {{ $visit->property?->locality?->name }}</div>
            </div>
            <div class="text-right">
                <span class="px-2 py-1 rounded-full text-xs {{ $visit->outcome_badge }}">{{ $visit->outcome }}</span>
                @if($visit->token_amount)
                    <div class="text-xs text-emerald-700 font-bold mt-1">₹{{ number_format($visit->token_amount) }}</div>
                @endif
            </div>
        </div>
    </a>
@empty
    <div class="bg-white p-12 rounded-2xl border border-ink-900/10 text-center">
        <p class="text-ink-900/60">No visits found.</p>
    </div>
@endforelse

<div class="mt-6">{{ $visits->links() }}</div>

@endsection