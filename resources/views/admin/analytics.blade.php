@extends('layouts.dashboard')
@section('title', 'Analytics')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">📊 Analytics</h1>
        <p class="text-ink-900/60 mt-1">Business insights · Last 30 days</p>
    </div>
    <button onclick="window.location.reload()" class="px-4 py-2 bg-ink-900 text-cream rounded-lg text-sm">🔄 Refresh</button>
</div>

{{-- Top KPI cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Revenue (30d)</div>
        <div class="font-display font-black text-3xl mt-2">₹{{ number_format($revenue['total']) }}</div>
        <div class="text-xs opacity-70 mt-1">{{ array_sum($revenue['counts']) }} transactions</div>
    </div>
    <div class="p-5 rounded-2xl bg-gradient-to-br from-coral-500 to-coral-600 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Total Leads</div>
        <div class="font-display font-black text-3xl mt-2">{{ $funnel[0]['count'] ?? 0 }}</div>
        <div class="text-xs opacity-70 mt-1">All time</div>
    </div>
    <div class="p-5 rounded-2xl bg-gradient-to-br from-violet-500 to-violet-600 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Closed Won</div>
        <div class="font-display font-black text-3xl mt-2">{{ end($funnel)['count'] ?? 0 }}</div>
        @php
            $totalLeads = $funnel[0]['count'] ?? 1;
            $closed = end($funnel)['count'] ?? 0;
            $convRate = $totalLeads > 0 ? round(($closed / $totalLeads) * 100, 1) : 0;
        @endphp
        <div class="text-xs opacity-70 mt-1">{{ $convRate }}% conversion</div>
    </div>
    <div class="p-5 rounded-2xl bg-gradient-to-br from-ink-900 to-ink-950 text-cream">
        <div class="text-xs uppercase tracking-wide opacity-80">Avg Daily Revenue</div>
        <div class="font-display font-black text-3xl mt-2">₹{{ number_format(round($revenue['total'] / 30)) }}</div>
        <div class="text-xs opacity-70 mt-1">Per day · 30d avg</div>
    </div>
</div>

{{-- Revenue chart --}}
<div class="bg-white p-6 rounded-2xl border border-ink-900/10 mb-6">
    <h2 class="font-display font-bold text-xl mb-1">Revenue trend</h2>
    <p class="text-sm text-ink-900/60 mb-4">Daily revenue from credit purchases</p>
    <div class="h-72"><canvas id="revenueChart"></canvas></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Lead funnel --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">Lead conversion funnel</h2>
        <p class="text-sm text-ink-900/60 mb-4">Where leads drop off</p>
        <div class="space-y-2">
            @php $maxCount = collect($funnel)->max('count') ?: 1; @endphp
            @foreach($funnel as $i => $stage)
                @php $pct = ($stage['count'] / $maxCount) * 100; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-semibold">{{ $stage['stage'] }}</span>
                        <span class="text-ink-900/60">{{ $stage['count'] }}</span>
                    </div>
                    <div class="h-8 bg-ink-900/5 rounded-lg overflow-hidden">
                        <div class="h-full rounded-lg transition-all" style="width: {{ $pct }}%; background: linear-gradient(90deg, #ff6b5b 0%, #0f2748 100%);"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Lead sources pie --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">Lead sources</h2>
        <p class="text-sm text-ink-900/60 mb-4">Where leads come from</p>
        <div class="h-64"><canvas id="sourceChart"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Top cities --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">Top cities by leads</h2>
        <p class="text-sm text-ink-900/60 mb-4">Where the demand is</p>
        <div class="h-64"><canvas id="citiesChart"></canvas></div>
    </div>

    {{-- Conversion trend --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">Conversion rate trend</h2>
        <p class="text-sm text-ink-900/60 mb-4">Daily % of leads closed</p>
        <div class="h-64"><canvas id="conversionChart"></canvas></div>
    </div>
</div>

{{-- Leaderboards --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4">💰 Top spending owners</h2>
        @forelse($topSpenders as $i => $owner)
            <div class="flex items-center justify-between py-3 border-b border-ink-900/5 last:border-0">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-bold text-ink-900/40">#{{ $i + 1 }}</span>
                    <div>
                        <div class="font-semibold">{{ $owner['name'] }}</div>
                        <div class="text-xs text-ink-900/60">{{ $owner['email'] }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-display font-bold text-lg">{{ number_format($owner['wallet']['lifetime_spent'] ?? 0) }}</div>
                    <div class="text-xs text-ink-900/60">credits spent</div>
                </div>
            </div>
        @empty
            <p class="text-ink-900/50 text-sm">No data yet.</p>
        @endforelse
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4">🏆 Top performing owners</h2>
        @forelse($topPerformers as $i => $owner)
            <div class="flex items-center justify-between py-3 border-b border-ink-900/5 last:border-0">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-bold text-ink-900/40">#{{ $i + 1 }}</span>
                    <div>
                        <div class="font-semibold">{{ $owner['name'] }}</div>
                        <div class="text-xs text-ink-900/60">{{ $owner['email'] }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-display font-bold text-lg text-emerald-700">{{ $owner['closed_deals'] }}</div>
                    <div class="text-xs text-ink-900/60">deals closed</div>
                </div>
            </div>
        @empty
            <p class="text-ink-900/50 text-sm">No data yet.</p>
        @endforelse
    </div>
</div>

{{-- Field exec performance --}}
@if(count($fieldExecs))
<div class="bg-white p-6 rounded-2xl border border-ink-900/10">
    <h2 class="font-display font-bold text-xl mb-4">🚗 Field executive performance (this month)</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($fieldExecs as $exec)
            <div class="p-4 bg-cream rounded-xl border border-ink-900/10">
                <div class="font-display font-bold">{{ $exec['name'] }}</div>
                <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                    <div>
                        <div class="font-display font-black text-2xl">{{ $exec['month_total'] }}</div>
                        <div class="text-xs text-ink-900/60">Visits</div>
                    </div>
                    <div>
                        <div class="font-display font-black text-2xl text-emerald-700">{{ $exec['month_closed'] }}</div>
                        <div class="text-xs text-ink-900/60">Closed</div>
                    </div>
                    <div>
                        <div class="font-display font-black text-2xl text-coral-600">{{ $exec['conversion_rate'] }}%</div>
                        <div class="text-xs text-ink-900/60">Rate</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@push('scripts')
<script>
const labelStyle = { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#6b7280' };
Chart.defaults.font.family = 'Plus Jakarta Sans';

// Revenue chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: @json($revenue['labels']),
        datasets: [{
            label: 'Revenue (₹)',
            data: @json($revenue['revenue']),
            borderColor: '#ff6b5b',
            backgroundColor: 'rgba(255, 107, 91, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: (v) => '₹' + v.toLocaleString() } },
        }
    }
});

// Source pie
new Chart(document.getElementById('sourceChart'), {
    type: 'doughnut',
    data: {
        labels: @json($sources['labels']),
        datasets: [{
            data: @json($sources['data']),
            backgroundColor: ['#ff6b5b', '#0f2748', '#10b981', '#8b5cf6', '#f59e0b', '#3b82f6', '#ec4899'],
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
});

// Cities bar
new Chart(document.getElementById('citiesChart'), {
    type: 'bar',
    data: {
        labels: @json($cities['labels']),
        datasets: [{
            label: 'Leads',
            data: @json($cities['data']),
            backgroundColor: '#0f2748',
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});

// Conversion rate
new Chart(document.getElementById('conversionChart'), {
    type: 'line',
    data: {
        labels: @json($conversion['labels']),
        datasets: [{
            label: 'Conversion %',
            data: @json($conversion['data']),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } }
    }
});
</script>
@endpush

@endsection