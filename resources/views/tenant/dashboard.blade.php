@extends('layouts.tenant')
@section('title', 'My Dashboard')
@section('content')

<h1 class="font-display font-black text-4xl text-ink-950">Welcome, {{ auth()->user()->name }} <i class="fa-solid fa-hand fa-fw"></i></h1>
<p class="text-ink-900/60 mt-2">Aapka tenant portal — sab ek jagah pe.</p>

{{-- STATS --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
    <div class="bg-rose-50 border border-rose-200 p-5 rounded-2xl">
        <div class="text-xs uppercase tracking-wide text-rose-700 font-bold">Pending Rent</div>
        <div class="font-display font-black text-3xl text-rose-900 mt-2">₹{{ number_format($stats['pending_rent']) }}</div>
    </div>
    <div class="bg-emerald-50 border border-emerald-200 p-5 rounded-2xl">
        <div class="text-xs uppercase tracking-wide text-emerald-700 font-bold">Total Paid</div>
        <div class="font-display font-black text-3xl text-emerald-900 mt-2">₹{{ number_format($stats['paid_total']) }}</div>
    </div>
    <div class="bg-amber-50 border border-amber-200 p-5 rounded-2xl">
        <div class="text-xs uppercase tracking-wide text-amber-700 font-bold">Open Complaints</div>
        <div class="font-display font-black text-3xl text-amber-900 mt-2">{{ $stats['open_complaints'] }}</div>
    </div>
    <div class="bg-violet-50 border border-violet-200 p-5 rounded-2xl">
        <div class="text-xs uppercase tracking-wide text-violet-700 font-bold">Months Stayed</div>
        <div class="font-display font-black text-3xl text-violet-900 mt-2">{{ $stats['months_stayed'] }}</div>
    </div>
</div>

{{-- PROPERTY INFO --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-10">
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-house fa-fw"></i> My PG</h2>
        @if($tenant->property)
            <div class="flex gap-4 items-start">
                @if($tenant->property->cover_image)
                    <img src="{{ asset('storage/' . $tenant->property->cover_image) }}" class="w-32 h-32 rounded-xl object-cover">
                @endif
                <div class="flex-1">
                    <h3 class="font-bold text-lg">{{ $tenant->property->name }}</h3>
                    <p class="text-sm text-ink-900/60">{{ $tenant->property->address_line }}</p>
                    <p class="text-sm text-ink-900/60">{{ $tenant->property->locality?->name }}, {{ $tenant->property->city?->name }}</p>
               @if($tenant->room_number)
                        <div class="mt-3 px-3 py-1 bg-coral-50 text-coral-700 rounded-full text-xs font-bold inline-block">
                            <i class="fa-solid fa-bed fa-fw"></i> Room: {{ $tenant->room_number }}{{ $tenant->bed_number ? ' / Bed '.$tenant->bed_number : '' }}
                        </div>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('tenant.room') }}" class="text-coral-600 text-sm font-bold">View room details →</a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- CURRENT BILL --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-sack-dollar fa-fw"></i> Current Bill</h2>
        @if($currentBill)
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span>Month:</span> <strong>{{ $currentBill->month_label }}</strong></div>
                <div class="flex justify-between"><span>Amount:</span> <strong>₹{{ number_format($currentBill->due_amount) }}</strong></div>
                <div class="flex justify-between"><span>Due:</span> <strong>{{ \Carbon\Carbon::parse($currentBill->due_date)->format('d M') }}</strong></div>
                <div class="flex justify-between"><span>Status:</span> <span class="font-bold uppercase text-amber-700">{{ $currentBill->status }}</span></div>
            </div>
            <a href="{{ route('tenant.pay-rent', $currentBill->id) }}" class="block mt-4 text-center py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
                Pay Now →
            </a>
        @else
            <p class="text-emerald-700 font-bold"><i class="fa-solid fa-circle-check fa-fw"></i> No pending bills!</p>
        @endif
    </div>
</div>

{{-- RECENT COMPLAINTS --}}
<div class="bg-white p-6 rounded-2xl border border-ink-900/10 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="font-display font-bold text-xl"><i class="fa-solid fa-screwdriver-wrench fa-fw"></i> Recent Complaints</h2>
        <a href="{{ route('tenant.complaints.create') }}" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold text-sm">+ New Complaint</a>
    </div>
    @forelse($recentComplaints as $c)
        <div class="flex justify-between items-center py-3 border-t border-ink-900/5 first:border-t-0">
            <div>
                <div class="font-semibold">{{ $c->title }}</div>
                <div class="text-xs text-ink-900/50">{{ $c->created_at->diffForHumans() }}</div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                {{ $c->status === 'new' ? 'bg-amber-100 text-amber-700' : '' }}
                {{ $c->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                {{ $c->status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                {{ str_replace('_', ' ', $c->status) }}
            </span>
        </div>
    @empty
        <p class="text-ink-900/50 text-center py-8">No complaints yet. Aap ekdum settled ho! <i class="fa-solid fa-champagne-glasses fa-fw"></i></p>
    @endforelse
</div>

@endsection