@extends('layouts.app')

@section('title', 'PGFind — Find Verified PGs in Delhi NCR & Noida | Zero Brokerage')
@section('meta_description', 'Browse 1000+ verified PGs, hostels & coliving spaces across Delhi, Noida, Gurgaon. Filter by budget, gender, locality. Free site visits, zero brokerage.')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "PGFind",
  "url": "{{ url('/') }}",
  "description": "PG and hostel aggregator for Delhi NCR & Noida.",
  "address": { "@type": "PostalAddress", "addressLocality": "Delhi", "addressCountry": "IN" }
}
</script>
@endsection

@section('content')

{{-- HERO --}}
<section class="relative overflow-hidden bg-cream grain">
    <div class="absolute -top-40 -right-40 w-[600px] h-[600px] bg-coral-100 rounded-full blur-3xl opacity-40"></div>
    <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-ink-900/5 rounded-full blur-3xl"></div>

    <div class="relative max-w-7xl mx-auto px-4 lg:px-8 py-16 lg:py-24">
        <div class="max-w-3xl">
            <span class="inline-block px-3 py-1 rounded-full bg-ink-900 text-cream text-xs font-semibold tracking-wide uppercase mb-6">
                Zero Brokerage · 1000+ Verified PGs
            </span>
            <h1 class="font-display font-black text-5xl md:text-7xl leading-[0.95] text-ink-950">
                Find a <em class="text-coral-500 not-italic">PG</em> that<br>feels like home.
            </h1>
            <p class="mt-6 text-lg md:text-xl text-ink-900/70 max-w-xl leading-relaxed">
                Verified listings, real photos, honest rents. Across Delhi, Noida, Gurgaon & Ghaziabad — book a free site visit in 60 seconds.
            </p>
        </div>

        {{-- SEARCH BAR --}}
        <form action="{{ route('search') }}" method="GET" class="mt-10 bg-white p-3 rounded-2xl shadow-2xl shadow-ink-900/10 border border-ink-900/5 grid grid-cols-1 md:grid-cols-12 gap-2 max-w-4xl">
            <div class="md:col-span-5 flex items-center gap-2 px-4">
                <svg class="w-5 h-5 text-ink-900/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <input name="q" placeholder="Locality, college, metro station…" class="w-full py-3 outline-none text-ink-900 placeholder:text-ink-900/40">
            </div>
            <select name="gender" class="md:col-span-3 px-4 py-3 outline-none border-l border-ink-900/10 text-ink-900">
                <option value="any">Any gender</option>
                <option value="male">Boys</option>
                <option value="female">Girls</option>
                <option value="unisex">Unisex</option>
            </select>
            <select name="max_rent" class="md:col-span-2 px-4 py-3 outline-none border-l border-ink-900/10 text-ink-900">
                <option value="">Budget</option>
                <option value="6000">Under ₹6k</option>
                <option value="10000">Under ₹10k</option>
                <option value="15000">Under ₹15k</option>
                <option value="25000">Under ₹25k</option>
            </select>
            <button class="md:col-span-2 bg-coral-500 hover:bg-coral-600 text-white font-semibold rounded-xl py-3 transition">Search →</button>
        </form>

        {{-- STATS --}}
        <div class="mt-12 flex flex-wrap gap-8">
            <div><div class="font-display font-bold text-3xl text-ink-950">{{ number_format($stats['properties']) }}+</div><div class="text-sm text-ink-900/60">Verified PGs</div></div>
            <div class="w-px bg-ink-900/10"></div>
            <div><div class="font-display font-bold text-3xl text-ink-950">{{ $stats['cities'] }}</div><div class="text-sm text-ink-900/60">Cities</div></div>
            <div class="w-px bg-ink-900/10"></div>
            <div><div class="font-display font-bold text-3xl text-ink-950">{{ number_format($stats['happy_residents']) }}+</div><div class="text-sm text-ink-900/60">Happy Residents</div></div>
        </div>
    </div>
</section>

{{-- FEATURED --}}
@if($featured->count())
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-20">
    <div class="flex items-end justify-between mb-10">
        <div>
            <span class="text-xs font-semibold text-coral-600 tracking-wider uppercase">Hand-picked</span>
            <h2 class="font-display font-bold text-4xl mt-2">Featured properties</h2>
        </div>
        <a href="{{ route('search') }}" class="text-sm font-semibold text-ink-900 hover:text-coral-600">View all →</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($featured as $property)
            <x-property-card :property="$property" />
        @endforeach
    </div>
