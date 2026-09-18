@extends('layouts.app')
@section('title', $property->name . ' — ' . ($property->locality?->name ?? 'PG') . ' | Pizi')
@section('meta_description', Str::limit(strip_tags($property->description ?? "Verified PG in {$property->locality?->name}, starting from ₹" . number_format($property->rent_min) . "/month."), 160))

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

{{-- This page has its own mobile sticky bottom CTA bar — lift the global
     floating chat/contact bubbles above it so they don't overlap it. --}}
<style>
@media (max-width: 480px) {
    #pzContactFab { bottom: 92px !important; }
    .chat-bubble-wrapper { bottom: 92px !important; }
}
</style>
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
        $images->push((object)['image_path' => null, 'placeholder' => true]);
    }
    $imageCount = $images->count();
    
    // Extract embed URL from Google Maps share link
    $mapEmbedUrl = null;
    if (!empty($property->google_map_link)) {
        $link = $property->google_map_link;
        // For maps.app.goo.gl shortened links — direct embed via search
        // For full maps.google.com links — convert to embed
        if (str_contains($link, 'google.com/maps')) {
            // Convert to embed format
            $mapEmbedUrl = $link;
            // If it's a place URL, transform to embed
            if (!str_contains($link, '/embed')) {
                $mapEmbedUrl = str_replace('/maps/place/', '/maps/embed/v1/place?key=&q=', $link);
            }
        }
    }
@endphp

{{-- ===== BREADCRUMB ===== --}}
<div class="bg-cream border-b border-ink-900/5">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-xs text-ink-900/60 overflow-x-auto scrollbar-hide">
            <a href="{{ route('home') }}" class="hover:text-coral-600 whitespace-nowrap">Home</a>
            <span class="text-ink-300">›</span>
            <a href="{{ route('city.show', $property->city?->slug ?? 'delhi') }}" class="hover:text-coral-600 whitespace-nowrap">{{ $property->city?->name ?? 'Delhi' }}</a>
            @if($property->locality)
                <span class="text-ink-300">›</span>
                <a href="{{ route('locality.show', [$property->city?->slug ?? 'delhi', $property->locality->slug]) }}" class="hover:text-coral-600 whitespace-nowrap">{{ $property->locality->name }}</a>
            @endif
            <span class="text-ink-300">›</span>
            <span class="text-ink-950 font-semibold truncate">{{ $property->name }}</span>
        </nav>
    </div>
</div>

{{-- ===== MAIN CONTENT (Left content + Right form) ===== --}}
<section class="bg-cream py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">

            {{-- LEFT: Title + Photos + Details --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- TITLE BLOCK --}}
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        @if($property->is_verified)
                            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                                Verified Property
                            </span>
                        @endif
                        @if($property->is_featured)
                            <span class="px-3 py-1 rounded-full bg-coral-500 text-white text-xs font-bold"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l2.6 5.6 6.2.5-4.7 4.1 1.4 6.1L12 16.2 6.5 19.3l1.4-6.1L3.2 9.1l6.2-.5L12 3z"/></svg> Featured</span>
                        @endif
                        <span class="px-3 py-1 rounded-full bg-coral-50 text-coral-700 text-xs font-bold uppercase tracking-wider">{{ ucfirst($property->property_type ?? 'pg') }}</span>
                        <span class="px-3 py-1 rounded-full bg-ink-100 text-ink-700 text-xs font-bold capitalize">
                            @if($property->gender === 'male') <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg> Boys only
                            @elseif($property->gender === 'female') <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg> Girls only
                            @else <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2 20c0-3.3 3.1-6 7-6s7 2.7 7 6"/><circle cx="17" cy="9" r="2.4"/><path d="M15.5 13.2c2.6.4 4.5 2.3 4.5 4.8v2"/></svg> Unisex
                            @endif
                        </span>
                    </div>

                    <h1 class="font-display font-black text-3xl lg:text-5xl text-ink-950 leading-tight">{{ $property->name }}</h1>

                    <div class="flex items-center gap-2 text-ink-700 mt-3">
                        <svg class="w-5 h-5 text-coral-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                        <span class="text-sm lg:text-base">{{ $property->address_line }}, {{ $property->locality?->name }}, {{ $property->city?->name }}</span>
                    </div>

                    <div class="flex flex-wrap gap-4 mt-4 text-sm">
                        <div class="flex items-center gap-1.5 text-ink-700">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg>
                            {{ number_format($property->view_count ?? 0) }} views
                        </div>
                        <div class="flex items-center gap-1.5 text-ink-700">
                            <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/><path d="M3 18v2M21 18v2M3 13V9a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v3"/></svg>️ {{ $property->total_rooms ?? 0 }} rooms
                        </div>
                        @if($property->available_rooms > 0)
                            <div class="text-emerald-700 font-bold"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" stroke-width="2.5"/></svg> {{ $property->available_rooms }} rooms available</div>
                        @else
                            <div class="text-amber-700 font-bold"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l10 18H2L12 3z"/><path d="M12 10v4"/><circle cx="12" cy="17" r="0.8" fill="currentColor" stroke="none"/></svg>️ Fully occupied</div>
                        @endif
                    </div>
                </div>

                {{-- PHOTOS --}}
                <div class="bg-white rounded-2xl p-4 lg:p-6 border border-ink-900/10">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-display font-bold text-xl text-ink-950"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="14" r="3.5"/></svg> Photos @if(!($images->first()->placeholder ?? false)) ({{ $imageCount }}) @endif</h2>
                        @if($imageCount > 1)
                            <button onclick="openGallery(0)" class="text-coral-500 font-bold text-sm hover:text-coral-600">View all →</button>
                        @endif
                    </div>

                 @if($imageCount === 1 && ($images->first()->placeholder ?? false))
                        <div class="relative rounded-xl overflow-hidden aspect-[16/9] bg-gradient-to-br from-coral-50 to-cream flex flex-col items-center justify-center text-ink-900/40">
                            <span class="text-5xl mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg></span>
                            <span class="text-sm font-semibold">Photos coming soon</span>
                        </div>
                    @elseif($imageCount === 1)
                        @php
                            $mainImg = $images->first()->image_path;
                            $mainImgUrl = str_starts_with($mainImg, 'http') ? $mainImg : asset('storage/' . $mainImg);
                        @endphp
                        <div class="relative rounded-xl overflow-hidden cursor-pointer group aspect-[16/9]" onclick="openGallery(0)">
                            <img loading="lazy" src="{{ $mainImgUrl }}" alt="{{ $property->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        </div>
                    @elseif($imageCount === 2)
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($images as $i => $img)
                                @php
                                    $imgUrl = str_starts_with($img->image_path, 'http') ? $img->image_path : asset('storage/' . $img->image_path);
                                @endphp
                                <div class="relative rounded-xl overflow-hidden cursor-pointer group aspect-square" onclick="openGallery({{ $i }})">
                                    <img loading="lazy" src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                </div>
                            @endforeach
                        </div>
                    @elseif($imageCount === 3)
                        <div class="grid grid-cols-3 gap-3">
                            @php
                                $mainImg = $images[0]->image_path;
                                $mainImgUrl = str_starts_with($mainImg, 'http') ? $mainImg : asset('storage/' . $mainImg);
                            @endphp
                            <div class="col-span-3 sm:col-span-2 sm:row-span-2 relative rounded-xl overflow-hidden cursor-pointer group aspect-square" onclick="openGallery(0)">
                                <img loading="lazy" src="{{ $mainImgUrl }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            </div>
                            @for($i = 1; $i <= 2; $i++)
                                @php
                                    $img = $images[$i]->image_path;
                                    $imgUrl = str_starts_with($img, 'http') ? $img : asset('storage/' . $img);
                                @endphp
                                <div class="relative rounded-xl overflow-hidden cursor-pointer group aspect-square" onclick="openGallery({{ $i }})">
                                    <img loading="lazy" src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                </div>
                            @endfor
                        </div>
                    @else
                        <div class="grid grid-cols-4 gap-3">
                            @php
                                $mainImg = $images[0]->image_path;
                                $mainImgUrl = str_starts_with($mainImg, 'http') ? $mainImg : asset('storage/' . $mainImg);
                            @endphp
                            <div class="col-span-4 sm:col-span-2 sm:row-span-2 relative rounded-xl overflow-hidden cursor-pointer group" onclick="openGallery(0)">
                                <img loading="lazy" src="{{ $mainImgUrl }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            </div>
                            @for($i = 1; $i <= 4; $i++)
                                @if(isset($images[$i]))
                                    @php
                                        $img = $images[$i]->image_path;
                                        $imgUrl = str_starts_with($img, 'http') ? $img : asset('storage/' . $img);
                                    @endphp
                                    <div class="relative rounded-xl overflow-hidden cursor-pointer group aspect-square" onclick="openGallery({{ $i }})">
                                        <img loading="lazy" src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                        @if($i === 4 && $imageCount > 5)
                                            <div class="absolute inset-0 bg-ink-950/70 flex flex-col items-center justify-center text-white">
                                                <span class="text-2xl font-display font-black">+{{ $imageCount - 4 }}</span>
                                                <span class="text-xs uppercase tracking-wider">more</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endfor
                        </div>
                    @endif
                </div>

                {{-- QUICK INFO PILLS --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
                        <div class="text-xs text-ink-900/50 uppercase font-bold">Rent</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1">₹{{ number_format($property->rent_min) }}+</div>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
                        <div class="text-xs text-ink-900/50 uppercase font-bold">Deposit</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1">₹{{ number_format($property->security_deposit ?? 0) }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
                        <div class="text-xs text-ink-900/50 uppercase font-bold">Food</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1">{!! $property->food_included ? '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" stroke-width="2.5"/></svg> Yes' : 'No' !!}</div>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-ink-900/10">
                        <div class="text-xs text-ink-900/50 uppercase font-bold">Type</div>
                        <div class="font-display font-black text-lg text-ink-950 mt-1 capitalize">{{ $property->property_type ?? 'PG' }}</div>
                    </div>
                </div>

                {{-- ABOUT --}}
                @if($property->description)
                    <div class="bg-white rounded-2xl p-6 border border-ink-900/10">
                        <h2 class="font-display font-bold text-xl text-ink-950 mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3z"/><path d="M13.5 6.5l3 3"/></svg> About this PG</h2>
                        <div class="text-ink-700 leading-relaxed text-sm lg:text-base">
                            {!! nl2br(e($property->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- SHARING OPTIONS --}}
                @if($property->sharing_options && (is_array($property->sharing_options) ? count($property->sharing_options) : !empty(json_decode($property->sharing_options, true))))
                    @php
                        $sharing = is_array($property->sharing_options) ? $property->sharing_options : (json_decode($property->sharing_options, true) ?: []);
                    @endphp
                    <div class="bg-white rounded-2xl p-6 border border-ink-900/10">
                        <h2 class="font-display font-bold text-xl text-ink-950 mb-4"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="17" cy="14.2" r="1.1" fill="currentColor" stroke="none"/></svg> Room sharing options</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach($sharing as $type => $price)
                                <div class="p-5 rounded-xl border-2 border-ink-900/10 hover:border-coral-500 transition cursor-pointer">
                                    <div class="text-xs text-ink-900/50 uppercase font-bold capitalize">{{ $type }} sharing</div>
                                    <div class="font-display font-black text-2xl text-ink-950 mt-1">₹{{ number_format($price) }}<span class="text-sm font-normal text-ink-900/50">/mo</span></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- AMENITIES --}}
                @if($property->amenities && $property->amenities->count())
                    <div class="bg-white rounded-2xl p-6 border border-ink-900/10">
                        <h2 class="font-display font-bold text-xl text-ink-950 mb-4"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/><path d="M19 15l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/></svg> Amenities</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($property->amenities as $amenity)
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-cream hover:bg-coral-50 transition group">
                                    <div class="w-10 h-10 rounded-full bg-white border-2 border-coral-100 flex items-center justify-center flex-shrink-0 text-xl group-hover:border-coral-500 transition">
                                        {!! $amenity->icon ? e($amenity->icon) : '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/><path d="M19 15l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/></svg>' !!}
                                    </div>
                                    <span class="text-sm font-semibold text-ink-700">{{ $amenity->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- MEAL TIMINGS --}}
                @php
                    $foodTiming = is_array($property->food_timing) ? $property->food_timing : (json_decode($property->food_timing ?? '', true) ?: []);
                    $mealLabels = ['breakfast' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a8 6 0 0 1-16 0z"/><path d="M12 5v3"/></svg> Breakfast', 'lunch' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a8 6 0 0 1-16 0z"/><path d="M12 5v3"/></svg> Lunch', 'dinner' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="7"/><path d="M12 13h.01"/></svg> Dinner'];
                    $dayLabels = ['all' => 'Everyday', 'weekdays' => 'Weekdays only (Mon–Fri)', 'weekends' => 'Weekends only (Sat–Sun)'];
                @endphp
                @if($property->food_type || count($foodTiming))
                    <div class="bg-white rounded-2xl p-6 border border-ink-900/10">
                        <h2 class="font-display font-bold text-xl text-ink-950 mb-4"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v8M4 3v5a2 2 0 0 0 4 0V3M6 11v10M18 3c-1.5 0-3 1.5-3 4s1.5 4 3 4v10"/></svg> Food & meal timings</h2>
                        @if($property->food_type)
                            <div class="mb-4">
                                <span class="inline-block px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase">
                                    {!! ['veg' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><circle cx="14" cy="7" r="2.6"/><circle cx="12" cy="10.5" r="2.6"/><path d="M12 13v8"/></svg> Veg only', 'non_veg' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c2.5 2.5 3.5 5.5 2 7-1.5 1.5-4.5.5-7-2-2-2-4-5.5-2-7.5s5.5 0 7.5 2z"/><path d="M13.5 11.5L20 5"/></svg> Non-veg only', 'both' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><circle cx="14" cy="7" r="2.6"/><circle cx="12" cy="10.5" r="2.6"/><path d="M12 13v8"/></svg><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c2.5 2.5 3.5 5.5 2 7-1.5 1.5-4.5.5-7-2-2-2-4-5.5-2-7.5s5.5 0 7.5 2z"/><path d="M13.5 11.5L20 5"/></svg> Veg & Non-veg both'][$property->food_type] ?? e($property->food_type) !!}
                                </span>
                            </div>
                        @endif
                        @if(count($foodTiming))
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @foreach($mealLabels as $meal => $label)
                                    @if(!empty($foodTiming[$meal]) && ($foodTiming[$meal]['days'] ?? 'none') !== 'none')
                                        <div class="p-4 rounded-xl bg-cream border border-ink-900/10">
                                            <div class="text-sm font-bold text-ink-950">{{ $label }}</div>
                                            @if(!empty($foodTiming[$meal]['timing']))
                                                <div class="text-sm text-ink-700 mt-1">{{ $foodTiming[$meal]['timing'] }}</div>
                                            @endif
                                            <div class="text-xs text-ink-900/50 mt-1">{{ $dayLabels[$foodTiming[$meal]['days']] ?? 'Everyday' }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- BUILDING DETAILS --}}
                @if($property->construction_year || !is_null($property->pet_allowed) || !is_null($property->guest_entry_allowed))
                    <div class="bg-white rounded-2xl p-6 border border-ink-900/10">
                        <h2 class="font-display font-bold text-xl text-ink-950 mb-4"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1M10 21v-3h4v3"/></svg> Building details</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @if($property->construction_year)
                                <div class="p-3 rounded-xl bg-cream text-center">
                                    <div class="text-xs text-ink-900/50 uppercase font-bold">Built in</div>
                                    <div class="font-display font-black text-lg text-ink-950 mt-1">{{ $property->construction_year }}</div>
                                </div>
                            @endif
                            @if(!is_null($property->pet_allowed))
                                <div class="p-3 rounded-xl bg-cream text-center">
                                    <div class="text-xs text-ink-900/50 uppercase font-bold">Pets</div>
                                    <div class="font-display font-black text-lg text-ink-950 mt-1">{!! $property->pet_allowed ? '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="7" cy="9" r="1.6"/><circle cx="11" cy="7" r="1.6"/><circle cx="15" cy="7" r="1.6"/><circle cx="18" cy="10" r="1.6"/><path d="M12 12c-3 0-5.5 2-5.5 4.5S8.5 20 12 20s5.5-1 5.5-3.5S15 12 12 12z"/></svg> Allowed' : '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5l14 14M19 5L5 19" stroke-width="2.2"/></svg> Not allowed' !!}</div>
                                </div>
                            @endif
                            @if(!is_null($property->guest_entry_allowed))
                                <div class="p-3 rounded-xl bg-cream text-center">
                                    <div class="text-xs text-ink-900/50 uppercase font-bold">Guests</div>
                                    <div class="font-display font-black text-lg text-ink-950 mt-1">{!! $property->guest_entry_allowed ? '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="18" rx="1"/><path d="M14 12h.01"/></svg> Allowed' : '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5l14 14M19 5L5 19" stroke-width="2.2"/></svg> Not allowed' !!}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- NEARBY LOCATIONS --}}
                @if($property->landmarks && $property->landmarks->count())
                    <div class="bg-white rounded-2xl p-6 border border-ink-900/10">
                        <h2 class="font-display font-bold text-xl text-ink-950 mb-4"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg> Nearby locations</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($property->landmarks as $lm)
                                <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-cream">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-lg flex-shrink-0">{!! ['metro' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="13" rx="3"/><circle cx="9" cy="14" r="1"/><circle cx="15" cy="14" r="1"/><path d="M7 20l2-3M17 20l-2-3M5 10h14"/></svg>', 'hospital' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 8v8M8 12h8"/></svg>', 'mall' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l1 12H5L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>', 'university' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-4 10 4-10 4-10-4z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/><path d="M22 9v6"/></svg>'][$lm->type] ?? '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>' !!}</span>
                                        <span class="text-sm font-semibold text-ink-700 truncate">{{ $lm->name }}</span>
                                    </div>
                                    @if($lm->pivot->distance_km)
                                        <span class="text-xs font-bold text-ink-900/50 flex-shrink-0">{{ $lm->pivot->distance_km }} km</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- RULES --}}
                @if($property->rules)
                    <div class="bg-white rounded-2xl p-6 border border-ink-900/10">
                        <h2 class="font-display font-bold text-xl text-ink-950 mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 11h6M9 15h6"/></svg> House rules</h2>
                        <div class="text-ink-700 leading-relaxed text-sm lg:text-base">{!! nl2br(e($property->rules)) !!}</div>
                    </div>
                @endif
            </div>

            {{-- RIGHT: Sticky booking form --}}
            <div class="lg:col-span-1">
                <div class="lg:sticky lg:top-24 space-y-4">
                    <div class="bg-white rounded-2xl border border-ink-900/10 shadow-xl shadow-ink-950/5 overflow-hidden">

                        <div class="bg-gradient-to-br from-ink-950 to-ink-900 text-cream p-6">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm opacity-70">From</span>
                                <span class="font-display font-black text-4xl">₹{{ number_format($property->rent_min) }}</span>
                                <span class="text-sm opacity-70">/mo</span>
                            </div>
                            @if($property->rent_max && $property->rent_max != $property->rent_min)
                                <div class="text-xs opacity-60 mt-1">Up to ₹{{ number_format($property->rent_max) }}/month</div>
                            @endif

                            <div class="mt-4 flex items-center gap-2 text-emerald-300 text-sm font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Free site visit · No brokerage
                            </div>
                        </div>
