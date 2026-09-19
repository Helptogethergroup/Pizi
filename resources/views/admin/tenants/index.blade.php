@extends('layouts.dashboard')
@section('title', 'All Tenants — Admin')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl"><i class="fa-solid fa-users fa-fw"></i> All Tenants</h1>
    <p class="text-ink-900/60 mt-1">Platform-wide tenant management</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-ink-100">
        <div class="text-xs text-ink-500 uppercase font-bold">Total Tenants</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-emerald-200">
        <div class="text-xs text-emerald-700 uppercase font-bold">Active</div>
        <div class="font-display font-black text-3xl text-emerald-700 mt-1">{{ $stats['active'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-amber-200">
        <div class="text-xs text-amber-700 uppercase font-bold">Pending KYC</div>
        <div class="font-display font-black text-3xl text-amber-700 mt-1">{{ $stats['pending_kyc'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-blue-200">
        <div class="text-xs text-blue-700 uppercase font-bold">KYC Verified</div>
        <div class="font-display font-black text-3xl text-blue-700 mt-1">{{ $stats['approved_kyc'] }}</div>
    </div>
</div>

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Search name/phone/email..." class="flex-1 min-w-[180px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <select name="owner_id" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Owners</option>
        @foreach($owners as $o)
            <option value="{{ $o->id }}" @selected(request('owner_id') == $o->id)>{{ $o->name }}</option>
        @endforeach
    </select>
    <select name="status" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Status</option>
        <option value="active" @selected(request('status')==='active')>Active</option>
        <option value="notice_period" @selected(request('status')==='notice_period')>Notice</option>
        <option value="left" @selected(request('status')==='left')>Left</option>
        <option value="blacklisted" @selected(request('status')==='blacklisted')>Blacklisted</option>
    </select>
    <select name="kyc" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All KYC</option>
        <option value="pending" @selected(request('kyc')==='pending')>Pending</option>
        <option value="submitted" @selected(request('kyc')==='submitted')>Submitted</option>
        <option value="approved" @selected(request('kyc')==='approved')>Approved</option>
        <option value="rejected" @selected(request('kyc')==='rejected')>Rejected</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>
</form>

@if($tenants->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-users fa-fw"></i></div>
        <p class="text-ink-700">No tenants found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($tenants as $tenant)
            <a href="{{ route('admin.tenants.show', $tenant) }}" class="block bg-white p-4 rounded-2xl border border-ink-100 hover:border-coral-300 transition">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="w-12 h-12 rounded-full bg-coral-100 text-coral-700 flex items-center justify-center font-bold text-lg">
                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-bold">{{ $tenant->name }}</h3>
                            
                            @if($tenant->kyc_status === 'approved')
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">✓ KYC</span>
                            @elseif($tenant->kyc_status === 'submitted')
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-clipboard-list fa-fw"></i> Review</span>
                            @elseif($tenant->kyc_status === 'rejected')
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-circle-xmark fa-fw"></i> Rejected</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-hourglass-half fa-fw"></i> Pending</span>
                            @endif

                            @if($tenant->status !== 'active')
                                <span class="text-xs bg-ink-200 text-ink-700 px-2 py-0.5 rounded-full font-bold capitalize">{{ str_replace('_', ' ', $tenant->status) }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs text-ink-700 flex-wrap">
                            <span><i class="fa-solid fa-phone fa-fw"></i> {{ $tenant->phone }}</span>
                            <span><i class="fa-solid fa-house fa-fw"></i> {{ $tenant->property?->name }}</span>
                            <span><i class="fa-solid fa-user fa-fw"></i> Owner: <strong>{{ $tenant->owner?->name }}</strong></span>
                        </div>
                    </div>

                    <div class="text-2xl text-ink-300">→</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $tenants->links() }}</div>
@endif

@endsection