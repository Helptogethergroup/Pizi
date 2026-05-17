@extends('layouts.app')
@section('title', 'PGFind — Find Verified PGs in Delhi NCR, Noida & Gurgaon')
@section('content')

{{-- ===== HERO ===== --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-950 via-ink-900 to-ink-800">
    {{-- Background pattern --}}
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 left-0 w-96 h-96 bg-coral-500 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-coral-400 rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="text-center max-w-4xl mx-auto">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-coral-500/10 text-coral-300 text-xs sm:text-sm font-semibold border border-coral-500/20 mb-6 animate-fade-up">
                ✨ Trusted by 10,000+ tenants across NCR
            </span>

            <h1 class="font-display font-black text-4xl sm:text-5xl lg:text-7xl text-cream leading-tight mb-6 text-balance animate-fade-up">
                Find Your <span class="text-coral-400 italic">Perfect</span> PG<br class="hidden sm:block">
                in Delhi NCR & Noida
            </h1>

            <p class="text-base sm:text-lg lg:text-xl text-cream/70 mb-10 max-w-2xl mx-auto text-balance animate-fade-up">
                Verified PGs with real photos. Direct contact with owners. No brokerage. No hidden fees.
            </p>

            {{-- Search box --}}
           {{-- Smart Search Card --}}
<div class="bg-white rounded-2xl lg:rounded-3xl shadow-2xl shadow-ink-950/30 max-w-4xl mx-auto animate-fade-up overflow-hidden">
    <form action="{{ route('search') }}" method="GET" id="smartSearchForm">

        {{-- Top row: Location + Search --}}
        <div class="p-2 lg:p-3 flex flex-col lg:flex-row gap-2 border-b border-ink-100">
            <div class="flex-1 flex items-center gap-3 px-4 py-3 relative">
                <svg class="w-5 h-5 text-coral-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                <input id="locationSearch" name="q" type="text" placeholder="Locality, college, metro station…" autocomplete="off" class="flex-1 text-sm lg:text-base outline-none placeholder:text-ink-400 bg-transparent">

                {{-- Autocomplete dropdown --}}
                <div id="autocomplete" class="hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-ink-100 max-h-80 overflow-y-auto z-50">
                    <div id="autocompleteResults" class="p-2"></div>
                </div>
            </div>
            <button type="submit" class="px-6 lg:px-8 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl lg:rounded-2xl font-bold text-sm lg:text-base transition-all hover:shadow-lg hover:shadow-coral-500/40 flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search
            </button>
        </div>

        {{-- Filters row --}}
        <div class="p-3 lg:p-4 grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-3 bg-cream-200">

            {{-- Gender --}}
            <div>
                <label class="text-[10px] font-bold uppercase text-ink-600 block mb-1.5 px-1">Looking for</label>
                <select name="gender" class="w-full px-3 py-2.5 rounded-xl border border-ink-200 bg-white text-sm focus:border-coral-500 outline-none">
                    <option value="">Any</option>
                    <option value="male">👨 Boys</option>
                    <option value="female">👩 Girls</option>
                    <option value="unisex">👥 Unisex</option>
                </select>
            </div>

            {{-- Budget --}}
            <div>
                <label class="text-[10px] font-bold uppercase text-ink-600 block mb-1.5 px-1">Max Budget</label>
                <select name="budget_max" class="w-full px-3 py-2.5 rounded-xl border border-ink-200 bg-white text-sm focus:border-coral-500 outline-none">
                    <option value="">Any budget</option>
                    <option value="6000">Under ₹6,000</option>
                    <option value="8000">Under ₹8,000</option>
                    <option value="10000">Under ₹10,000</option>
                    <option value="15000">Under ₹15,000</option>
                    <option value="25000">Under ₹25,000</option>
                </select>
            </div>

            {{-- Type --}}
            <div>
                <label class="text-[10px] font-bold uppercase text-ink-600 block mb-1.5 px-1">Type</label>
                <select name="type" class="w-full px-3 py-2.5 rounded-xl border border-ink-200 bg-white text-sm focus:border-coral-500 outline-none">
                    <option value="">All</option>
                    <option value="pg">🏠 PG</option>
                    <option value="hostel">🏨 Hostel</option>
                    <option value="coliving">🛋️ Coliving</option>
                    <option value="flatmate">👯 Flatmate</option>
                </select>
            </div>

            {{-- Food --}}
            <div>
                <label class="text-[10px] font-bold uppercase text-ink-600 block mb-1.5 px-1">Food</label>
                <select name="food" class="w-full px-3 py-2.5 rounded-xl border border-ink-200 bg-white text-sm focus:border-coral-500 outline-none">
                    <option value="">Any</option>
                    <option value="1">🍽️ Included</option>
                    <option value="0">Not included</option>
                </select>
            </div>
        </div>

        {{-- Quick filter chips --}}
        <div class="p-3 lg:p-4 flex flex-wrap items-center gap-2 border-t border-ink-100">
            <span class="text-xs font-bold text-ink-500 hidden sm:block">Quick:</span>
            <button type="button" onclick="applyQuickFilter('budget_max', '6000')" class="quick-chip">💰 Under ₹6k</button>
            <button type="button" onclick="applyQuickFilter('gender', 'female')" class="quick-chip">👩 Girls PG</button>
            <button type="button" onclick="applyQuickFilter('gender', 'male')" class="quick-chip">👨 Boys PG</button>
            <button type="button" onclick="applyQuickFilter('food', '1')" class="quick-chip">🍽️ With food</button>
            <button type="button" onclick="applyQuickFilter('type', 'coliving')" class="quick-chip">🛋️ Coliving</button>
            <button type="button" onclick="applyQuickFilter('q', 'metro')" class="quick-chip">🚇 Near metro</button>
        </div>
    </form>
</div>

<style>
.quick-chip {
    @apply px-3 py-1.5 rounded-full bg-white border border-ink-200 text-xs font-semibold text-ink-700 hover:bg-coral-500 hover:text-white hover:border-coral-500 transition;
}
</style>

<script>
// Locality + landmark + city autocomplete data
const searchableItems = [
    @foreach($cities ?? [] as $city)
        { type: 'city', name: '{{ $city->name }}', slug: '{{ $city->slug }}', label: '{{ $city->name }} (City)', icon: '🏙️' },
    @endforeach
    @foreach($localities ?? [] as $loc)
        { type: 'locality', name: '{{ $loc->name }}', city: '{{ $loc->city?->name }}', label: '{{ $loc->name }}, {{ $loc->city?->name }}', icon: '📍' },
    @endforeach
    @foreach($landmarks ?? [] as $lm)
        { type: 'landmark', name: '{{ $lm->name }}', slug: '{{ $lm->slug }}', label: '{{ $lm->name }} (Landmark)', icon: '🎯' },
    @endforeach
];

const searchInput = document.getElementById('locationSearch');
const autocompleteEl = document.getElementById('autocomplete');
const resultsEl = document.getElementById('autocompleteResults');

searchInput.addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();

    if (query.length < 1) {
        autocompleteEl.classList.add('hidden');
        return;
    }

    const matches = searchableItems
        .filter(item => item.name.toLowerCase().includes(query) || item.label.toLowerCase().includes(query))
        .slice(0, 8);

    if (matches.length === 0) {
        autocompleteEl.classList.add('hidden');
        return;
    }

    resultsEl.innerHTML = matches.map(item => `
        <button type="button" onclick="selectItem('${item.name.replace(/'/g, "\\'")}','${item.type}','${item.slug || ''}')"
                class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-cream-200 text-left transition">
            <span class="text-xl">${item.icon}</span>
            <div>
                <div class="font-semibold text-sm text-ink-950">${item.name}</div>
                <div class="text-xs text-ink-500 capitalize">${item.type}${item.city ? ' • ' + item.city : ''}</div>
            </div>
        </button>
    `).join('');

    autocompleteEl.classList.remove('hidden');
});

