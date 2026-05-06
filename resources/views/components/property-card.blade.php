@props(['property'])
<a href="{{ route('property.show', $property->slug) }}" class="group block bg-white rounded-2xl overflow-hidden border border-ink-900/10 hover:border-coral-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
    <div class="relative aspect-[4/3] overflow-hidden bg-ink-900/5">
        <img src="{{ $property->cover_url }}" alt="{{ $property->name }}"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
        @if($property->is_verified)
            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-emerald-500 text-white text-xs font-semibold flex items-center gap-1">
                ✓ Verified
            </span>
        @endif
        @if($property->is_featured)
            <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-coral-500 text-white text-xs font-semibold">⭐ Featured</span>
        @endif
        <span class="absolute bottom-3 left-3 px-2.5 py-1 rounded-full bg-ink-950/80 text-cream text-xs font-medium capitalize">
            {{ $property->gender === 'unisex' ? 'Unisex' : ($property->gender === 'male' ? 'Boys' : 'Girls') }} · {{ $property->property_type }}
        </span>
    </div>
    <div class="p-5">
        <h3 class="font-display font-bold text-lg leading-tight group-hover:text-coral-600 transition-colors line-clamp-1">{{ $property->name }}</h3>
        <p class="text-sm text-ink-900/60 mt-1 line-clamp-1">📍 {{ $property->locality?->name }}, {{ $property->city?->name }}</p>
        <div class="flex items-end justify-between mt-4">
            <div>
                <div class="font-display font-bold text-2xl text-ink-900">{{ $property->rent_range }}</div>
                <div class="text-xs text-ink-900/60">/month</div>
            </div>
            @if($property->food_included)
                <span class="text-xs px-2 py-1 rounded-full bg-coral-50 text-coral-700 font-medium">🍱 Food included</span>
            @endif
        </div>
    </div>
</a>