</section>
@endif

{{-- CITIES --}}
<section class="bg-ink-950 text-cream py-20 my-12">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="max-w-2xl mb-12">
            <span class="text-xs font-semibold text-coral-400 tracking-wider uppercase">Browse by city</span>
            <h2 class="font-display font-bold text-4xl mt-2">Where do you want to live?</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($cities as $city)
                <a href="{{ route('city.show', $city->slug) }}" class="group p-6 bg-cream/5 hover:bg-coral-500 rounded-2xl transition-all border border-cream/10 hover:border-coral-500">
                    <div class="font-display font-bold text-2xl">{{ $city->name }}</div>
                    <div class="text-sm text-cream/60 group-hover:text-cream/90 mt-1">{{ $city->properties_count ?? 0 }} PGs available</div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- LATEST --}}
@if($latest->count())
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-20">
    <div class="flex items-end justify-between mb-10">
        <div>
            <span class="text-xs font-semibold text-coral-600 tracking-wider uppercase">Just listed</span>
            <h2 class="font-display font-bold text-4xl mt-2">Newly added PGs</h2>
        </div>
        <a href="{{ route('search') }}?sort=newest" class="text-sm font-semibold hover:text-coral-600">View all →</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($latest as $property)
            <x-property-card :property="$property" />
        @endforeach
    </div>
</section>
@endif

{{-- HOW IT WORKS --}}
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-20">
    <div class="text-center max-w-2xl mx-auto mb-16">
        <span class="text-xs font-semibold text-coral-600 tracking-wider uppercase">Simple, honest, fast</span>
        <h2 class="font-display font-bold text-4xl mt-2">How PGFind works</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @foreach([
            ['1', 'Search & shortlist', 'Filter by budget, locality, gender. See real photos & honest reviews.'],
            ['2', 'Free site visit', 'Book a free visit. Our executive shows you the PG, no pressure.'],
            ['3', 'Move in', 'Pay token, move in the same day. Zero brokerage. We help with everything.'],
        ] as [$num, $title, $desc])
            <div class="p-8 bg-cream rounded-2xl border border-ink-900/10 hover:border-coral-500 transition">
                <div class="font-display font-black text-7xl text-coral-500/30">{{ $num }}</div>
                <h3 class="font-display font-bold text-2xl mt-2">{{ $title }}</h3>
                <p class="text-ink-900/70 mt-2">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- BLOG --}}
@if($blogs->count())
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-20">
    <div class="flex items-end justify-between mb-10">
        <h2 class="font-display font-bold text-4xl">From the journal</h2>
        <a href="{{ route('blog.index') }}" class="text-sm font-semibold hover:text-coral-600">All articles →</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($blogs as $blog)
            <a href="{{ route('blog.show', $blog->slug) }}" class="group block">
                <div class="aspect-[4/3] bg-ink-900/5 rounded-2xl overflow-hidden mb-4">
                    <img src="{{ $blog->cover_image ?: 'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=800&q=80' }}" class="w-full h-full object-cover group-hover:scale-105 transition" alt="{{ $blog->title }}">
                </div>
                <h3 class="font-display font-bold text-2xl group-hover:text-coral-600 leading-tight">{{ $blog->title }}</h3>
                <p class="text-ink-900/60 mt-2 text-sm line-clamp-2">{{ $blog->excerpt }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- CTA --}}
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-20">
    <div class="bg-gradient-to-br from-ink-900 to-ink-950 rounded-3xl p-10 md:p-16 text-cream relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-80 h-80 bg-coral-500/20 rounded-full blur-3xl"></div>
        <div class="relative max-w-2xl">
            <h2 class="font-display font-bold text-4xl md:text-5xl">Own a PG? List it free.</h2>
            <p class="mt-4 text-cream/80 text-lg">Reach thousands of verified tenants every month. We handle leads, visits, and bookings — you get paid.</p>
            <a href="{{ route('register') }}" class="inline-block mt-8 px-8 py-4 bg-coral-500 hover:bg-coral-600 rounded-full font-bold text-lg transition">List your property →</a>
        </div>
    </div>
</section>

@endsection
