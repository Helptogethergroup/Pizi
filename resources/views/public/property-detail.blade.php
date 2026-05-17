@extends('layouts.app')
@section('title', $property->name . ' — ' . ($property->locality?->name ?? 'PG') . ' | PGFind')
@section('meta_description', Str::limit(strip_tags($property->description ?? "Verified PG in {$property->locality?->name}, starting from ₹" . number_format($property->rent_min) . "/month. View photos, amenities, and book a free site visit."), 160))

@push('head')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LodgingBusiness",
  "name": "{{ $property->name }}",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "{{ $property->address_line }}",
    "addressLocality": "{{ $property->locality?->name }}",
    "addressRegion": "{{ $property->city?->name }}",
    "postalCode": "{{ $property->pincode }}",
    "addressCountry": "IN"
  },
  "priceRange": "₹{{ number_format($property->rent_min) }}-₹{{ number_format($property->rent_max) }}"
}
</script>
@endpush

@section('content')

@php
    $images = collect();
    if ($property->cover_image) {
        $images->push((object)['image_path' => $property->cover_image]);
    }
    if (isset($property->images)) {
        foreach ($property->images as $img) {
            $images->push($img);
        }
    }
    if ($images->isEmpty()) {
        $images->push((object)['image_path' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&q=80']);
    }
@endphp

{{-- ===== BREADCRUMB ===== --}}
<div class="bg-cream-200 border-b border-ink-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-xs text-ink-600 overflow-x-auto scrollbar-hide">
            <a href="{{ route('home') }}" class="hover:text-coral-500 whitespace-nowrap">Home</a>
            <span class="text-ink-300">/</span>
            <a href="{{ route('city.show', $property->city?->slug ?? 'delhi') }}" class="hover:text-coral-500 whitespace-nowrap">{{ $property->city?->name ?? 'Delhi' }}</a>
            <span class="text-ink-300">/</span>
            <a href="{{ route('locality.show', [$property->city?->slug ?? 'delhi', $property->locality?->slug ?? '#']) }}" class="hover:text-coral-500 whitespace-nowrap">{{ $property->locality?->name ?? '' }}</a>
            <span class="text-ink-300">/</span>
            <span class="text-ink-950 font-semibold truncate">{{ $property->name }}</span>
        </nav>
    </div>
</div>

{{-- ===== GALLERY ===== --}}
<section class="bg-ink-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 md:gap-3 h-[300px] md:h-[500px]">

            {{-- Main big image --}}
            <div class="md:col-span-3 md:row-span-2 relative rounded-2xl overflow-hidden group cursor-pointer">
                @php
                    $mainImg = $images->first()->image_path;
                    $mainImgUrl = str_starts_with($mainImg, 'http') ? $mainImg : asset('storage/' . $mainImg);
                @endphp
                <img src="{{ $mainImgUrl }}" alt="{{ $property->name }}" class="w-full h-full object-cover" onclick="openGallery(0)">

                <div class="absolute bottom-4 left-4 flex flex-wrap gap-2">
                    @if($property->is_verified)
                        <span class="px-3 py-1.5 rounded-full bg-white/95 text-emerald-700 text-xs font-bold">✓ Verified</span>
                    @endif
                    @if($property->is_featured)
                        <span class="px-3 py-1.5 rounded-full bg-coral-500 text-white text-xs font-bold">⭐ Featured</span>
                    @endif
                </div>
            </div>

            {{-- Side thumbnails (desktop) --}}
            @for($i = 1; $i <= 4; $i++)
                @if(isset($images[$i]))
                    @php
                        $img = $images[$i]->image_path;
                        $imgUrl = str_starts_with($img, 'http') ? $img : asset('storage/' . $img);
                    @endphp
                    <div class="hidden md:block relative rounded-xl overflow-hidden cursor-pointer group {{ $i === 4 ? 'relative' : '' }}" onclick="openGallery({{ $i }})">
                        <img src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                        @if($i === 4 && $images->count() > 5)
                            <div class="absolute inset-0 bg-ink-950/70 flex items-center justify-center">
                                <span class="text-white font-bold">+{{ $images->count() - 4 }} more</span>
                            </div>
                        @endif
                    </div>
                @endif
            @endfor
        </div>

        {{-- View all photos button (mobile) --}}
        @if($images->count() > 1)
            <button onclick="openGallery(0)" class="md:hidden mt-3 w-full py-2.5 bg-white text-ink-950 rounded-xl font-bold text-sm">📸 View all {{ $images->count() }} photos</button>
        @endif
    </div>
</section>

{{-- ===== MAIN CONTENT ===== --}}
<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- LEFT: Property details (2/3 width) --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Title section --}}
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="px-3 py-1 rounded-full bg-coral-50 text-coral-700 text-xs font-bold uppercase tracking-wider">{{ ucfirst($property->property_type ?? 'pg') }}</span>
                        <span class="px-3 py-1 rounded-full bg-ink-100 text-ink-700 text-xs font-bold capitalize">
                            @if($property->gender === 'male') 👨 Boys only
                            @elseif($property->gender === 'female') 👩 Girls only
                            @else 👥 Unisex
                            @endif
                        </span>
                    </div>

                    <h1 class="font-display font-black text-3xl lg:text-5xl text-ink-950 leading-tight">{{ $property->name }}</h1>

                    <div class="flex items-center gap-2 text-ink-600 mt-3">
                        <svg class="w-5 h-5 text-coral-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                        <span>{{ $property->address_line }}, {{ $property->locality?->name }}, {{ $property->city?->name }}</span>
                    </div>

                    {{-- Stats row --}}
                    <div class="flex flex-wrap gap-4 mt-4 text-sm">
                        <div class="flex items-center gap-1.5 text-ink-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            {{ number_format($property->view_count ?? 0) }} views
                        </div>
                        <div class="flex items-center gap-1.5 text-ink-600">
                            🛏️ {{ $property->total_rooms ?? 0 }} rooms
                        </div>
                        @if($property->available_rooms > 0)
                            <div class="text-emerald-700 font-bold">✓ {{ $property->available_rooms }} rooms available</div>
                        @else
                            <div class="text-amber-700 font-bold">⚠️ Fully occupied</div>
                        @endif
                    </div>
                </div>

                {{-- Quick info pills --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-cream-200 p-4 rounded-2xl">
                        <div class="text-xs text-ink-500 uppercase font-bold">Rent</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1">₹{{ number_format($property->rent_min) }}+</div>
                    </div>
                    <div class="bg-cream-200 p-4 rounded-2xl">
                        <div class="text-xs text-ink-500 uppercase font-bold">Deposit</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1">₹{{ number_format($property->security_deposit ?? 0) }}</div>
                    </div>
                    <div class="bg-cream-200 p-4 rounded-2xl">
                        <div class="text-xs text-ink-500 uppercase font-bold">Food</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1">{{ $property->food_included ? '✓ Included' : 'Not incl.' }}</div>
                    </div>
                    <div class="bg-cream-200 p-4 rounded-2xl">
                        <div class="text-xs text-ink-500 uppercase font-bold">Type</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1 capitalize">{{ $property->property_type ?? 'PG' }}</div>
                    </div>
                </div>

                {{-- About this PG --}}
                @if($property->description)
                    <div class="bg-white rounded-2xl p-6 border border-ink-100">
                        <h2 class="font-display font-bold text-2xl text-ink-950 mb-4">About this PG</h2>
                        <div class="prose prose-ink max-w-none text-ink-700 leading-relaxed">
                            {!! nl2br(e($property->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- Sharing options --}}
                @if($property->sharing_options && (is_array($property->sharing_options) ? count($property->sharing_options) : !empty(json_decode($property->sharing_options, true))))
                    @php
                        $sharing = is_array($property->sharing_options) ? $property->sharing_options : (json_decode($property->sharing_options, true) ?: []);
                    @endphp
                    <div class="bg-white rounded-2xl p-6 border border-ink-100">
                        <h2 class="font-display font-bold text-2xl text-ink-950 mb-4">💰 Room sharing options</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach($sharing as $type => $price)
                                <div class="p-4 rounded-xl border-2 border-ink-100 hover:border-coral-500 transition">
                                    <div class="text-xs text-ink-500 uppercase font-bold capitalize">{{ $type }} sharing</div>
                                    <div class="font-display font-black text-2xl text-ink-950 mt-1">₹{{ number_format($price) }}<span class="text-sm font-normal text-ink-500">/mo</span></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Amenities --}}
                @if($property->amenities && $property->amenities->count())
                    <div class="bg-white rounded-2xl p-6 border border-ink-100">
                        <h2 class="font-display font-bold text-2xl text-ink-950 mb-4">✨ Amenities</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($property->amenities as $amenity)
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-cream-200">
                                    <div class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <span class="text-sm font-semibold text-ink-700">{{ $amenity->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Rules --}}
                @if($property->rules)
                    <div class="bg-white rounded-2xl p-6 border border-ink-100">
                        <h2 class="font-display font-bold text-2xl text-ink-950 mb-4">📋 House rules</h2>
                        <div class="text-ink-700 leading-relaxed">{!! nl2br(e($property->rules)) !!}</div>
                    </div>
                @endif

                {{-- Location / Map --}}
                <div class="bg-white rounded-2xl p-6 border border-ink-100">
                    <h2 class="font-display font-bold text-2xl text-ink-950 mb-4">📍 Location</h2>
                    <p class="text-ink-700 mb-4">{{ $property->address_line }}, {{ $property->locality?->name }}, {{ $property->city?->name }} - {{ $property->pincode }}</p>

                    @if($property->latitude && $property->longitude)
                        <div class="rounded-xl overflow-hidden border border-ink-100">
                            <iframe
                                width="100%" height="350"
                                frameborder="0" style="border:0"
                                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $property->longitude - 0.005 }}%2C{{ $property->latitude - 0.005 }}%2C{{ $property->longitude + 0.005 }}%2C{{ $property->latitude + 0.005 }}&amp;layer=mapnik&amp;marker={{ $property->latitude }}%2C{{ $property->longitude }}"
                                allowfullscreen></iframe>
                        </div>
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $property->latitude }},{{ $property->longitude }}" target="_blank" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-lg bg-blue-500 text-white text-sm font-bold hover:bg-blue-600">
                            🧭 Get directions on Google Maps
                        </a>
                    @endif
                </div>

                {{-- Similar PGs --}}
                @if(isset($similar) && $similar->count())
                    <div>
                        <h2 class="font-display font-bold text-2xl text-ink-950 mb-4">Similar PGs nearby</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($similar->take(2) as $sim)
                                @include('components.property-card', ['property' => $sim])
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT: Sticky booking form (1/3 width) --}}
            <div class="lg:sticky lg:top-24 self-start">
                <div class="bg-white rounded-2xl border border-ink-100 shadow-xl shadow-ink-950/5 overflow-hidden">

                    {{-- Price header --}}
                    <div class="bg-gradient-to-br from-ink-950 to-ink-900 text-cream p-6">
                        <div class="flex items-baseline gap-2">
                            <span class="text-sm opacity-70">From</span>
                            <span class="font-display font-black text-4xl">₹{{ number_format($property->rent_min) }}</span>
                            <span class="text-sm opacity-70">/month</span>
                        </div>
                        @if($property->rent_max && $property->rent_max != $property->rent_min)
                            <div class="text-xs opacity-60 mt-1">Up to ₹{{ number_format($property->rent_max) }}/month</div>
                        @endif

                        <div class="mt-4 flex items-center gap-2 text-emerald-300 text-sm font-bold">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                            Free site visit · No brokerage
                        </div>
                    </div>

                    {{-- Lead form --}}
                    <form method="POST" action="{{ route('leads.store') }}" class="p-6 space-y-3">
                        @csrf
                        <input type="hidden" name="property_id" value="{{ $property->id }}">
                        <input type="hidden" name="preferred_city" value="{{ $property->city?->name }}">
                        <input type="hidden" name="preferred_locality" value="{{ $property->locality?->name }}">

                        <h3 class="font-display font-bold text-lg text-ink-950">Book a free site visit</h3>

                        <div>
                            <input name="name" required placeholder="Your name *" class="w-full px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none text-sm">
                        </div>
                        <div>
                            <input name="phone" required type="tel" placeholder="Phone number *" class="w-full px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none text-sm">
                        </div>
                        <div>
                            <input name="email" type="email" placeholder="Email (optional)" class="w-full px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none text-sm">
                        </div>
                        <div>
                            <input name="move_in_date" type="date" min="{{ date('Y-m-d') }}" placeholder="Move-in date" class="w-full px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none text-sm">
                        </div>
                        <div>
                            <textarea name="message" rows="2" placeholder="Any specific requirements?" class="w-full px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none text-sm resize-none"></textarea>
                        </div>

                        <button type="submit" class="w-full py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-base transition shadow-lg shadow-coral-500/30">
                            Book free site visit →
                        </button>

                        <p class="text-xs text-center text-ink-500">Our team will call within 30 minutes</p>
                    </form>

                    {{-- Quick contact --}}
                    <div class="px-6 pb-6 grid grid-cols-2 gap-2">
                        <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}?text={{ urlencode('Hi, I am interested in ' . $property->name . ' - ' . route('property.show', $property->slug)) }}" target="_blank" class="flex items-center justify-center gap-2 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm">
                            💬 WhatsApp
                        </a>
                        <a href="tel:{{ env('BRAND_PHONE', '+919999999999') }}" class="flex items-center justify-center gap-2 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-bold text-sm">
                            📞 Call
                        </a>
                    </div>
                </div>

                {{-- Trust badge --}}
                <div class="mt-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center">
                    <div class="text-2xl mb-2">🛡️</div>
                    <div class="font-bold text-emerald-900 text-sm">100% Verified Property</div>
                    <div class="text-xs text-emerald-700 mt-1">Physically inspected by our team</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Mobile sticky bottom CTA --}}
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-ink-100 p-3 shadow-xl">
    <div class="flex items-center gap-2">
        <div class="flex-1 px-2">
            <div class="text-xs text-ink-500">From</div>
            <div class="font-display font-black text-xl text-ink-950">₹{{ number_format($property->rent_min) }}<span class="text-xs font-normal">/mo</span></div>
        </div>
        <a href="#" onclick="document.querySelector('form[action*=&quot;leads&quot;] input[name=name]').focus(); window.scrollTo({top: 0, behavior: 'smooth'}); return false;" class="px-6 py-3 bg-coral-500 text-white rounded-xl font-bold text-sm shadow-lg shadow-coral-500/30">
            Book visit →
        </a>
    </div>
