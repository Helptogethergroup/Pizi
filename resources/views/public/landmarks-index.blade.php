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
            'university' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-4 10 4-10 4-10-4z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/><path d="M22 9v6"/></svg> Universities & Colleges',
            'college' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-4 10 4-10 4-10-4z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/><path d="M22 9v6"/></svg> Colleges',
            'office' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1M10 21v-3h4v3"/></svg> IT Parks & Offices',
            'hospital' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 8v8M8 12h8"/></svg> Hospitals',
            'metro' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="13" rx="3"/><circle cx="9" cy="14" r="1"/><circle cx="15" cy="14" r="1"/><path d="M7 20l2-3M17 20l-2-3M5 10h14"/></svg> Metro Stations',
            'mall' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l1 12H5L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>️ Malls',
            'airport' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="currentColor"><path d="M21 15l-6-2-3 5-2-1 1-5-7-2 1-2 8 1 3-6 2 1-2 6 5 2z"/></svg>️ Airports',
            'railway' => '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="13" rx="3"/><circle cx="9" cy="14" r="1"/><circle cx="15" cy="14" r="1"/><path d="M7 20l2-3M17 20l-2-3M5 10h14"/></svg> Railway Stations',
        ];
    @endphp

    @foreach($landmarks as $type => $items)
        <div class="mb-12">
            <h2 class="font-display font-bold text-2xl mb-6">{!! $typeNames[$type] ?? ucfirst($type) !!}</h2>
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