function selectItem(name, type, slug) {
    // For landmark, redirect to special URL
    if (type === 'landmark' && slug) {
        window.location.href = '/pg-near-' + slug;
        return;
    }
    // For city, redirect to city page
    if (type === 'city' && slug) {
        window.location.href = '/pg-in-' + slug;
        return;
    }
    // For locality, fill input + submit
    searchInput.value = name;
    autocompleteEl.classList.add('hidden');
    document.getElementById('smartSearchForm').submit();
}

// Close autocomplete on outside click
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !autocompleteEl.contains(e.target)) {
        autocompleteEl.classList.add('hidden');
    }
});

// Quick filter — apply and submit
function applyQuickFilter(field, value) {
    const form = document.getElementById('smartSearchForm');
    if (field === 'q') {
        form.q.value = value;
    } else {
        const select = form.querySelector(`[name="${field}"]`);
        if (select) select.value = value;
    }
    form.submit();
}
</script>

            {{-- Quick links --}}
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-xs sm:text-sm">
                <span class="text-cream/50">Popular:</span>
                @foreach(['Delhi', 'Noida', 'Gurgaon', 'Faridabad'] as $city)
                    <a href="{{ route('city.show', strtolower($city)) }}" class="px-3 py-1 rounded-full bg-cream/10 text-cream/80 hover:bg-coral-500 hover:text-white transition">{{ $city }}</a>
                @endforeach
            </div>

            {{-- Stats --}}
            <div class="mt-12 lg:mt-16 grid grid-cols-3 gap-4 max-w-2xl mx-auto">
                <div>
                    <div class="font-display font-black text-3xl lg:text-5xl text-coral-400">2,500+</div>
                    <div class="text-xs sm:text-sm text-cream/60 mt-1">Verified PGs</div>
                </div>
                <div>
                    <div class="font-display font-black text-3xl lg:text-5xl text-coral-400">10K+</div>
                    <div class="text-xs sm:text-sm text-cream/60 mt-1">Happy Tenants</div>
                </div>
                <div>
                    <div class="font-display font-black text-3xl lg:text-5xl text-coral-400">15+</div>
                    <div class="text-xs sm:text-sm text-cream/60 mt-1">Cities</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== CATEGORIES ===== --}}