</div>

{{-- Gallery lightbox --}}
<div id="galleryLightbox" class="hidden fixed inset-0 z-50 bg-black/95 items-center justify-center">
    <button onclick="closeGallery()" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-2xl">×</button>
    <button onclick="prevImg()" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-2xl">‹</button>
    <button onclick="nextImg()" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-2xl">›</button>
    <img id="galleryImg" src="" class="max-w-full max-h-full p-12">
    <div id="galleryCounter" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-sm bg-black/50 px-3 py-1 rounded-full"></div>
</div>

@push('scripts')
<script>
const images = @json($images->pluck('image_path'));
const imageUrls = images.map(i => i.startsWith('http') ? i : '/storage/' + i);
let currentImg = 0;

function openGallery(i) {
    currentImg = i;
    document.getElementById('galleryImg').src = imageUrls[i];
    document.getElementById('galleryCounter').textContent = (i+1) + ' / ' + imageUrls.length;
    document.getElementById('galleryLightbox').classList.remove('hidden');
    document.getElementById('galleryLightbox').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeGallery() {
    document.getElementById('galleryLightbox').classList.add('hidden');
    document.getElementById('galleryLightbox').classList.remove('flex');
    document.body.style.overflow = '';
}

function prevImg() {
    currentImg = (currentImg - 1 + imageUrls.length) % imageUrls.length;
    openGallery(currentImg);
}

function nextImg() {
    currentImg = (currentImg + 1) % imageUrls.length;
    openGallery(currentImg);
}

document.addEventListener('keydown', (e) => {
    if (document.getElementById('galleryLightbox').classList.contains('hidden')) return;
    if (e.key === 'Escape') closeGallery();
    if (e.key === 'ArrowLeft') prevImg();
    if (e.key === 'ArrowRight') nextImg();
});
</script>
@endpush

@endsection 