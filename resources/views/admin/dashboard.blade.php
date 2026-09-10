@extends('layouts.dashboard')
@section('title', 'Admin Dashboard')
@section('content')

<h1 class="font-display font-black text-4xl mb-2">Admin Dashboard</h1>
<p class="text-ink-900/60">Overview of your business at a glance.</p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
    @foreach([
        ['Total properties', $stats['total_properties'], 'bg-ink-900 text-cream'],
        ['Active', $stats['active_properties'], 'bg-emerald-100 text-emerald-900'],
        ['Pending verification', $stats['pending_verification'], 'bg-amber-100 text-amber-900'],
        ['Total leads', number_format($stats['total_leads']), 'bg-coral-50 text-coral-900'],
        ['New leads', $stats['new_leads'], 'bg-sky-100 text-sky-900'],
        ['Closed (won)', $stats['closed_won'], 'bg-emerald-200 text-emerald-900'],
        ['Conversion %', $stats['conversion_rate'].'%', 'bg-violet-100 text-violet-900'],
        ['Active telecallers', $stats['telecallers'], 'bg-cream text-ink-900 border border-ink-900/10'],
    ] as [$label, $value, $cls])
        <div class="p-5 rounded-2xl {{ $cls }}">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ $label }}</div>
            <div class="font-display font-black text-3xl mt-2">{{ $value }}</div>
        </div>
    @endforeach
</div>

{{-- Lead Routing Engine Stats --}}
<div class="mt-8 p-6 rounded-2xl bg-gradient-to-br from-ink-900 to-ink-950 text-cream">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs uppercase tracking-wider text-coral-400">🎯 Lead Routing Engine</div>
            <h2 class="font-display font-bold text-2xl mt-1">Smart matching is active</h2>
            <p class="text-cream/70 text-sm mt-2">
                Every lead is auto-scored against owners' properties using location (40%), budget (30%), gender (20%), and availability (10%).
                Owners see leads sorted by relevance, not chronology.
            </p>
        </div>
        <div class="text-right">
            <div class="text-4xl">🤖</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-10">
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4">Leads by status</h2>
        <div class="space-y-2">
            @foreach($leadsByStatus as $status => $count)
                <div class="flex justify-between items-center text-sm">
                    <span class="capitalize">{{ str_replace('_', ' ', $status) }}</span>
                    <span class="font-bold">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4">Recent leads</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-ink-900/60 text-xs uppercase">
                    <tr><th class="py-2">Name</th><th>Property</th><th>Status</th><th>When</th></tr>
                </thead>
                <tbody>
                @foreach($recentLeads as $lead)
                    <tr class="border-t border-ink-900/5">
                        <td class="py-3"><div class="font-semibold">{{ $lead->name }}</div><div class="text-xs text-ink-900/50">{{ $lead->phone }}</div></td>
                        <td>{{ $lead->property?->name ?? '—' }}</td>
                        <td><span class="inline-block px-2 py-1 rounded-full text-xs font-medium {{ $lead->statusBadge() }}">{{ str_replace('_',' ', $lead->status) }}</span></td>
                        <td class="text-ink-900/60 text-xs">{{ $lead->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('admin.leads.index') }}" class="inline-block mt-4 text-coral-600 font-semibold text-sm">View all →</a>
    </div>
</div>




{{-- Unmatched leads alert --}}
@if($unmatchedLeads->count())
<div class="mt-10 p-6 rounded-2xl bg-amber-50 border-2 border-amber-200">
    <div class="flex items-start gap-3">
        <span class="text-3xl">⚠️</span>
        <div class="flex-1">
            <h2 class="font-display font-bold text-xl text-amber-900">
                {{ $unmatchedLeads->count() }} unmatched leads (last 7 days)
            </h2>
            <p class="text-sm text-amber-900/80 mt-1">
                These leads couldn't find a good property match. Opportunity to onboard new PG owners in these locations!
            </p>

            <div class="mt-4 space-y-2 max-h-64 overflow-y-auto">
                @foreach($unmatchedLeads->take(10) as $lead)
                    <div class="bg-white px-4 py-3 rounded-lg flex items-center justify-between text-sm">
                        <div>
                            <div class="font-semibold">{{ $lead->name }}</div>
                            <div class="text-xs text-ink-900/60">
                                📍 {{ $lead->preferred_locality ?? '—' }} {{ $lead->preferred_city ? ", {$lead->preferred_city}" : '' }}
                                · 💰 ₹{{ number_format($lead->budget_max ?? 0) }}
                                · {{ ucfirst($lead->preferred_gender ?? 'any') }}
                            </div>
                        </div>
                        <span class="text-xs text-ink-900/50">{{ $lead->created_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

@endsection
