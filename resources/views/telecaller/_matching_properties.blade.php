@if($matchingProperties->isEmpty())
    <div class="p-8 text-center text-ink-900/50">
        <div class="text-4xl mb-2">🔍</div>
        <p class="text-sm font-semibold">No matching properties found.</p>
        <p class="text-xs mt-1">Try changing budget or area on the left.</p>
    </div>
@else
    <div class="grid grid-cols-1 gap-3">
    @foreach($matchingProperties as $p)
        <div class="bg-white border border-ink-900/10 rounded-xl p-3 flex items-start gap-3 hover:border-coral-500 transition">
            @if($p->cover_image)
                <img src="{{ str_starts_with($p->cover_image, 'http') ? $p->cover_image : asset('storage/' . $p->cover_image) }}"
                     class="w-20 h-20 rounded-lg object-cover flex-shrink-0">
            @else
                <div class="w-20 h-20 rounded-lg bg-ink-900/10 flex items-center justify-center flex-shrink-0 text-2xl">🏠</div>
            @endif

            <div class="flex-1 min-w-0">
                <div class="font-display font-bold truncate">{{ $p->name }}</div>
                <div class="text-xs text-ink-900/60 truncate">📍 {{ $p->locality?->name }}, {{ $p->city?->name }}</div>
                <div class="text-sm font-bold mt-1">₹{{ number_format($p->rent_min) }}–{{ number_format($p->rent_max) }}/mo</div>
                <div class="text-xs text-ink-900/50 capitalize">{{ $p->gender }} · {{ str_replace('_', ' ', $p->property_type ?? 'pg') }}</div>
            </div>

            <div class="flex flex-col gap-1 flex-shrink-0">
                <a href="{{ route('property.show', $p->slug) }}" target="_blank"
                   class="text-xs px-2 py-1 border border-ink-900/15 rounded text-center hover:bg-cream">
                    👁 View
                </a>
            </div>
        </div>
    @endforeach
    </div>
@endif