@extends('layouts.dashboard')
@section('title', 'My Matched Leads')
@section('content')

<div class="flex items-center justify-between mb-2">
    <div>
        <h1 class="font-display font-black text-3xl">Matched leads</h1>
        <p class="text-ink-900/60 mt-1">Smart-matched to your properties by location, budget, and gender preference.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('owner.wallet') }}" class="px-4 py-2.5 bg-ink-900 text-cream rounded-xl text-sm">
            💰 <strong>{{ number_format($wallet->balance) }}</strong> credits
        </a>
        <a href="{{ route('owner.packages') }}" class="px-4 py-2.5 bg-coral-500 text-white rounded-xl text-sm font-semibold">+ Buy Credits</a>
    </div>
</div>

{{-- Pricing strip --}}
<div class="flex flex-wrap gap-3 mb-6 mt-4 text-xs">
    <span class="text-ink-900/60">Unlock cost:</span>
    @foreach($pricing as $type => $cost)
        <span class="px-3 py-1 rounded-full bg-cream border border-ink-900/10">
            <strong class="capitalize">{{ $type }}</strong>: {{ $cost }} credits
        </span>
    @endforeach
</div>

{{-- Tabs --}}
<div class="bg-white p-1.5 rounded-xl border border-ink-900/10 inline-flex gap-1 mb-6">
    @foreach([
        'all' => ['All', $stats['total']],
        'hot' => ['🔥 Hot', $stats['hot']],
        'affordable' => ['💰 Affordable', $stats['affordable']],
        'unlocked' => ['✓ Unlocked', $stats['unlocked']],
    ] as $tabKey => [$label, $count])
        <a href="?tab={{ $tabKey }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $tab === $tabKey ? 'bg-ink-900 text-cream' : 'text-ink-900/70 hover:bg-ink-900/5' }}">
            {{ $label }} <span class="opacity-60">({{ $count }})</span>
        </a>
    @endforeach
</div>

@if($paginated->count())
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($paginated as $lead)
            <div class="bg-white p-6 rounded-2xl border-2 {{ $lead->is_unlocked ? 'border-emerald-300' : ($lead->match_score >= 70 ? 'border-coral-300' : 'border-ink-900/10') }} relative">

                {{-- Match score badge (top right) --}}
                <div class="absolute top-4 right-4 flex flex-col items-end gap-1">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $lead->badge_info['class'] }}">
                        {{ $lead->badge_info['label'] }}
                    </span>
                    <span class="text-xs text-ink-900/40 font-mono">Score: {{ $lead->match_score }}/100</span>
                </div>

                <div class="pr-32">
                    <h3 class="font-display font-bold text-xl">{{ $lead->name }}</h3>
                    <p class="text-xs text-ink-900/50 mt-0.5">
                        {{ $lead->matched_property?->name ?? 'General inquiry' }}
                        · {{ $lead->source_label ?? ucfirst($lead->source) }}
                    </p>
                </div>

                {{-- Lead requirements --}}
                <div class="grid grid-cols-2 gap-2 mt-4 text-xs">
                    @if($lead->preferred_city || $lead->preferred_locality)
                        <div class="flex items-center gap-1 text-ink-900/70">
                            📍 {{ $lead->preferred_locality }} {{ $lead->preferred_city ? ", {$lead->preferred_city}" : '' }}
                        </div>
                    @endif
                    @if($lead->budget_min || $lead->budget_max)
                        <div class="flex items-center gap-1 text-ink-900/70">
                            💰 ₹{{ number_format($lead->budget_min ?? 0) }} - ₹{{ number_format($lead->budget_max ?? 0) }}
                        </div>
                    @endif
                    @if($lead->preferred_gender)
                        <div class="flex items-center gap-1 text-ink-900/70 capitalize">
                            👤 {{ $lead->preferred_gender }}
                        </div>
                    @endif
                    @if($lead->move_in_date)
                        <div class="flex items-center gap-1 text-ink-900/70">
                            📅 {{ $lead->move_in_date->format('d M') }}
                        </div>
                    @endif
                </div>

                {{-- Contact details --}}
                <div class="grid grid-cols-2 gap-3 text-sm mt-4 pb-4 border-b border-ink-900/5">
                    <div>
                        <div class="text-xs text-ink-900/50 uppercase">Phone</div>
                        @if($lead->is_unlocked)
                            <div class="font-bold text-emerald-700">📞 {{ $lead->phone }}</div>
                        @else
                            <div class="font-mono text-ink-900/40">🔒 {{ $lead->masked_phone }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs text-ink-900/50 uppercase">Email</div>
                        @if($lead->is_unlocked)
                            <div class="text-emerald-700 truncate text-xs">{{ $lead->email ?? '—' }}</div>
                        @else
                            <div class="font-mono text-ink-900/40 truncate text-xs">🔒 {{ $lead->masked_email ?? '—' }}</div>
                        @endif
                    </div>
                </div>

                @if($lead->message)
                    <p class="text-xs text-ink-900/70 mt-3 italic">"{{ Str::limit($lead->message, 90) }}"</p>
                @endif

                <div class="flex justify-between items-center mt-4">
                    <span class="text-xs text-ink-900/50">{{ $lead->created_at->diffForHumans() }}</span>

                    @if($lead->is_unlocked)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}?text={{ urlencode('Hi ' . $lead->name . ', this is regarding your PG inquiry...') }}" target="_blank"
                           class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold">
                            💬 WhatsApp
                        </a>
                    @elseif($lead->can_afford)
                        <form method="POST" action="{{ route('owner.leads.unlock', $lead) }}">
                            @csrf
                            <button class="px-4 py-2 bg-coral-500 hover:bg-coral-600 text-white rounded-lg text-sm font-bold"
                                    onclick="return confirm('Unlock this lead for {{ $lead->unlock_cost }} credits?\n\nMatch score: {{ $lead->match_score }}/100')">
                                🔓 Unlock for {{ $lead->unlock_cost }} credits
                            </button>
                        </form>
                    @else
                        <a href="{{ route('owner.packages') }}" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-bold">
                            ⚠ Recharge ({{ $lead->unlock_cost }} needed)
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">{{ $paginated->links() }}</div>
@else
    <div class="bg-white p-12 rounded-2xl border border-ink-900/10 text-center">
        <div class="text-6xl mb-4">🎯</div>
        <h3 class="font-display font-bold text-xl">No matched leads in this view</h3>
        <p class="text-ink-900/50 mt-2">Try a different tab, or wait for new inquiries.</p>
        @if($stats['total'] === 0)
            <p class="text-sm text-ink-900/60 mt-6">
                💡 Tip: Make sure your properties have correct location, gender, and rent details set — that's how our matching engine connects you with the right tenants.
            </p>
        @endif
    </div>
@endif

@endsection