@extends('layouts.dashboard')
@section('title', 'PG Management Overview')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl">🏠 PG Management Overview</h1>
    <p class="text-ink-900/60 mt-1">Platform-wide tenant operations dashboard</p>
</div>

{{-- Main Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('admin.tenants.index') }}" class="bg-gradient-to-br from-blue-500 to-blue-600 p-5 rounded-2xl text-white hover:scale-[1.02] transition">
        <div class="text-xs uppercase font-bold opacity-80">Active Tenants</div>
        <div class="font-display font-black text-4xl mt-1">{{ $stats['active_tenants'] }}</div>
        <div class="text-xs opacity-70 mt-1">Total: {{ $stats['total_tenants'] }}</div>
    </a>

    <a href="{{ route('admin.tenants.index', ['kyc' => 'submitted']) }}" class="bg-gradient-to-br from-amber-500 to-amber-600 p-5 rounded-2xl text-white hover:scale-[1.02] transition">
        <div class="text-xs uppercase font-bold opacity-80">Pending KYC</div>
        <div class="font-display font-black text-4xl mt-1">{{ $stats['pending_kyc'] }}</div>
        <div class="text-xs opacity-70 mt-1">Need verification</div>
    </a>

    <a href="{{ route('admin.complaints.index', ['priority' => 'urgent']) }}" class="bg-gradient-to-br from-rose-500 to-rose-600 p-5 rounded-2xl text-white hover:scale-[1.02] transition">
        <div class="text-xs uppercase font-bold opacity-80">Urgent Complaints</div>
        <div class="font-display font-black text-4xl mt-1">{{ $stats['urgent_complaints'] }}</div>
        <div class="text-xs opacity-70 mt-1">Need immediate action</div>
    </a>

    <a href="{{ route('admin.rent.index') }}" class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 rounded-2xl text-white hover:scale-[1.02] transition">
        <div class="text-xs uppercase font-bold opacity-80">Collected This Month</div>
        <div class="font-display font-black text-3xl mt-1">₹{{ number_format($stats['collected_month'], 0) }}</div>
        <div class="text-xs opacity-70 mt-1">Platform revenue</div>
    </a>

    <a href="{{ route('admin.rent.index', ['status' => 'overdue']) }}" class="bg-gradient-to-br from-orange-500 to-orange-600 p-5 rounded-2xl text-white hover:scale-[1.02] transition">
        <div class="text-xs uppercase font-bold opacity-80">Pending Dues</div>
        <div class="font-display font-black text-3xl mt-1">₹{{ number_format($stats['pending_dues'], 0) }}</div>
        <div class="text-xs opacity-70 mt-1">{{ $stats['overdue_bills'] }} overdue bills</div>
    </a>

    <a href="{{ route('admin.complaints.index', ['status' => 'open']) }}" class="bg-gradient-to-br from-purple-500 to-purple-600 p-5 rounded-2xl text-white hover:scale-[1.02] transition">
        <div class="text-xs uppercase font-bold opacity-80">Open Complaints</div>
        <div class="font-display font-black text-4xl mt-1">{{ $stats['open_complaints'] }}</div>
        <div class="text-xs opacity-70 mt-1">{{ $stats['resolved_month'] }} resolved this month</div>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Top Owners --}}
    <div class="bg-white rounded-2xl border border-ink-100">
        <div class="p-5 border-b border-ink-100">
            <h2 class="font-display font-bold text-lg">🏆 Top Property Owners</h2>
        </div>
        <div class="divide-y divide-ink-100">
            @forelse($topOwners as $owner)
                <div class="p-4 flex items-center gap-3 hover:bg-cream transition">
                    <div class="w-10 h-10 rounded-full bg-coral-100 text-coral-700 flex items-center justify-center font-bold">
                        {{ strtoupper(substr($owner->name, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="font-bold">{{ $owner->name }}</div>
                        <div class="text-xs text-ink-700">{{ $owner->phone ?? $owner->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-display font-black text-lg text-coral-600">{{ $owner->tenants_count }}</div>
                        <div class="text-xs text-ink-500">tenants</div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-ink-500">No owners with tenants yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Urgent Complaints --}}
    <div class="bg-white rounded-2xl border border-rose-200">
        <div class="p-5 border-b border-rose-100 flex items-center justify-between">
            <h2 class="font-display font-bold text-lg">🚨 Urgent Complaints</h2>
            <a href="{{ route('admin.complaints.index', ['priority' => 'urgent']) }}" class="text-xs text-coral-500 font-bold">View All →</a>
        </div>
        <div class="divide-y divide-rose-50">
            @forelse($urgentComplaints as $c)
                <a href="{{ route('admin.complaints.show', $c) }}" class="block p-4 hover:bg-rose-50 transition">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="text-xs font-mono bg-rose-100 text-rose-700 px-2 py-0.5 rounded font-bold">{{ $c->ticket_number }}</span>
                        <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">🔴 Urgent</span>
                    </div>
                    <div class="font-bold text-sm">{{ $c->title }}</div>
                    <div class="text-xs text-ink-700 mt-1">{{ $c->tenant?->name }} · Owner: {{ $c->owner?->name }} · {{ $c->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="p-8 text-center text-ink-500">No urgent complaints. All good! 🎉</div>
            @endforelse
        </div>
    </div>

    {{-- Recent Tenants --}}
    <div class="bg-white rounded-2xl border border-ink-100">
        <div class="p-5 border-b border-ink-100 flex items-center justify-between">
            <h2 class="font-display font-bold text-lg">👥 Recent Tenants</h2>
            <a href="{{ route('admin.tenants.index') }}" class="text-xs text-coral-500 font-bold">View All →</a>
        </div>
        <div class="divide-y divide-ink-100">
            @forelse($recentTenants as $t)
                <a href="{{ route('admin.tenants.show', $t) }}" class="block p-4 hover:bg-cream transition">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="font-bold">{{ $t->name }}</div>
                        @if($t->kyc_status === 'approved')
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">✓ KYC</span>
                        @elseif($t->kyc_status === 'submitted')
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">📋 Review</span>
                        @elseif($t->kyc_status === 'rejected')
                            <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">❌ Rejected</span>
                        @endif
                    </div>
                    <div class="text-xs text-ink-700 mt-0.5">{{ $t->property?->name }} · {{ $t->owner?->name }} · {{ $t->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="p-8 text-center text-ink-500">No tenants yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Recent Bills --}}
    <div class="bg-white rounded-2xl border border-ink-100">
        <div class="p-5 border-b border-ink-100 flex items-center justify-between">
            <h2 class="font-display font-bold text-lg">💰 Recent Bills</h2>
            <a href="{{ route('admin.rent.index') }}" class="text-xs text-coral-500 font-bold">View All →</a>
        </div>
        <div class="divide-y divide-ink-100">
            @forelse($recentBills as $b)
                <a href="{{ route('admin.rent.show', $b) }}" class="block p-4 hover:bg-cream transition">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div>
                            <div class="font-bold text-sm">{{ $b->tenant?->name }}</div>
                            <div class="text-xs text-ink-700">{{ $b->month_label }} · Owner: {{ $b->owner?->name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold">₹{{ number_format($b->total_amount, 0) }}</div>
                            @if($b->status === 'paid')
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">✓ Paid</span>
                            @elseif($b->status === 'overdue')
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">⚠️ Overdue</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">Pending</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-ink-500">No bills yet.</div>
            @endforelse
        </div>
    </div>

</div>

@endsection