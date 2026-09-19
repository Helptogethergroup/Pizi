@extends('layouts.dashboard')
@section('title', 'My Tenants')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">My Tenants</h1>
        <p class="text-ink-900/60 mt-1">Manage all your PG tenants</p>
    </div>
    <a href="{{ route('owner.tenants.create') }}" class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold shadow-lg shadow-coral-500/30">+ Add Tenant</a>
</div>

<div class="flex gap-2 mb-6">
    <a href="{{ route('owner.tenants.index', ['filter' => 'mine']) }}"
       class="px-4 py-2 rounded-xl text-sm font-bold {{ request('filter', 'mine') === 'mine' ? 'bg-ink-950 text-cream' : 'bg-white border border-ink-200 text-ink-700' }}">
        My Tenants
    </a>
    <a href="{{ route('owner.tenants.index', ['filter' => 'unassigned']) }}"
       class="px-4 py-2 rounded-xl text-sm font-bold {{ request('filter') === 'unassigned' ? 'bg-ink-950 text-cream' : 'bg-white border border-ink-200 text-ink-700' }}">
        <i class="fa-solid fa-certificate fa-fw"></i> New Sign-ups Awaiting Assignment
    </a>
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
    <div class="bg-white p-5 rounded-2xl border border-rose-200">
        <div class="text-xs text-rose-700 uppercase font-bold">Notice Period</div>
        <div class="font-display font-black text-3xl text-rose-700 mt-1">{{ $stats['notice'] }}</div>
    </div>
</div>

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Name, phone, room..." class="flex-1 min-w-[200px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <select name="status" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Status</option>
        <option value="active" @selected(request('status')==='active')>Active</option>
        <option value="notice_period" @selected(request('status')==='notice_period')>Notice Period</option>
        <option value="left" @selected(request('status')==='left')>Left</option>
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
        <p class="text-ink-700 mb-4">No tenants yet. Add your first tenant!</p>
        <a href="{{ route('owner.tenants.create') }}" class="inline-block px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ Add First Tenant</a>
    </div>
@else
    <div class="space-y-3">
        @foreach($tenants as $tenant)
            <div class="bg-white p-4 rounded-2xl border border-ink-100 hover:border-coral-300 transition">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="w-12 h-12 rounded-full bg-coral-100 text-coral-700 flex items-center justify-center font-bold text-lg flex-shrink-0">
                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-bold text-ink-950">{{ $tenant->name }}</h3>
                            
                            @if($tenant->status === 'active')
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Active</span>
                            @elseif($tenant->status === 'notice_period')
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">Notice</span>
                            @elseif($tenant->status === 'left')
                                <span class="text-xs bg-ink-200 text-ink-700 px-2 py-0.5 rounded-full font-bold">Left</span>
                            @else
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">Blacklisted</span>
                            @endif

                            @if($tenant->kyc_status === 'approved')
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">✓ KYC Verified</span>
                            @elseif($tenant->kyc_status === 'submitted')
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-clipboard-list fa-fw"></i> KYC Submitted</span>
                            @elseif($tenant->kyc_status === 'rejected')
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-circle-xmark fa-fw"></i> KYC Rejected</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-hourglass-half fa-fw"></i> KYC Pending</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs text-ink-700 flex-wrap">
                            <span><i class="fa-solid fa-phone fa-fw"></i> {{ $tenant->phone }}</span>
                            @if($tenant->room_number)<span><i class="fa-solid fa-door-open fa-fw"></i> Room {{ $tenant->room_number }}</span>@endif
                            <span><i class="fa-solid fa-house fa-fw"></i> {{ $tenant->property?->name }}</span>
                            @if($tenant->monthly_rent > 0)<span class="font-bold text-coral-600">₹{{ number_format($tenant->monthly_rent) }}/mo</span>@endif
                        </div>
                    </div>

                    <div class="flex gap-1.5 flex-wrap items-center">
                        @if(is_null($tenant->owner_id))
                            <button type="button" onclick="document.getElementById('claimForm{{ $tenant->id }}').classList.toggle('hidden')"
                                class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold">
                                <i class="fa-solid fa-circle-check fa-fw"></i> Claim &amp; Assign Room
                            </button>
                        @else
                            <a href="{{ route('owner.tenants.show', $tenant) }}" class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-eye fa-fw"></i> View</a>
                            <a href="{{ route('owner.tenants.edit', $tenant) }}" class="px-3 py-2 bg-ink-100 hover:bg-ink-200 text-ink-950 rounded-lg text-xs font-bold"><i class="fa-solid fa-pencil fa-fw"></i> Edit</a>
                        @endif
                        <a href="https://wa.me/91{{ $tenant->phone }}" target="_blank" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> WA</a>
                    </div>
       </div>

                @if(is_null($tenant->owner_id))
                    <div id="claimForm{{ $tenant->id }}" class="hidden mt-4 pt-4 border-t border-dashed border-amber-300">
                        <form method="POST" action="{{ route('owner.tenants.claim', $tenant) }}" class="grid sm:grid-cols-3 gap-3">
                            @csrf
                            <select name="property_id" required class="px-3 py-2 rounded-lg border border-ink-200 text-sm sm:col-span-1">
                                <option value="">Select your property...</option>
                                @foreach(\App\Models\Property::where('owner_id', auth()->id())->orderBy('name')->get() as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="room_number" placeholder="Room number (optional)" class="px-3 py-2 rounded-lg border border-ink-200 text-sm">
                            <button class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-bold">Confirm &amp; Assign</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $tenants->links() }}</div>
@endif

@endsection