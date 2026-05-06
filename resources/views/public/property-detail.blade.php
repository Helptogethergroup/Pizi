@extends('layouts.app')

@section('title', ($property->meta_title ?: $property->name . ' — ' . $property->locality?->name . ', ' . $property->city?->name) . ' | PGFind')
@section('meta_description', $property->meta_description ?: Str::limit(strip_tags($property->description ?? 'Verified PG in ' . $property->locality?->name), 160))
@section('og_image', $property->cover_url)

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LodgingBusiness",
  "name": @json($property->name),
  "image": @json($property->cover_url),
  "description": @json(Str::limit(strip_tags($property->description ?? ''), 250)),
  "address": {
    "@type": "PostalAddress",
    "streetAddress": @json($property->address_line),
    "addressLocality": @json($property->locality?->name),
    "addressRegion": @json($property->city?->name),
    "postalCode": @json($property->pincode),
    "addressCountry": "IN"
  },
  "priceRange": @json($property->rent_range),
  @if($property->latitude && $property->longitude)
  "geo": { "@type": "GeoCoordinates", "latitude": {{ $property->latitude }}, "longitude": {{ $property->longitude }} },
  @endif
  "url": @json(url()->current())
}
</script>
@endsection

@section('content')
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
    {{-- Breadcrumbs --}}
    <nav class="text-sm text-ink-900/60 mb-6">
        <a href="{{ route('home') }}" class="hover:text-coral-600">Home</a> ›
        <a href="{{ route('city.show', $property->city->slug) }}" class="hover:text-coral-600">PGs in {{ $property->city?->name }}</a> ›
        <a href="{{ route('locality.show', [$property->city->slug, $property->locality->slug]) }}" class="hover:text-coral-600">{{ $property->locality?->name }}</a> ›
        <span class="text-ink-900">{{ $property->name }}</span>
    </nav>

    {{-- Gallery --}}
    <div class="grid grid-cols-4 grid-rows-2 gap-2 h-[400px] md:h-[500px] rounded-3xl overflow-hidden mb-10">
        <div class="col-span-4 md:col-span-2 row-span-2 bg-ink-900/5">
            <img src="{{ $property->cover_url }}" alt="{{ $property->name }}" class="w-full h-full object-cover">
        </div>
        @foreach($property->images->take(4) as $img)
            <div class="hidden md:block bg-ink-900/5">
                <img src="{{ $img->url }}" alt="{{ $img->caption }}" class="w-full h-full object-cover">
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <div class="lg:col-span-2">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">✓ Verified</span>
                <span class="px-2.5 py-1 rounded-full bg-coral-50 text-coral-700 text-xs font-semibold capitalize">
                    For {{ $property->gender === 'unisex' ? 'all' : ($property->gender === 'male' ? 'boys' : 'girls') }}
                </span>
                <span class="px-2.5 py-1 rounded-full bg-ink-900/5 text-ink-900 text-xs font-semibold capitalize">{{ $property->property_type }}</span>
            </div>

            <h1 class="font-display font-black text-4xl md:text-5xl leading-tight">{{ $property->name }}</h1>
            <p class="text-ink-900/70 mt-3 text-lg">📍 {{ $property->address_line }}, {{ $property->locality?->name }}, {{ $property->city?->name }}</p>

            {{-- Quick facts --}}
            <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-3">
                @php
                    $facts = [
                        ['Rent', $property->rent_range],
                        ['Deposit', '₹' . number_format($property->security_deposit)],
                        ['Total rooms', $property->total_rooms],
                        ['Available', $property->available_rooms],
                    ];
                @endphp
                @foreach($facts as [$label, $value])
                    <div class="p-4 bg-cream rounded-xl border border-ink-900/10">
                        <div class="text-xs text-ink-900/60 uppercase tracking-wide">{{ $label }}</div>
                        <div class="font-display font-bold text-lg mt-1">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            @if($property->description)
            <div class="mt-12">
                <h2 class="font-display font-bold text-2xl mb-3">About this PG</h2>
                <div class="prose text-ink-900/80 leading-relaxed">{!! nl2br(e($property->description)) !!}</div>
            </div>
            @endif

            @if($property->sharing_options)
            <div class="mt-12">
                <h2 class="font-display font-bold text-2xl mb-3">Room types & pricing</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($property->sharing_options as $type => $price)
                        <div class="p-5 bg-cream rounded-2xl border border-ink-900/10">
                            <div class="font-display font-bold text-xl capitalize">{{ $type }} sharing</div>
                            <div class="font-display font-bold text-3xl text-coral-500 mt-2">₹{{ number_format($price) }}</div>
                            <div class="text-xs text-ink-900/60">/ person / month</div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($property->amenities->count())
            <div class="mt-12">
                <h2 class="font-display font-bold text-2xl mb-3">What's included</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($property->amenities as $a)
                        <div class="flex items-center gap-3 p-3 bg-cream rounded-xl border border-ink-900/10">
                            <span class="text-2xl">{{ $a->icon ?: '✓' }}</span>
                            <span class="font-medium">{{ $a->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($property->rules)
            <div class="mt-12">
                <h2 class="font-display font-bold text-2xl mb-3">House rules</h2>
                <div class="p-5 bg-cream rounded-2xl border border-ink-900/10 text-ink-900/80">{!! nl2br(e($property->rules)) !!}</div>
            </div>
            @endif

            @if($property->latitude && $property->longitude)
            <div class="mt-12">
                @if($property->landmarks->count())
            <div class="mt-12">
                <h2 class="font-display font-bold text-2xl mb-3">What's nearby</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($property->landmarks->take(8) as $lm)
                        <a href="{{ route('landmark.show', $lm->slug) }}" class="flex items-center justify-between p-4 bg-cream rounded-xl border border-ink-900/10 hover:border-coral-500 transition">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ $lm->type_icon }}</span>
                                <div>
                                    <div class="font-semibold">{{ $lm->name }}</div>
                                    <div class="text-xs text-ink-900/60">{{ $lm->type_label }}</div>
                                </div>
                            </div>
                            <span class="font-bold text-coral-600">{{ $lm->pivot->distance_km }} km</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
                <h2 class="font-display font-bold text-2xl mb-3">Location</h2>
                <iframe class="w-full h-80 rounded-2xl border-0" loading="lazy"
                        src="https://maps.google.com/maps?q={{ $property->latitude }},{{ $property->longitude }}&output=embed"></iframe>
            </div>
            @endif
        </div>

        {{-- Sticky sidebar / Lead form --}}
        <aside>
            <div class="sticky top-24 bg-white p-6 rounded-2xl border border-ink-900/10 shadow-xl shadow-ink-900/5">
                <div class="text-sm text-ink-900/60">Starting at</div>
                <div class="font-display font-black text-4xl text-ink-950">{{ $property->rent_range }}<span class="text-base font-medium text-ink-900/60">/mo</span></div>

                <form action="{{ route('leads.store') }}" method="POST" class="mt-6 space-y-3">
                    @csrf
                    <input type="hidden" name="property_id" value="{{ $property->id }}">
                    <input name="name" required placeholder="Your name" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
                    <input name="phone" required placeholder="Phone number" pattern="[0-9]{10}" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
                    <input name="email" type="email" placeholder="Email (optional)" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
                    <input name="move_in_date" type="date" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
                    <textarea name="message" rows="3" placeholder="Anything specific?" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500"></textarea>

                    <button class="w-full py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg transition">
                        Book free site visit →
                    </button>
                </form>

                <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}?text={{ urlencode('Hi, I want details about ' . $property->name . ' — ' . url()->current()) }}"
                   target="_blank"
                   class="mt-3 flex items-center justify-center gap-2 py-3 border-2 border-emerald-500 text-emerald-700 rounded-xl font-semibold hover:bg-emerald-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    WhatsApp Us
                </a>
                <p class="text-xs text-center text-ink-900/50 mt-3">Zero brokerage · Free site visit · Reply in 30 min</p>
            </div>
        </aside>
    </div>

    {{-- Similar --}}
    @if($similar->count())
    <section class="mt-24">
        <h2 class="font-display font-bold text-3xl mb-8">Similar PGs nearby</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($similar as $p)
                <x-property-card :property="$p" />
            @endforeach
        </div>
    </section>
    @endif
</section>
@endsection