<form method="POST" action="{{ route('leads.store') }}" class="p-6 space-y-3">
    @csrf
    <input type="hidden" name="property_id" value="{{ $property->id }}">
    <input type="hidden" name="preferred_city" value="{{ $property->city?->name }}">
    <input type="hidden" name="preferred_locality" value="{{ $property->locality?->name }}">
    <input type="hidden" name="source" value="website">
    
    @auth
        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
    @endauth

    <h3 class="font-display font-bold text-lg text-ink-950">Book a free site visit</h3>

    @auth
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs text-emerald-900">
            <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke-width="2.2"/></svg> Logged in as <strong>{{ auth()->user()->name }}</strong> — your details are pre-filled
        </div>
    @endauth

    <input name="name" required placeholder="Your name *" 
       value="{{ auth()->user()->name ?? '' }}"
       class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm">
    
   <input name="phone" required type="tel" placeholder="Phone number *" 
       value="{{ auth()->user()->phone ?? '' }}"
       class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm">
    
<input name="email" type="email" placeholder="Email (optional)" 
       value="{{ auth()->user()->email ?? '' }}"
       class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm">
    
    <input name="move_in_date" type="date" min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm">
    
    <textarea name="message" rows="2" placeholder="Any specific requirements?" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm resize-none"></textarea>

    <button type="submit" class="w-full py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-base transition shadow-lg shadow-coral-500/30">
        Book free site visit →
    </button>

    <p class="text-xs text-center text-ink-900/50">
        @auth
            <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke-width="2.2"/></svg> Your tenant journey will auto-update
        @else
            Our team will call within 30 minutes
        @endauth
    </p>
