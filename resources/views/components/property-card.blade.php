@php
    $cover = $property->cover_image
        ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
        : null;
@endphp

<a href="{{ route('property.show', $property->slug) }}" class="group block rounded-2xl border border-ink-900/10 bg-white overflow-hidden hover:border-coral-500 hover:shadow-xl hover:shadow-ink-900/5 transition">
    <div class="aspect-[4/3] bg-cream relative overflow-hidden">
        @if($cover)
            <img src="{{ $cover }}" alt="{{ $property->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
        @else
            <div class="w-full h-full flex items-center justify-center text-6xl bg-gradient-to-br from-coral-50 to-cream">🏠</div>
        @endif
        @if($property->is_verified)
            <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-white/95 backdrop-blur text-xs font-semibold text-emerald-700 flex items-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                Verified
            </span>
        @endif
        @if($property->is_featured)
            <span class="absolute top-3 right-3 px-3 py-1 rounded-full bg-coral-500 text-white text-xs font-semibold">⭐ Featured</span>
        @endif
    </div>

    <div class="p-5">
        <div class="flex items-start justify-between gap-2">
            <h3 class="font-display font-bold text-lg leading-tight group-hover:text-coral-600 transition">{{ $property->name }}</h3>
            <span class="text-xs px-2 py-1 rounded-md bg-ink-100 text-ink-700 capitalize whitespace-nowrap">{{ $property->gender }}</span>
        </div>

     <div class="text-sm text-ink-900/60 mt-1 truncate">
            📍 {{ $property->locality?->name }}{{ $property->city?->name ? ', ' . $property->city->name : '' }}
        </div>
        @if($property->is_verified && $property->verified_at)
            <div class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                ✓ Last verified: {{ \Carbon\Carbon::parse($property->verified_at)->format('d M Y') }}
            </div>
        @endif

        {{-- 🔥 AMENITIES with icons --}}
        @if($property->amenities && $property->amenities->count())
            <div class="flex flex-wrap gap-1.5 mt-3">
                @foreach($property->amenities->take(4) as $amenity)
                    <span class="px-2 py-0.5 rounded-full bg-cream-200 text-ink-700 text-xs flex items-center gap-1">
                        <span>{{ $amenity->icon ?? '✨' }}</span>
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