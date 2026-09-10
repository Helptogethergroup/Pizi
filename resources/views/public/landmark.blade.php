@extends('layouts.app')

@section('title', $landmark->meta_title ?: 'PG near ' . $landmark->name . ' | PGFind')
@section('meta_description', $landmark->meta_description)

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Place",
  "name": @json($landmark->name),
  "description": @json(Str::limit($landmark->description ?? '', 250)),
  @if($landmark->latitude && $landmark->longitude)
  "geo": { "@type": "GeoCoordinates", "latitude": {{ $landmark->latitude }}, "longitude": {{ $landmark->longitude }} },
  @endif
  "address": { "@type": "PostalAddress", "addressLocality": @json($landmark->city?->name), "addressRegion": "Delhi NCR", "addressCountry": "IN" }
}
</script>
@endsection

@section('content')

{{-- Hero --}}
<section class="bg-cream grain border-b border-ink-900/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
        <nav class="text-sm text-ink-900/60 mb-3">
            <a href="{{ route('home') }}" class="hover:text-coral-600">Home</a> ›
            <a href="{{ route('city.show', $landmark->city->slug) }}" class="hover:text-coral-600">{{ $landmark->city?->name }}</a> ›
            <span class="text-ink-900">{{ $landmark->name }}</span>
        </nav>

        <div class="flex items-start gap-4">
            <div class="text-6xl">{{ $landmark->type_icon }}</div>
            <div class="flex-1">
                <span class="text-xs font-semibold text-coral-600 tracking-wider uppercase">{{ $landmark->type_label }}</span>
                <h1 class="font-display font-black text-4xl md:text-6xl mt-2 leading-tight">
                    PG near<br><em class="text-coral-500 not-italic">{{ $landmark->name }}</em>
                </h1>
                <p class="text-ink-900/70 mt-3 text-lg">
                    {{ $properties->total() }} verified PGs within 8 km · Sorted by distance
                </p>
                @if($landmark->description)
                    <p class="mt-4 max-w-3xl text-ink-900/70 leading-relaxed">{{ $landmark->description }}</p>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Map --}}
@if($landmark->latitude && $landmark->longitude)
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
    <iframe class="w-full h-80 rounded-2xl border-0" loading="lazy"
            src="https://maps.google.com/maps?q={{ $landmark->latitude }},{{ $landmark->longitude }}&z=14&output=embed"></iframe>
</section>
@endif

{{-- Properties --}}
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
    <h2 class="font-display font-bold text-3xl mb-8">Verified PGs near {{ $landmark->name }}</h2>

    @if($properties->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($properties as $property)
                <div class="relative">
                    <x-property-card :property="$property" />
                    @if($property->landmarks->first())
                        <span class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-full bg-ink-950/85 text-cream text-xs font-bold">
                            📍 {{ $property->landmarks->first()->pivot->distance_km }} km away
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-10">{{ $properties->links() }}</div>
    @else
        <div class="text-center py-16">
            <p class="text-ink-900/60">No PGs registered near this landmark yet. Check back soon!</p>
        </div>
    @endif
</section>

{{-- Related landmarks --}}
@if($relatedLandmarks->count())
<section class="bg-ink-950 text-cream py-16 mt-16">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <h2 class="font-display font-bold text-3xl mb-8">Other landmarks in {{ $landmark->city?->name }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($relatedLandmarks as $rl)
                <a href="{{ route('landmark.show', $rl->slug) }}" class="p-5 bg-cream/5 hover:bg-coral-500 rounded-xl transition border border-cream/10">
                    <div class="text-2xl">{{ $rl->type_icon }}</div>
                    <div class="font-bold mt-2 text-sm">{{ $rl->name }}</div>
                    <div class="text-xs text-cream/50 mt-1 capitalize">{{ $rl->type_label }}</div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection