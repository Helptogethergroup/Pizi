@extends('layouts.app')

@section('title', 'Search PGs in Delhi NCR & Noida — PGFind')
@section('meta_description', 'Search and filter from 1000+ verified PGs across Delhi NCR. Find the perfect PG by budget, locality, gender and amenities.')

@section('content')
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Filters --}}
        <aside class="lg:col-span-3">
            <form method="GET" class="bg-white p-6 rounded-2xl border border-ink-900/10 sticky top-24 space-y-5">
                <h2 class="font-display font-bold text-xl">Refine search</h2>

                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Search</label>
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Locality, college…"
                           class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>

                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">City</label>
                    <select name="city" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 outline-none">
                        <option value="">All cities</option>
                        @foreach($cities as $c)
                            <option value="{{ $c->slug }}" @selected(($filters['city'] ?? '') === $c->slug)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">For</label>
                    <div class="flex gap-2 mt-1">
                        @foreach(['any' => 'Any', 'male' => 'Boys', 'female' => 'Girls', 'unisex' => 'Unisex'] as $val => $label)
                            <label class="flex-1">
                                <input type="radio" name="gender" value="{{ $val }}" class="peer hidden"
                                       @checked(($filters['gender'] ?? 'any') === $val)>
                                <div class="text-center text-sm py-2 rounded-lg border border-ink-900/15 cursor-pointer peer-checked:bg-ink-900 peer-checked:text-cream peer-checked:border-ink-900">{{ $label }}</div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Budget (₹)</label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <input name="min_rent" value="{{ $filters['min_rent'] ?? '' }}" placeholder="Min" class="px-3 py-2 rounded-lg border border-ink-900/15 outline-none">
                        <input name="max_rent" value="{{ $filters['max_rent'] ?? '' }}" placeholder="Max" class="px-3 py-2 rounded-lg border border-ink-900/15 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Amenities</label>
                    <div class="mt-2 space-y-2 max-h-48 overflow-y-auto">
                        @foreach($amenities as $a)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="amenities[]" value="{{ $a->id }}"
                                       @checked(in_array($a->id, (array)($filters['amenities'] ?? [])))
                                       class="rounded border-ink-900/20 text-coral-500 focus:ring-coral-500">
                                <span>{{ $a->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button class="flex-1 bg-ink-900 text-cream py-3 rounded-xl font-semibold hover:bg-ink-800">Apply</button>
                    <a href="{{ route('search') }}" class="px-4 py-3 rounded-xl border border-ink-900/15 text-sm">Reset</a>
                </div>
            </form>
        </aside>

        {{-- Results --}}
        <div class="lg:col-span-9">
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-ink-900/60">{{ $properties->total() }} PGs found</p>
                <select onchange="window.location.href='?{{ http_build_query(array_merge(request()->query(), ['sort' => '__'])) }}'.replace('__', this.value)"
                        class="text-sm px-3 py-2 rounded-lg border border-ink-900/15">
                    <option value="recommended" @selected(request('sort') === 'recommended' || !request('sort'))>Recommended</option>
                    <option value="low_to_high" @selected(request('sort') === 'low_to_high')>Price: Low to High</option>
                    <option value="high_to_low" @selected(request('sort') === 'high_to_low')>Price: High to Low</option>
                    <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                </select>
            </div>

            @if($properties->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($properties as $property)
                        <x-property-card :property="$property" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $properties->links() }}</div>
            @else
                <div class="text-center py-24 bg-white rounded-2xl border border-ink-900/10">
                    <div class="font-display font-bold text-2xl">No PGs match your filters</div>
                    <p class="text-ink-900/60 mt-2">Try widening your budget or removing some filters.</p>
                    <a href="{{ route('search') }}" class="inline-block mt-6 px-6 py-3 bg-coral-500 text-white rounded-full font-semibold">Reset filters</a>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
