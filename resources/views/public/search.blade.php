@extends('layouts.app')
@section('title', 'Search PGs — Find Verified Paying Guest Accommodation')
@section('meta_description', 'Browse 2,500+ verified PGs in Delhi NCR. Filter by city, locality, budget, gender, and amenities.')
@section('content')

{{-- ===== SEARCH HEADER ===== --}}
<section class="bg-ink-950 py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="font-display font-black text-2xl lg:text-4xl text-cream mb-4">Browse {{ $properties->total() ?? 0 }} verified PGs</h1>

        {{-- Search bar --}}
        <form method="GET" class="bg-white rounded-2xl p-2 lg:p-3 flex flex-col lg:flex-row gap-2 max-w-4xl">
            <div class="flex-1 flex items-center gap-3 px-4 py-2">
                <svg class="w-5 h-5 text-ink-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                <input name="q" value="{{ request('q') }}" placeholder="Search city, locality, or PG name..." class="flex-1 outline-none text-sm">
            </div>
            <button class="px-6 lg:px-8 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm">Search</button>
        </form>
    </div>
</section>

{{-- ===== RESULTS ===== --}}
<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8">

            {{-- FILTERS SIDEBAR --}}
            <aside class="lg:sticky lg:top-24 self-start">
                <div class="bg-white rounded-2xl border border-ink-100 p-5">

                    {{-- Mobile collapse --}}
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display font-bold text-lg">Filters</h3>
                        @if(request()->hasAny(['q', 'city', 'gender', 'budget_max', 'amenities']))
                            <a href="{{ route('search') }}" class="text-xs text-coral-500 font-bold">Clear all</a>
                        @endif
                    </div>

                    <form method="GET" class="space-y-5">
                        <input type="hidden" name="q" value="{{ request('q') }}">

                        {{-- City --}}
                        @if(isset($cities))
                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700 block mb-2">City</label>
                            <select name="city" class="w-full px-3 py-2.5 rounded-xl border border-ink-200 text-sm">
                                <option value="">All cities</option>
                                @foreach($cities as $c)
                                    <option value="{{ $c->slug }}" @selected(request('city') === $c->slug)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Gender --}}
                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700 block mb-2">Looking for</label>
                            <div class="grid grid-cols-3 gap-1.5">
                                @foreach(['male'=>'👨 Boys', 'female'=>'👩 Girls', 'unisex'=>'👥 Unisex'] as $val => $label)
                                    <label>
                                        <input type="radio" name="gender" value="{{ $val }}" @checked(request('gender') === $val) class="peer hidden">
                                        <div class="text-center py-2 text-xs rounded-lg border border-ink-200 cursor-pointer peer-checked:bg-coral-500 peer-checked:text-white peer-checked:border-coral-500">{{ $label }}</div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Budget --}}
                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700 block mb-2">Max Budget (₹)</label>
                            <select name="budget_max" class="w-full px-3 py-2.5 rounded-xl border border-ink-200 text-sm">
                                <option value="">Any budget</option>
                                <option value="6000" @selected(request('budget_max') === '6000')>Up to ₹6,000</option>
                                <option value="8000" @selected(request('budget_max') === '8000')>Up to ₹8,000</option>
                                <option value="10000" @selected(request('budget_max') === '10000')>Up to ₹10,000</option>
                                <option value="15000" @selected(request('budget_max') === '15000')>Up to ₹15,000</option>
                                <option value="25000" @selected(request('budget_max') === '25000')>Up to ₹25,000</option>
                            </select>
                        </div>

                        {{-- Property type --}}
                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700 block mb-2">Type</label>
                            <select name="type" class="w-full px-3 py-2.5 rounded-xl border border-ink-200 text-sm">
                                <option value="">All types</option>
                                <option value="pg" @selected(request('type') === 'pg')>PG</option>
                                <option value="hostel" @selected(request('type') === 'hostel')>Hostel</option>
                                <option value="coliving" @selected(request('type') === 'coliving')>Coliving</option>
                                <option value="flatmate" @selected(request('type') === 'flatmate')>Flatmate</option>
                            </select>
                        </div>

                        {{-- Amenities --}}
                        @if(isset($amenities))
                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700 block mb-2">Amenities</label>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-2">
                                @foreach($amenities as $a)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="amenities[]" value="{{ $a->id }}" @checked(in_array($a->id, (array)request('amenities', []))) class="rounded text-coral-500">
                                        <span class="text-sm text-ink-700">{{ $a->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <button class="w-full py-2.5 bg-ink-950 text-cream rounded-xl font-bold text-sm hover:bg-ink-900 transition">Apply filters</button>
                    </form>
                </div>
            </aside>

            {{-- RESULTS GRID --}}
            <div>
                {{-- Sort + count --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                    <div class="text-sm text-ink-700">
                        <strong class="text-ink-950">{{ $properties->total() ?? $properties->count() }}</strong> properties found
                    </div>
                    <form method="GET" class="flex items-center gap-2">
                        @foreach(request()->except('sort') as $key => $val)
                            @if(is_array($val))
                                @foreach($val as $v)<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                            @endif
                        @endforeach
                        <label class="text-xs font-bold text-ink-700">Sort:</label>
                        <select name="sort" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-ink-200 text-xs">
                            <option value="latest" @selected(request('sort') === 'latest')>Newest first</option>
                            <option value="price_low" @selected(request('sort') === 'price_low')>Price: Low to high</option>
                            <option value="price_high" @selected(request('sort') === 'price_high')>Price: High to low</option>
                            <option value="popular" @selected(request('sort') === 'popular')>Most popular</option>
                        </select>
                    </form>
                </div>

                {{-- Property grid --}}
                @if($properties->count())
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($properties as $property)
                            @include('components.property-card', ['property' => $property])
                        @endforeach
                    </div>

                    <div class="mt-10">{{ $properties->withQueryString()->links() }}</div>
                @else
                    <div class="bg-white p-16 rounded-2xl border border-ink-100 text-center">
                        <div class="text-6xl mb-4">🔍</div>
                        <h3 class="font-display font-bold text-2xl text-ink-950">No PGs found</h3>
                        <p class="text-ink-600 mt-2">Try changing filters or search a different city.</p>
                        <a href="{{ route('search') }}" class="inline-block mt-4 px-6 py-2.5 bg-coral-500 text-white rounded-xl font-bold text-sm">Clear filters</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection