@extends('layouts.dashboard')
@section('title', 'All Agreements — Admin')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl"><i class="fa-solid fa-file-lines fa-fw"></i> All Rent Agreements</h1>
    <p class="text-ink-900/60 mt-1">Platform-wide agreement tracking</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-ink-100">
        <div class="text-xs text-ink-500 uppercase font-bold">Total</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-emerald-200">
        <div class="text-xs text-emerald-700 uppercase font-bold">Active</div>
        <div class="font-display font-black text-3xl text-emerald-700 mt-1">{{ $stats['active'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-amber-200">
        <div class="text-xs text-amber-700 uppercase font-bold">Expiring (30d)</div>
        <div class="font-display font-black text-3xl text-amber-700 mt-1">{{ $stats['expiring'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-ink-200">
        <div class="text-xs text-ink-700 uppercase font-bold">Drafts</div>
        <div class="font-display font-black text-3xl text-ink-700 mt-1">{{ $stats['draft'] }}</div>
    </div>
</div>

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Search..." class="flex-1 min-w-[180px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <select name="owner_id" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Owners</option>
        @foreach($owners as $o)
            <option value="{{ $o->id }}" @selected(request('owner_id') == $o->id)>{{ $o->name }}</option>
        @endforeach
    </select>
    <select name="status" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Status</option>
        <option value="draft" @selected(request('status')==='draft')>Draft</option>
        <option value="active" @selected(request('status')==='active')>Active</option>
        <option value="expired" @selected(request('status')==='expired')>Expired</option>
        <option value="terminated" @selected(request('status')==='terminated')>Terminated</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>
</form>

@if($agreements->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-file-lines fa-fw"></i></div>
        <p class="text-ink-700">No agreements found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($agreements as $agreement)
            <a href="{{ route('admin.agreements.show', $agreement) }}" class="block bg-white p-4 rounded-2xl border border-ink-100 hover:border-coral-300 transition">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="text-xs font-mono font-bold">{{ $agreement->agreement_number }}</span>
                            @if($agreement->status === 'active')
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">{{ $agreement->status_label }}</span>
                            @elseif(in_array($agreement->status, ['expired', 'terminated']))
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">{{ $agreement->status_label }}</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">{{ $agreement->status_label }}</span>
                            @endif
                        </div>
                        <h3 class="font-bold">{{ $agreement->tenant?->name }}</h3>
                        <div class="flex items-center gap-3 text-xs text-ink-700 mt-1 flex-wrap">
                            <span><i class="fa-solid fa-user fa-fw"></i> Owner: <strong>{{ $agreement->owner?->name }}</strong></span>
                            <span><i class="fa-solid fa-house fa-fw"></i> {{ $agreement->property?->name }}</span>
                            <span><i class="fa-solid fa-calendar-days fa-fw"></i> {{ $agreement->start_date->format('d/m/y') }} → {{ $agreement->end_date->format('d/m/y') }}</span>
                            <span class="font-bold text-coral-600">₹{{ number_format($agreement->monthly_rent) }}</span>
                        </div>
                    </div>
                    <div class="text-2xl text-ink-300">→</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $agreements->links() }}</div>
@endif

@endsection