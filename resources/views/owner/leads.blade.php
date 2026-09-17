@extends('layouts.dashboard')
@section('title', 'My Matched Leads')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">Matched Leads</h1>
        <p class="text-ink-900/60 mt-1">Smart-matched to your properties by location, budget, and gender preference.</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="px-4 py-2 bg-ink-950 text-cream rounded-xl font-bold">
            🪙 {{ number_format($wallet->balance) }} credits
        </div>
        <a href="{{ route('owner.packages') }}" class="px-4 py-2 bg-coral-500 text-white rounded-xl font-bold">+ Buy Credits</a>
    </div>
</div>

{{-- Pricing strip --}}
<div class="bg-white p-3 rounded-xl border border-ink-900/10 mb-4 flex flex-wrap items-center gap-3 text-sm">
    <span class="text-xs font-bold text-ink-900/60 uppercase">Unlock cost:</span>
    <span class="px-3 py-1 rounded-full bg-cream"><strong>Direct:</strong> {{ $pricing['direct']->credit_cost ?? 0 }} credits</span>
    <span class="px-3 py-1 rounded-full bg-cream"><strong>Verified:</strong> {{ $pricing['verified']->credit_cost ?? 0 }} credits</span>
    <span class="px-3 py-1 rounded-full bg-cream"><strong>Converted:</strong> {{ $pricing['converted']->credit_cost ?? 0 }} credits</span>
</div>

