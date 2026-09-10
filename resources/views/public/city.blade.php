@extends('layouts.app')

@section('title', ($city->meta_title ?: 'PG in ' . $city->name . ' | Verified Hostels & Coliving — PGFind'))
@section('meta_description', $city->meta_description ?: 'Browse ' . $properties->total() . '+ verified PGs in ' . $city->name . '. Filter by budget, locality, gender. Free site visits, zero brokerage.')

@section('content')
<section class="bg-cream grain border-b border-ink-900/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
        <span class="text-xs font-semibold text-coral-600 tracking-wider uppercase">{{ $properties->total() }} PGs available</span>
        <h1 class="font-display font-black text-5xl md:text-6xl mt-3">PGs in <em class="text-coral-500 not-italic">{{ $city->name }}</em></h1>
        @if($city->description)
            <p class="mt-4 max-w-3xl text-lg text-ink-900/70 leading-relaxed">{{ $city->description }}</p>
        @endif
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 lg:px-8 py-12">
    @if($localities->count())
        <div class="mb-12">
            <h2 class="font-display font-bold text-2xl mb-4">Popular localities in {{ $city->name }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($localities as $loc)
                    <a href="{{ route('locality.show', [$city->slug, $loc->slug]) }}"
                       class="px-4 py-2 bg-white border border-ink-900/10 rounded-full text-sm font-medium hover:border-coral-500 hover:text-coral-600">
                        {{ $loc->name }} <span class="text-ink-900/40">({{ $loc->properties_count }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($properties->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($properties as $property)
                <x-property-card :property="$property" />
            @endforeach
        </div>
        <div class="mt-10">{{ $properties->links() }}</div>
    @else
        <p class="text-center text-ink-900/60 py-20">No PGs listed in {{ $city->name }} yet. Check back soon!</p>
    @endif
</section>
@endsection