<section class="py-16 lg:py-20 bg-cream-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-display font-black text-3xl lg:text-5xl text-ink-950 mb-3">Find by your need</h2>
            <p class="text-ink-700 text-base lg:text-lg">Browse PGs by city, gender, or budget</p>
        </div>

        @if(isset($cities) && $cities->count())
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
            @foreach($cities->take(8) as $city)
                <a href="{{ route('city.show', $city->slug) }}" class="hover-lift bg-white rounded-2xl p-6 border border-ink-100 group">
                    <div class="w-12 h-12 rounded-xl bg-coral-50 group-hover:bg-coral-500 flex items-center justify-center mb-4 transition-colors">
                        <svg class="w-6 h-6 text-coral-500 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                    </div>
                    <h3 class="font-display font-bold text-lg text-ink-950">{{ $city->name }}</h3>
                    <p class="text-sm text-ink-500 mt-1">{{ $city->properties_count ?? 0 }} PGs available</p>
                </a>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- ===== FEATURED PROPERTIES ===== --}}
@if(isset($featured) && $featured->count())
<section class="py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-10">
            <div>
                <span class="text-coral-500 font-bold text-sm uppercase tracking-wider">⭐ Featured</span>
                <h2 class="font-display font-black text-3xl lg:text-5xl text-ink-950 mt-2">Hand-picked PGs for you</h2>
            </div>
            <a href="{{ route('search') }}" class="hidden sm:inline-flex items-center gap-2 text-coral-500 font-bold text-sm hover:gap-3 transition-all">
                View all →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($featured->take(6) as $property)
                @include('components.property-card', ['property' => $property])
            @endforeach
        </div>

        <div class="mt-10 text-center sm:hidden">
            <a href="{{ route('search') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-coral-500 text-white font-bold text-sm">View all PGs →</a>
        </div>
    </div>
</section>
@endif

{{-- ===== HOW IT WORKS ===== --}}
<section class="py-16 lg:py-24 bg-ink-950 text-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-coral-400 font-bold text-sm uppercase tracking-wider">How it works</span>
            <h2 class="font-display font-black text-3xl lg:text-5xl mt-2 text-balance">From search to keys in <span class="text-coral-400 italic">3 steps</span></h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
            @foreach([
                ['1', '🔍', 'Search verified PGs', 'Browse by city, locality, budget, or landmark. Every PG is admin-verified with real photos.'],
                ['2', '📞', 'Connect with owner', 'Submit your inquiry. Our team verifies & connects you directly with the owner. No brokerage.'],
                ['3', '🏠', 'Visit & book', 'Free site visit with our field executive. Pay token amount directly. Move in immediately.'],
            ] as $step)
                <div class="text-center">
                    <div class="relative inline-block mb-6">
                        <div class="w-24 h-24 rounded-3xl bg-coral-500/10 border border-coral-500/20 flex items-center justify-center text-5xl">
                            {{ $step[1] }}
                        </div>
                        <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-coral-500 flex items-center justify-center font-display font-black text-sm">{{ $step[0] }}</div>
                    </div>
                    <h3 class="font-display font-bold text-2xl mb-3">{{ $step[2] }}</h3>
                    <p class="text-cream/70 max-w-xs mx-auto leading-relaxed">{{ $step[3] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== TRUST / WHY US ===== --}}
