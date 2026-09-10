@extends('layouts.dashboard')
@section('title', 'Chat Analytics')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">💬 Chat Analytics</h1>
        <p class="text-ink-900/60 mt-1">Pizi AI Assistant — usage &amp; conversations</p>
    </div>
    <button onclick="window.location.reload()" class="px-4 py-2 bg-ink-900 text-cream rounded-lg text-sm">🔄 Refresh</button>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
    <div class="p-5 rounded-2xl bg-gradient-to-br from-coral-500 to-coral-600 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Total Sessions</div>
        <div class="font-display font-black text-3xl mt-2">{{ number_format($totalSessions) }}</div>
    </div>
    <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white">
        <div class="text-xs uppercase tracking-wide opacity-80">Total Messages</div>
        <div class="font-display font-black text-3xl mt-2">{{ number_format($totalMessages) }}</div>
    </div>
    <div class="p-5 rounded-2xl bg-gradient-to-br from-ink-900 to-ink-950 text-cream">
        <div class="text-xs uppercase tracking-wide opacity-80">Messages Today</div>
        <div class="font-display font-black text-3xl mt-2">{{ number_format($messagesToday) }}</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    {{-- Messages over time --}}
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">Messages — last 7 days</h2>
        <p class="text-sm text-ink-900/60 mb-4">Daily message volume across all conversations</p>
        <canvas id="chatVolumeChart" height="90"></canvas>
    </div>

    {{-- Language split --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-1">Language split</h2>
        <p class="text-sm text-ink-900/60 mb-4">Sessions by preferred language</p>
        <canvas id="chatLangChart"></canvas>
        @if($languageBreakdown->isEmpty())
            <p class="text-sm text-ink-900/40 text-center mt-6">No sessions yet.</p>
        @endif
    </div>
</div>

{{-- Recent conversations --}}
<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="p-6 border-b border-ink-900/8">
        <h2 class="font-display font-bold text-xl">Recent conversations</h2>
        <p class="text-sm text-ink-900/60 mt-1">Last 15 chat sessions</p>
    </div>
    <div class="divide-y divide-ink-900/5">
        @forelse($recentSessions as $session)
            <div class="p-5 flex items-center justify-between gap-4 hover:bg-cream/50 transition">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm">{{ $session->customer_name ?? 'Guest' }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-ink-100 text-ink-700 uppercase">{{ $session->language ?? 'en' }}</span>
                    </div>
                    <p class="text-sm text-ink-900/60 mt-1 truncate max-w-lg">
                        {{ $session->messages->first()?->message ?? 'No messages yet' }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-xs text-ink-900/40">{{ $session->last_activity_at?->diffForHumans() ?? $session->created_at->diffForHumans() }}</div>
                    <div class="text-xs font-semibold text-coral-600 mt-1">{{ $session->messages_count }} messages</div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-ink-900/50">
                No conversations yet. Once visitors start chatting with the AI Assistant, they'll show up here.
            </div>
        @endforelse
    </div>
</div>

<script>
new Chart(document.getElementById('chatVolumeChart'), {
    type: 'line',
    data: {
        labels: @json($chartLabels),
        datasets: [{
            label: 'Messages',
            data: @json($chartData),
            borderColor: '#FF6B5B',
            backgroundColor: 'rgba(255,107,91,0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointBackgroundColor: '#FF6B5B',
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

@if($languageBreakdown->isNotEmpty())
new Chart(document.getElementById('chatLangChart'), {
    type: 'doughnut',
    data: {
        labels: @json($languageBreakdown->keys()->map(fn($l) => strtoupper($l ?: 'EN'))),
        datasets: [{
            data: @json($languageBreakdown->values()),
            backgroundColor: ['#FF6B5B', '#0F2748', '#10B981', '#F59E0B'],
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});
@endif
</script>

@endsection
