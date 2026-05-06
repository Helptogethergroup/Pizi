@extends('layouts.app')

@section('title', ($locality->meta_title ?: 'PG in ' . $locality->name . ', ' . $city->name . ' — PGFind'))
@section('meta_description', $locality->meta_description ?: $properties->total() . '+ verified PGs in ' . $locality->name . ', ' . $city->name . '. Find boys, girls & coliving PGs by budget. Free site visits.')

@section('content')
<section class="bg-cream grain border-b border-ink-900/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
        <nav class="text-sm text-ink-900/60 mb-3">
            <a href="{{ route('home') }}">Home</a> ›
            <a href="{{ route('city.show', $city->slug) }}">PGs in {{ $city->name }}</a> ›
            <span class="text-ink-900">{{ $locality->name }}</span>
        </nav>
        <span class="text-xs font-semibold text-coral-600 tracking-wider uppercase">{{ $properties->total() }} PGs available</span>
        <h1 class="font-display font-black text-5xl md:text-6xl mt-3">PGs in <em class="text-coral-500 not-italic">{{ $locality->name }}</em></h1>
        <p class="mt-2 text-ink-900/60">{{ $city->name }}</p>
        @if($locality->description)
            <p class="mt-4 max-w-3xl text-lg text-ink-900/70 leading-relaxed">{{ $locality->description }}</p>
        @endif
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 lg:px-8 py-12">
    @if($properties->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($properties as $property)
                <x-property-card :property="$property" />
            @endforeach
        </div>
        <div class="mt-10">{{ $properties->links() }}</div>
    @else
        <p class="text-center text-ink-900/60 py-20">No PGs listed in {{ $locality->name }} yet.</p>
    @endif
</section>
@endsection