<section class="py-16 lg:py-20 bg-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-coral-500 font-bold text-sm uppercase tracking-wider">Why PGFind</span>
                <h2 class="font-display font-black text-3xl lg:text-5xl text-ink-950 mt-2 mb-6 text-balance">India's most <span class="italic text-coral-500">trusted</span> PG aggregator</h2>
                <p class="text-ink-700 text-lg leading-relaxed mb-8">
                    We don't just list PGs — we verify them. Every owner is checked, every property is visited, every photo is real. Move in with confidence.
                </p>

                <div class="space-y-4">
                    @foreach([
                        ['✓', 'Admin-verified properties', 'Every PG is physically inspected before going live'],
                        ['✓', 'Real photos, real owners', 'No fake listings, no stock photos'],
                        ['✓', 'Zero brokerage to tenants', 'You only pay your security deposit & rent'],
                        ['✓', 'Free site visits', 'Our field team accompanies you for visits'],
                    ] as $feature)
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 font-bold text-sm">{{ $feature[0] }}</div>
                            <div>
                                <div class="font-bold text-ink-950">{{ $feature[1] }}</div>
                                <div class="text-sm text-ink-600">{{ $feature[2] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:gap-6">
                <div class="bg-white p-6 rounded-2xl border border-ink-100 hover-lift">
                    <div class="text-4xl mb-3">🏆</div>
                    <div class="font-display font-black text-3xl text-coral-500">95%</div>
                    <div class="text-sm text-ink-600 mt-1">Tenant satisfaction</div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-ink-100 hover-lift mt-8">
                    <div class="text-4xl mb-3">⚡</div>
                    <div class="font-display font-black text-3xl text-coral-500">30 min</div>
                    <div class="text-sm text-ink-600 mt-1">Avg response time</div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-ink-100 hover-lift">
                    <div class="text-4xl mb-3">🛡️</div>
                    <div class="font-display font-black text-3xl text-coral-500">100%</div>
                    <div class="text-sm text-ink-600 mt-1">Verified PGs</div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-ink-100 hover-lift mt-8">
                    <div class="text-4xl mb-3">💸</div>
                    <div class="font-display font-black text-3xl text-coral-500">₹0</div>
                    <div class="text-sm text-ink-600 mt-1">Brokerage charges</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== CTA OWNER ===== --}}
<section class="py-16 lg:py-20 bg-gradient-to-br from-coral-500 to-coral-600 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-10 -left-10 w-80 h-80 bg-white rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -right-10 w-96 h-96 bg-cream rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-display font-black text-3xl lg:text-5xl text-white mb-4 text-balance">
            Own a PG? List it on PGFind <span class="italic">for free</span>.
        </h2>
        <p class="text-white/90 text-base lg:text-lg mb-8 max-w-2xl mx-auto">
            Get verified tenants in 48 hours. Pay only when you unlock a lead. No upfront fees.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-coral-600 font-bold text-base hover:bg-cream transition shadow-2xl">
                List Your PG Now
            </a>
            <a href="{{ route('about') }}" class="w-full sm:w-auto px-8 py-4 rounded-full border-2 border-white/30 text-white font-bold text-base hover:bg-white/10 transition">
                Learn how it works
            </a>
        </div>
    </div>
</section>

{{-- ===== BLOG PREVIEW ===== --}}
@if(isset($recentBlogs) && $recentBlogs->count())
<section class="py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-10">
            <div>
                <span class="text-coral-500 font-bold text-sm uppercase tracking-wider">From the blog</span>
                <h2 class="font-display font-black text-3xl lg:text-5xl text-ink-950 mt-2">Tips & Guides</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="hidden sm:inline-flex items-center gap-2 text-coral-500 font-bold text-sm hover:gap-3 transition-all">
                Read all →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($recentBlogs->take(3) as $blog)
                <a href="{{ route('blog.show', $blog->slug) }}" class="hover-lift bg-white rounded-2xl overflow-hidden border border-ink-100 group block">
                    @if($blog->cover_image)
                        <img src="{{ str_starts_with($blog->cover_image, 'http') ? $blog->cover_image : asset('storage/' . $blog->cover_image) }}" class="w-full aspect-[16/10] object-cover" alt="{{ $blog->title }}">
                    @endif
                    <div class="p-6">
                        <span class="text-xs text-coral-500 font-bold uppercase tracking-wider">{{ $blog->published_at?->format('d M Y') }}</span>
                        <h3 class="font-display font-bold text-xl text-ink-950 mt-2 group-hover:text-coral-500 transition-colors line-clamp-2">{{ $blog->title }}</h3>
                        <p class="text-sm text-ink-600 mt-2 line-clamp-2">{{ $blog->excerpt }}</p>
                        <span class="inline-block mt-3 text-coral-500 font-bold text-sm">Read more →</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection