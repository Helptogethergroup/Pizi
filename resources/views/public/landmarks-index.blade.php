@extends('layouts.app')
@section('title', 'PGs Near Popular Landmarks in Delhi NCR | PGFind')
@section('meta_description', 'Find PGs near top universities, colleges, IT parks, and metros in Delhi NCR. Sorted by distance.')

@section('content')

<section class="bg-cream grain border-b border-ink-900/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
        <span class="text-xs font-semibold text-coral-600 uppercase tracking-wider">PGs near landmarks</span>
        <h1 class="font-display font-black text-5xl md:text-6xl mt-3">Find PGs by what's around.</h1>
        <p class="text-ink-900/70 mt-3 max-w-2xl text-lg">
            Browse verified PGs sorted by their distance from the universities, IT parks, hospitals, and metros that matter to you.
        </p>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
    @php
        $typeNames = [
            'university' => '🎓 Universities & Colleges',
            'college' => '🎓 Colleges',
            'office' => '🏢 IT Parks & Offices',
            'hospital' => '🏥 Hospitals',
            'metro' => '🚇 Metro Stations',
            'mall' => '🛍️ Malls',
            'airport' => '✈️ Airports',
            'railway' => '🚆 Railway Stations',
        ];
    @endphp

    @foreach($landmarks as $type => $items)
        <div class="mb-12">
            <h2 class="font-display font-bold text-2xl mb-6">{{ $typeNames[$type] ?? ucfirst($type) }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($items as $landmark)
                    <a href="{{ route('landmark.show', $landmark->slug) }}" class="block p-5 bg-white rounded-2xl border border-ink-900/10 hover:border-coral-500 hover:shadow-lg transition">
                        <div class="flex items-start gap-3">
                            <div class="text-3xl">{{ $landmark->type_icon }}</div>
                            <div class="flex-1">
                                <div class="font-display font-bold text-lg leading-tight">{{ $landmark->name }}</div>
                                <div class="text-xs text-ink-900/60 mt-1">{{ $landmark->city?->name }}</div>
                                <div class="text-xs text-coral-600 font-semibold mt-2">View PGs near here →</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</section>

@endsection