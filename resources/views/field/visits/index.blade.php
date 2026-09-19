@extends('layouts.dashboard')
@section('title', 'My Visits')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl">My Visits</h1>
    <p class="text-ink-900/60 mt-1">All your assigned property visits</p>
</div>

<div class="bg-white rounded-xl border border-ink-100 p-2 mb-4 flex gap-1 overflow-x-auto">
    @foreach(['all' => 'All', 'scheduled' => '📅 Scheduled', 'in_progress' => '⏳ In Progress', 'completed' => '✅ Completed', 'cancelled' => '❌ Cancelled'] as $key => $label)
        <a href="{{ $key === 'all' ? route('field.visits.index') : route('field.visits.index', ['status' => $key]) }}"
           class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap {{ (request('status') === $key || ($key === 'all' && !request('status'))) ? 'bg-coral-500 text-white' : 'text-ink-700 hover:bg-cream' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if($visits->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-clipboard-list fa-fw"></i></div>
        <p class="text-ink-700">No visits found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($visits as $visit)
            @php $lead = $visit->related_lead; @endphp
            <div class="bg-white p-5 rounded-2xl border {{ $visit->is_missed ? 'border-rose-300 bg-rose-50/30' : 'border-ink-100 hover:border-coral-500 hover:shadow-md' }} transition">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <a href="{{ route('field.visits.show', $visit) }}" class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-xs font-bold uppercase px-2 py-0.5 rounded
                                @if($visit->status === 'completed') bg-emerald-100 text-emerald-700
                                @elseif($visit->status === 'in_progress') bg-amber-100 text-amber-700
                                @elseif($visit->status === 'scheduled') bg-blue-100 text-blue-700
                                @else bg-rose-100 text-rose-700 @endif">
                                {{ str_replace('_', ' ', $visit->status) }}
                            </span>
                            @if($visit->is_missed)
                                <span class="text-xs font-bold uppercase px-2 py-0.5 rounded bg-rose-600 text-white"><i class="fa-solid fa-circle fa-fw" style="color:#ef4444"></i> Missed</span>
                            @endif
                        </div>
                        <h3 class="font-display font-bold text-lg text-ink-950">{{ $visit->property?->name ?? '—' }}</h3>
                        <p class="text-sm text-ink-700 mt-1"><i class="fa-solid fa-location-dot fa-fw"></i> {{ $visit->property?->locality?->name }}, {{ $visit->property?->city?->name }}</p>
                    </a>
                    <div class="text-right flex flex-col items-end gap-2">
                        <div>
                            <div class="text-xs text-ink-500 uppercase">When</div>
                            <div class="font-bold text-ink-950">{{ $visit->scheduled_at->format('d M, h:i A') }}</div>
                        </div>
                        @if($lead?->phone)
                            <a href="tel:{{ $lead->phone }}" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $visits->links() }}</div>
@endif

@endsection