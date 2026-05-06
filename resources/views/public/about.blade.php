@extends('layouts.app')
@section('title', 'About PGFind — Verified PGs Across Delhi NCR')
@section('content')
<section class="max-w-4xl mx-auto px-4 lg:px-8 py-20">
    <span class="text-xs font-semibold text-coral-600 uppercase tracking-wider">About us</span>
    <h1 class="font-display font-black text-5xl md:text-6xl mt-3">We make PG hunting honest.</h1>
    <p class="mt-6 text-xl text-ink-900/70 leading-relaxed">
        Finding a good PG used to mean broker chains, fake photos, and last-minute surprises. We built PGFind to fix that — every listing is verified by our team, every visit is free, and we don't take a paisa from you.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16">
        <div class="p-8 bg-cream rounded-2xl border border-ink-900/10">
            <div class="text-4xl">🏡</div>
            <h3 class="font-display font-bold text-2xl mt-3">Verified listings</h3>
            <p class="text-ink-900/70 mt-2">Our team visits every PG before it goes live.</p>
        </div>
        <div class="p-8 bg-cream rounded-2xl border border-ink-900/10">
            <div class="text-4xl">🚗</div>
            <h3 class="font-display font-bold text-2xl mt-3">Free site visits</h3>
            <p class="text-ink-900/70 mt-2">Our executive will pick you up and show you around.</p>
        </div>
        <div class="p-8 bg-cream rounded-2xl border border-ink-900/10">
            <div class="text-4xl">💸</div>
            <h3 class="font-display font-bold text-2xl mt-3">Zero brokerage</h3>
            <p class="text-ink-900/70 mt-2">We charge owners, not tenants. You pay rent, that's it.</p>
        </div>
    </div>
</section>
@endsection
