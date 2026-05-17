{{-- Reusable property card. Used on home, search, city, locality pages. --}}
@php
    $cover = $property->cover_image
        ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
        : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&q=80';
@endphp

<a href="{{ route('property.show', $property->slug) }}" class="hover-lift group block bg-white rounded-2xl overflow-hidden border border-ink-100 transition-all">

    {{-- Image with overlay badges --}}
    <div class="relative aspect-[4/3] overflow-hidden bg-ink-100">
        <img src="{{ $cover }}"
             alt="{{ $property->name }}"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
             loading="lazy">

        {{-- Gradient overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

        {{-- Top badges --}}
        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
            @if($property->is_verified)
                <span class="px-2.5 py-1 rounded-full bg-white/95 backdrop-blur text-emerald-700 text-xs font-bold flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Verified
                </span>
            @endif
            @if($property->is_featured)
                <span class="px-2.5 py-1 rounded-full bg-coral-500 text-white text-xs font-bold">⭐ Featured</span>
            @endif
        </div>

        {{-- Gender badge (top right) --}}
        <div class="absolute top-3 right-3">
            <span class="px-2.5 py-1 rounded-full bg-ink-950/80 backdrop-blur text-white text-xs font-bold capitalize">
                @if($property->gender === 'male') 👨 Boys
                @elseif($property->gender === 'female') 👩 Girls
                @else 👥 Unisex
                @endif
            </span>
        </div>

        {{-- Bottom price overlay --}}
        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <div class="flex items-end justify-between gap-2">
                <div>
                    <div class="text-xs opacity-90">Starts from</div>
                    <div class="font-display font-black text-2xl">₹{{ number_format($property->rent_min) }}<span class="text-sm font-normal">/mo</span></div>
                </div>
                @if($property->available_rooms > 0)
                    <span class="px-2 py-1 rounded-full bg-emerald-500 text-white text-xs font-bold">{{ $property->available_rooms }} rooms left</span>
                @else
                    <span class="px-2 py-1 rounded-full bg-amber-500 text-white text-xs font-bold">Full</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Card body --}}
    <div class="p-5">
        <h3 class="font-display font-bold text-lg text-ink-950 group-hover:text-coral-500 transition-colors line-clamp-1">{{ $property->name }}</h3>

        <div class="flex items-center gap-1 text-sm text-ink-600 mt-1">
            <svg class="w-4 h-4 text-coral-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
            <span class="truncate">{{ $property->locality?->name ?? '' }}{{ $property->city?->name ? ', ' . $property->city->name : '' }}</span>
        </div>

        {{-- Amenities --}}
        @if($property->amenities && $property->amenities->count())
            <div class="flex flex-wrap gap-1.5 mt-3">
                @foreach($property->amenities->take(4) as $amenity)
                    <span class="px-2 py-0.5 rounded-full bg-cream-200 text-ink-700 text-xs">{{ $amenity->name }}</span>
                @endforeach
                @if($property->amenities->count() > 4)
                    <span class="px-2 py-0.5 rounded-full bg-cream-200 text-ink-700 text-xs">+{{ $property->amenities->count() - 4 }}</span>
                @endif
            </div>
        @endif

        {{-- Bottom action row --}}
        <div class="mt-4 flex items-center justify-between pt-3 border-t border-ink-100">
            <div class="flex items-center gap-1 text-xs text-ink-500">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                {{ $property->view_count ?? 0 }} views
            </div>
            <span class="text-coral-500 font-bold text-sm group-hover:gap-2 flex items-center gap-1 transition-all">
                View details
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </span>
        </div>
    </div>
</a>