</form>
                        <div class="px-6 pb-6">
                            <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}?text={{ urlencode('Hi, I am interested in ' . $property->name) }}" target="_blank" class="flex items-center justify-center gap-2 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm transition shadow-lg shadow-emerald-500/20">
                                <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.5 9 9 0 0 1-3.6-.7L3 21l1.7-5.5A8.4 8.4 0 0 1 12.6 3a8.4 8.4 0 0 1 8.4 8.5z"/></svg> WhatsApp Us
                            </a>
                        </div>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center">
                        <div class="text-2xl mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5.5c0 4.6-3 8.1-7 9.5-4-1.4-7-4.9-7-9.5V6l7-3z"/></svg>️</div>
                        <div class="font-bold text-emerald-900 text-sm">100% Verified Property</div>
                        <div class="text-xs text-emerald-700 mt-1">Physically inspected by our team</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== REVIEWS ===== --}}
@include('partials.reviews-section', ['property' => $property])
{{-- ===== LOCATION (FULL WIDTH) ===== --}}
@if(!empty($property->google_map_link) || ($property->latitude && $property->longitude))
<section class="py-8 lg:py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="bg-cream rounded-2xl p-6 lg:p-8 border border-ink-900/10">
            <div class="flex items-start justify-between gap-4 flex-wrap mb-5">
                <div>
                    <h2 class="font-display font-bold text-2xl lg:text-3xl text-ink-950"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg> Location</h2>
                    <p class="text-ink-700 text-sm lg:text-base mt-2">{{ $property->address_line }}, {{ $property->locality?->name }}, {{ $property->city?->name }} - {{ $property->pincode }}</p>
                </div>
                @if(!empty($property->google_map_link) || ($property->latitude && $property->longitude))
                <a href="{{ ($property->latitude && $property->longitude) ? 'https://www.google.com/maps?q='.$property->latitude.','.$property->longitude : $property->google_map_link }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-bold text-sm transition shadow-lg shadow-blue-500/20">
                        <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-2 6-4 1 2-6z"/></svg> Get Directions
                    </a>
                @endif
            </div>

  {{-- MAP EMBED — Leaflet (free, no API key, no "Place info couldn't load" glitch) --}}
            <div id="propertyLocationMap" style="height: 450px; width: 100%; border-radius: 16px; border: 2px solid #e5e7eb;"></div>

            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
            (function () {
                function ready(fn) {
                    if (document.readyState !== 'loading') fn();
                    else document.addEventListener('DOMContentLoaded', fn);
                }

                function renderMap(lat, lng, label) {
                    var el = document.getElementById('propertyLocationMap');
                    if (!el || typeof L === 'undefined') return;

                    var map = L.map('propertyLocationMap', { scrollWheelZoom: false }).setView([lat, lng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 19
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map).bindPopup(label || '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg> Property location').openPopup();

                    setTimeout(function () { map.invalidateSize(); }, 200);
                }

                ready(function () {
                    var lat = {{ $property->latitude ? $property->latitude : 'null' }};
                    var lng = {{ $property->longitude ? $property->longitude : 'null' }};

                    if (lat && lng) {
                        renderMap(lat, lng, @json($property->name));
                        return;
                    }

                    // Fallback: geocode the address text (only when exact coordinates aren't saved yet)
                    var query = @json(trim(($property->address_line ?? '') . ', ' . ($property->locality?->name ?? '') . ', ' . ($property->city?->name ?? '') . ' ' . ($property->pincode ?? '')));
                    var url = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=in&limit=1&q=' + encodeURIComponent(query);

                    fetch(url, { headers: { 'Accept-Language': 'en' } })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            if (!d.length) {
                                document.getElementById('propertyLocationMap').innerHTML =
                                    '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#888; font-size:14px;">Map location not available yet</div>';
                                return;
                            }
                            renderMap(parseFloat(d[0].lat), parseFloat(d[0].lon), @json($property->name));
                        })
                        .catch(function () {
                            document.getElementById('propertyLocationMap').innerHTML =
                                '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#888; font-size:14px;">Map location not available yet</div>';
                        });
                });
            })();
            </script>
        </div>
    </div>