{{-- Status Summary Cards (clickable) --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    @php
        $statusCards = [
            'new_lead' => ['label' => 'New', 'color' => 'bg-blue-50 text-blue-700 border-blue-200'],
            'open' => ['label' => 'Open', 'color' => 'bg-sky-50 text-sky-700 border-sky-200'],
            'connected' => ['label' => 'Connected', 'color' => 'bg-teal-50 text-teal-700 border-teal-200'],
            'follow_up' => ['label' => 'Follow Up', 'color' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'deal_closed' => ['label' => 'Closed', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'lost' => ['label' => 'Lost', 'color' => 'bg-rose-50 text-rose-700 border-rose-200'],
        ];
        $currentParams = request()->except(['status', 'page']);
    @endphp
    @foreach($statusCards as $key => $card)
        <a href="?{{ http_build_query(array_merge($currentParams, ['status' => $key])) }}"
           class="p-4 rounded-xl border-2 {{ $card['color'] }} {{ request('status') === $key ? 'ring-2 ring-offset-1 ring-ink-900' : '' }} transition hover:shadow-md">
            <div class="text-xs font-bold uppercase opacity-70">{{ $card['label'] }}</div>
            <div class="text-2xl font-black mt-1">{{ $statusCounts[$key] }}</div>
        </a>
    @endforeach
</div>

{{-- Filter Panel — pick everything, then hit Filter once. Nothing
     auto-submits, so you can set 3-4 filters together without the page
     reloading after every single click. --}}
<form method="GET" id="filterForm" class="bg-white p-4 rounded-xl border border-ink-900/10 mb-6 space-y-3">
    <input type="hidden" name="tab" value="{{ $tab }}">

    <div>
        <label class="block text-xs font-bold text-ink-900/50 uppercase mb-1">Search by name or phone</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. Rahul or 98765..."
               class="w-full px-3 py-2 border border-ink-900/15 rounded-lg text-sm">
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-bold text-ink-900/50 uppercase mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-ink-900/15 rounded-lg text-sm">
                <option value="">All statuses</option>
                @foreach(['new_lead'=>'New Lead','open'=>'Open','contacted'=>'Contacted','connected'=>'Connected','not_connected'=>'Not Connected','follow_up'=>'Follow Up','visit_scheduled'=>'Visit Scheduled','visit_completed'=>'Visit Completed','deal_closed'=>'Deal Closed','lost'=>'Lost','cancelled'=>'Cancelled'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-ink-900/50 uppercase mb-1">Inquiry</label>
            <select name="inquiry_type" class="px-3 py-2 border border-ink-900/15 rounded-lg text-sm">
                <option value="">All</option>
                <option value="tenant" @selected(request('inquiry_type') === 'tenant')>🧳 Tenant</option>
                <option value="owner" @selected(request('inquiry_type') === 'owner')>🏠 Owner</option>
                <option value="unknown" @selected(request('inquiry_type') === 'unknown')>❓ Unknown</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-ink-900/50 uppercase mb-1">Date</label>
            <select name="date_range" class="px-3 py-2 border border-ink-900/15 rounded-lg text-sm">
                <option value="">Any time</option>
                <option value="today" @selected(request('date_range') === 'today')>Today</option>
                <option value="yesterday" @selected(request('date_range') === 'yesterday')>Yesterday</option>
                <option value="week" @selected(request('date_range') === 'week')>This Week</option>
                <option value="month" @selected(request('date_range') === 'month')>This Month</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-ink-900/50 uppercase mb-1">Property</label>
            <select name="property_id" class="px-3 py-2 border border-ink-900/15 rounded-lg text-sm">
                <option value="">All properties</option>
                @foreach($properties as $p)
                    <option value="{{ $p->id }}" @selected((string) request('property_id') === (string) $p->id)>{{ $p->name }} ({{ $propertyLeadCounts[$p->id] ?? 0 }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-ink-900/50 uppercase mb-1">Locality</label>
            <select name="locality" class="px-3 py-2 border border-ink-900/15 rounded-lg text-sm">
                <option value="">All localities</option>
                @foreach($localities as $loc)
                    <option value="{{ $loc }}" @selected(request('locality') === $loc)>📍 {{ $loc }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-ink-900/50 uppercase mb-1">Sort by</label>
            <select name="sort" class="px-3 py-2 border border-ink-900/15 rounded-lg text-sm">
                <option value="" @selected(!request('sort'))>🎯 Best Match</option>
                <option value="newest" @selected(request('sort') === 'newest')>🆕 Newest first</option>
                <option value="budget_high" @selected(request('sort') === 'budget_high')>💰 Budget: High to Low</option>
                <option value="budget_low" @selected(request('sort') === 'budget_low')>💰 Budget: Low to High</option>
            </select>
        </div>

        <button class="px-6 py-2 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>

        @if(request()->hasAny(['status','date_range','property_id','locality','search','sort','area_only']))
            <a href="?tab={{ $tab }}" class="px-4 py-2 text-sm font-bold text-rose-600 hover:underline">Clear filters</a>
        @endif
    </div>

    {{-- Quick toggle — one click instead of setting the Locality dropdown
         to match "my area" leads (same area_match signal the score bonus uses). --}}
    <div>
        @php
            $areaOnlyParams = array_merge(request()->except(['area_only', 'page']), ['area_only' => 1]);
            $withoutAreaOnly = request()->except(['area_only', 'page']);
        @endphp
        @if(request()->boolean('area_only'))
            <a href="?{{ http_build_query($withoutAreaOnly) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-coral-500 text-white">
                📍 Only my area ✕
            </a>
        @else
            <a href="?{{ http_build_query($areaOnlyParams) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-cream text-ink-900/70 border border-ink-900/15 hover:bg-coral-50 hover:text-coral-700 hover:border-coral-200">
                📍 Only my area
            </a>
        @endif
    </div>
</form>

{{-- Tabs --}}
<div class="flex gap-1 mb-6 bg-white p-1 rounded-2xl border border-ink-900/10 w-fit overflow-x-auto">
    <a href="?tab=all" class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap {{ $tab === 'all' ? 'bg-ink-950 text-cream' : 'text-ink-900/60 hover:bg-ink-900/5' }}">
        All ({{ $counts['all'] }})
    </a>
     <a href="?tab=verified"
            class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap {{ $tab === 'verified' ? 'bg-ink-950 text-cream' : 'text-ink-900/60 hover:bg-ink-900/5' }}">
            ✅ Verified ({{ $counts['verified'] }})
        </a>
        <a href="?tab=manual"
            class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap {{ $tab === 'manual' ? 'bg-ink-950 text-cream' : 'text-ink-900/60 hover:bg-ink-900/5' }}">
            📋 Direct ({{ $counts['manual'] }})
        </a>
</div>

{{-- Lead cards --}}
@if($paginated->count() === 0)
    <div class="bg-white p-16 rounded-2xl border border-ink-900/10 text-center">
        <div class="text-6xl mb-4">🔍</div>
        <p class="font-display font-bold text-xl">No leads in this tab</p>
        <p class="text-sm text-ink-900/60 mt-2">Check other tabs or wait for new leads to come in.</p>
    </div>
@else
    @php
        $pageItems = collect($paginated->items());
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($pageItems as $lead)
            @include('owner.leads._card', ['lead' => $lead])
        @endforeach
    </div>

    <div class="mt-6">{{ $paginated->links() }}</div>
@endif

@endsection
