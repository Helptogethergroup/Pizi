@extends('layouts.app')
@section('title', 'About PGFind — Our Story & Mission')
@section('meta_description', 'PGFind helps tenants find verified PGs in Delhi NCR. Learn about our mission, team, and how we are changing PG hunting.')
@section('content')

{{-- Hero --}}
<section class="bg-ink-950 text-cream py-20 lg:py-28">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1.5 rounded-full bg-coral-500/10 text-coral-300 text-sm font-semibold border border-coral-500/20 mb-6">
            About PGFind
        </span>
        <h1 class="font-display font-black text-4xl sm:text-5xl lg:text-7xl mb-6 text-balance">
            We're fixing PG hunting <span class="text-coral-400 italic">one verified listing at a time</span>
        </h1>
        <p class="text-lg lg:text-xl text-cream/70 max-w-3xl mx-auto leading-relaxed">
            PGFind started with a simple frustration — fake listings, hidden brokers, and unverified owners. We built a platform where every PG is real, every owner is verified, and every tenant gets a fair deal.
        </p>
    </div>
</section>

{{-- Mission --}}
<section class="py-16 lg:py-24 bg-cream">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-coral-500 font-bold text-sm uppercase tracking-wider">Our mission</span>
                <h2 class="font-display font-black text-3xl lg:text-5xl text-ink-950 mt-2 mb-6 text-balance">
                    Make finding a PG <span class="italic text-coral-500">simple, transparent, & honest</span>.
                </h2>
                <p class="text-ink-700 text-lg leading-relaxed mb-4">
                    Millions of students and young professionals move to Delhi NCR every year. Most of them end up paying brokers, dealing with fake listings, or visiting PGs that don't match the photos.
                </p>
                <p class="text-ink-700 text-lg leading-relaxed">
                    We're building a world where finding a PG is as easy as booking a hotel — verified properties, real photos, instant connection, and zero brokerage.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['🛡️', 'Verified', 'Every listing physically inspected'],
                    ['💸', 'No brokerage', 'Free for tenants forever'],
                    ['⚡', 'Fast match', '30-min response time'],
                    ['🤝', 'Trust', 'Owner KYC + tenant reviews'],
                ] as $value)
                    <div class="bg-white p-6 rounded-2xl border border-ink-100 hover-lift">
                        <div class="text-4xl mb-3">{{ $value[0] }}</div>
                        <div class="font-display font-bold text-lg text-ink-950">{{ $value[1] }}</div>
                        <div class="text-sm text-ink-600 mt-1">{{ $value[2] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Story / Numbers --}}
<section class="py-16 lg:py-20 bg-ink-950 text-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-display font-black text-3xl lg:text-5xl mb-3">PGFind in numbers</h2>
            <p class="text-cream/70 text-lg">Built with trust, scaled with care</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach([
                ['2,500+', 'Verified PGs'],
                ['10K+', 'Happy Tenants'],
                ['500+', 'Trusted Owners'],
                ['15+', 'Cities Served'],
            ] as $stat)
                <div class="text-center">
                    <div class="font-display font-black text-5xl lg:text-6xl text-coral-400">{{ $stat[0] }}</div>
                    <div class="text-sm text-cream/60 mt-2">{{ $stat[1] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- How it works (detailed) --}}
<section class="py-16 lg:py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-coral-500 font-bold text-sm uppercase tracking-wider">How we work</span>
            <h2 class="font-display font-black text-3xl lg:text-5xl text-ink-950 mt-2">The PGFind difference</h2>
        </div>

        <div class="space-y-12">
            @foreach([
                ['01', 'Owner onboarding', 'Every PG owner submits their property with details, photos, and ownership proof. Our team physically visits and verifies before going live.'],
                ['02', 'Tenant matching', 'When you submit a lead, our smart algorithm matches you with PGs based on location, budget, gender preference, and availability.'],
                ['03', 'Direct connection', 'Tele-callers verify your requirements, then connect you with owners. Our field team accompanies you on visits.'],
                ['04', 'Move-in support', 'From token payment to digital agreement — we help with every step until you have the keys in hand.'],
            ] as $process)
                <div class="grid grid-cols-1 md:grid-cols-[150px_1fr] gap-6 items-start">
                    <div class="font-display font-black text-6xl text-coral-500/20">{{ $process[0] }}</div>
                    <div>
                        <h3 class="font-display font-bold text-2xl text-ink-950 mb-2">{{ $process[1] }}</h3>
                        <p class="text-ink-600 text-lg leading-relaxed">{{ $process[2] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 lg:py-20 bg-gradient-to-br from-coral-500 to-coral-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-display font-black text-3xl lg:text-5xl text-white mb-4 text-balance">Ready to find your PG?</h2>
        <p class="text-white/90 text-lg mb-8">Browse 2,500+ verified PGs or list yours today.</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('search') }}" class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-coral-600 font-bold text-base shadow-2xl">Find a PG</a>
            <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 rounded-full border-2 border-white text-white font-bold text-base hover:bg-white/10 transition">List Your PG</a>
        </div>
    </div>
</section>

@endsection