</section>
@endif

{{-- ===== SIMILAR PGS ===== --}}
@if(isset($similar) && $similar->count())
<section class="py-12 lg:py-16 bg-cream">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <h2 class="font-display font-bold text-2xl lg:text-3xl text-ink-950 mb-6">Similar PGs nearby</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($similar->take(3) as $sim)
                @include('components.property-card', ['property' => $sim])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== MOBILE STICKY BOTTOM CTA ===== --}}
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-ink-100 p-3 shadow-2xl">
    <div class="flex items-center gap-2">
        <div class="flex-1 px-2">
            <div class="text-xs text-ink-900/50">From</div>
            <div class="font-display font-black text-xl text-ink-950">₹{{ number_format($property->rent_min) }}<span class="text-xs font-normal">/mo</span></div>
        </div>
        <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}?text={{ urlencode('Hi, I want to book ' . $property->name) }}" target="_blank" class="px-4 py-3 bg-emerald-500 text-white rounded-xl font-bold text-sm"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.5 9 9 0 0 1-3.6-.7L3 21l1.7-5.5A8.4 8.4 0 0 1 12.6 3a8.4 8.4 0 0 1 8.4 8.5z"/></svg> WhatsApp</a>
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'});" class="flex-1 px-4 py-3 bg-coral-500 text-white rounded-xl font-bold text-sm">
            Book →
        </button>
    </div>
</div>

{{-- ===== GALLERY LIGHTBOX ===== --}}
<div id="galleryLightbox" class="hidden fixed inset-0 z-50 bg-black/95 items-center justify-center">
    <button onclick="closeGallery()" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-2xl">×</button>
    <button onclick="prevImg()" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-2xl">‹</button>
    <button onclick="nextImg()" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-2xl">›</button>
    <img loading="lazy" id="galleryImg" src="" class="max-w-full max-h-full p-12">
    <div id="galleryCounter" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-sm bg-black/50 px-3 py-1 rounded-full"></div>
</div>

@push('scripts')
<script>
const images = @json($images->pluck('image_path'));
const imageUrls = images.filter(i => i).map(i => i.startsWith('http') ? i : '/storage/' + i);
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