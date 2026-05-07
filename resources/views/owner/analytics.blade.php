@extends('layouts.dashboard')
@section('title', 'My Analytics')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

<h1 class="font-display font-black text-3xl mb-2">📊 My Analytics</h1>
<p class="text-ink-900/60">How your properties are performing</p>

{{-- KPI cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 mb-8">
    <div class="p-5 rounded-2xl bg-coral-500 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Total Leads</div>
        <div class="font-display font-black text-3xl mt-2">{{ $totals['total_leads'] }}</div>
    </div>
    <div class="p-5 rounded-2xl bg-emerald-500 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Closed Deals</div>
        <div class="font-display font-black text-3xl mt-2">{{ $totals['closed_leads'] }}</div>
    </div>
    <div class="p-5 rounded-2xl bg-ink-900 text-cream">
        <div class="text-xs uppercase tracking-wide opacity-70">Credits Spent</div>
        <div class="font-display font-black text-3xl mt-2">{{ number_format($totals['total_spent']) }}</div>
    </div>
    <div class="p-5 rounded-2xl bg-violet-500 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Current Balance</div>
        <div class="font-display font-black text-3xl mt-2">{{ number_format($totals['current_balance']) }}</div>
    </div>
</div>

{{-- Property performance --}}
<div class="bg-white p-6 rounded-2xl border border-ink-900/10 mb-6">
    <h2 class="font-display font-bold text-xl mb-4">🏠 Property performance</h2>
    @forelse($properties as $p)
        <div class="flex items-center justify-between py-3 border-b border-ink-900/5 last:border-0">
            <div>
                <div class="font-semibold">{{ $p->name }}</div>
                <div class="text-xs text-ink-900/60">📍 {{ $p->locality?->name }}</div>
            </div>
            <div class="text-right">
                <div class="text-sm">
                    <strong>{{ $p->leads_count }}</strong> leads ·
                    <strong class="text-emerald-700">{{ $p->closed_leads_count }}</strong> closed
                </div>
                <div class="text-xs text-ink-900/60 mt-1">{{ $p->view_count }} views</div>
            </div>
        </div>
    @empty
        <p class="text-ink-900/50">No properties listed yet. <a href="{{ route('owner.properties.create') }}" class="text-coral-600 font-semibold">+ Add property</a></p>
    @endforelse
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Credit usage --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">💰 Credit usage (6 months)</h2>
        <p class="text-sm text-ink-900/60 mb-4">Monthly credit spending</p>
        <div class="h-64"><canvas id="creditChart"></canvas></div>
    </div>

    {{-- Lead types --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">🎯 Lead types received</h2>
        <p class="text-sm text-ink-900/60 mb-4">Quality breakdown</p>
        <div class="h-64"><canvas id="typesChart"></canvas></div>
    </div>
</div>

@push('scripts')
<script>
new Chart(document.getElementById('creditChart'), {
    type: 'bar',
    data: {
        labels: @json($credit_usage['labels']),
        datasets: [{
            label: 'Credits Spent',
            data: @json($credit_usage['data']),
            backgroundColor: '#ff6b5b',
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('typesChart'), {
    type: 'doughnut',
    data: {
        labels: @json(array_map('ucfirst', $lead_types['labels'])),
        datasets: [{
            data: @json($lead_types['data']),
            backgroundColor: ['#0f2748', '#ff6b5b', '#10b981', '#8b5cf6'],
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>
@endpush

@endsection