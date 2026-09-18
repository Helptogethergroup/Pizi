@php
    $cover = $property->cover_image
        ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
        : null;
    // Up to 4 photos for the hover-carousel — falls back to just the cover
    // (or a single placeholder) when the property has no gallery images.
    $carouselUrls = $property->images->take(4)->map(fn ($img) =>
        str_starts_with($img->image_path, 'http') ? $img->image_path : asset('storage/' . $img->image_path)
    )->filter()->values();
    if ($carouselUrls->isEmpty() && $cover) $carouselUrls = collect([$cover]);
@endphp

<a href="{{ route('property.show', $property->slug) }}" class="group block rounded-2xl border border-ink-900/10 bg-white overflow-hidden hover:border-coral-500 hover:shadow-xl hover:shadow-ink-900/5 transition">
    <div class="pz-card-carousel aspect-[4/3] bg-cream relative overflow-hidden">
        @if($carouselUrls->isNotEmpty())
            @foreach($carouselUrls as $i => $url)
                <img loading="lazy" src="{{ $url }}" alt="{{ $property->name }}"
                     class="pz-carousel-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-300 {{ $i === 0 ? 'opacity-100' : 'opacity-0' }} group-hover:scale-105 transition-transform duration-500">
            @endforeach
            @if($carouselUrls->count() > 1)
                <div class="pz-carousel-dots absolute bottom-2.5 left-1/2 -translate-x-1/2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                    @foreach($carouselUrls as $i => $url)
                        <span class="w-1.5 h-1.5 rounded-full bg-white/60 {{ $i === 0 ? 'pz-dot-active' : '' }}" style="{{ $i === 0 ? 'background:#fff' : '' }}"></span>
                    @endforeach
                </div>
            @endif
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-coral-50 to-cream text-coral-300"><svg class="w-14 h-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg></div>
        @endif
        @if($property->is_verified)
            <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-white/95 backdrop-blur text-xs font-semibold text-emerald-700 flex items-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                Verified
            </span>
        @endif
        @if($property->is_featured)
            <span class="absolute top-3 right-3 px-3 py-1 rounded-full bg-coral-500 text-white text-xs font-semibold flex items-center gap-1">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l2.6 5.6 6.2.5-4.7 4.1 1.4 6.1L12 16.2 6.5 19.3l1.4-6.1L3.2 9.1l6.2-.5L12 3z"/></svg>
                Featured
            </span>
        @endif
    </div>

    <div class="p-5">
        <div class="flex items-start justify-between gap-2">
            <h3 class="font-display font-bold text-lg leading-tight group-hover:text-coral-600 transition">{{ $property->name }}</h3>
            <span class="text-xs px-2 py-1 rounded-md bg-ink-100 text-ink-700 capitalize whitespace-nowrap">{{ $property->gender }}</span>
        </div>

     <div class="text-sm text-ink-900/60 mt-1 truncate flex items-center gap-1">
            <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>
            {{ $property->locality?->name }}{{ $property->city?->name ? ', ' . $property->city->name : '' }}
        </div>
        @if($property->is_verified && $property->verified_at)
            <div class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                Last verified: {{ \Carbon\Carbon::parse($property->verified_at)->format('d M Y') }}
            </div>
        @endif

        {{-- AMENITIES with icons --}}
        @if($property->amenities && $property->amenities->count())
            <div class="flex flex-wrap gap-1.5 mt-3">
                @foreach($property->amenities->take(4) as $amenity)
                    <span class="px-2 py-0.5 rounded-full bg-cream-200 text-ink-700 text-xs flex items-center gap-1">
                        <span>{{ $amenity->icon ?? '•' }}</span>
                        <span>{{ $amenity->name }}</span>
                    </span>
                @endforeach
                @if($property->amenities->count() > 4)
                    <span class="px-2 py-0.5 rounded-full bg-cream-200 text-ink-700 text-xs">+{{ $property->amenities->count() - 4 }}</span>
                @endif
            </div>
        @endif
  {{-- Price + View button --}}
        <div class="flex items-center justify-between gap-2 mt-4 pt-4 border-t border-ink-900/5">
            <div>
                @if($property->rent_min)
                    <span class="font-display font-black text-lg text-ink-950">₹{{ number_format($property->rent_min) }}</span>
                    <span class="text-xs text-ink-900/50">/mo</span>
                @endif
            </div>
            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-coral-500 text-white text-sm font-semibold group-hover:bg-coral-600 transition shadow-md shadow-coral-500/20">
                View Details
                <svg class="w-4 h-4 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </span>
        </div>
    </div>
</a>