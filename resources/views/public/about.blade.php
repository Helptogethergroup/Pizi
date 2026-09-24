@extends('layouts.app')
@section('title', 'About Pizi — Verified PGs & Hostels in Delhi NCR | Zero Brokerage')
@section('meta_description', 'Pizi is Delhi NCR\'s trusted PG aggregator. Field-verified properties, real photos, owner-direct contact, zero brokerage. Discover how we\'re changing how India finds PGs.')

@push('head')
<meta name="keywords" content="about pizi, verified pg delhi ncr, zero brokerage pg, owner direct pg, pg aggregator india, trusted pg platform noida">
<link rel="canonical" href="https://pizi.in/about">

<meta property="og:title" content="About Pizi — Verified PGs in Delhi NCR">
<meta property="og:description" content="Field-verified PGs, real photos, owner-direct, zero brokerage. Discover the Pizi story.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://pizi.in/about">

{{-- Organization Schema --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Pizi",
  "url": "https://pizi.in",
  "logo": "https://pizi.in/assets/images/logo.png",
  "description": "Verified PG and hostel aggregator across Delhi NCR with zero brokerage and owner-direct contact.",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Delhi NCR",
    "addressRegion": "Delhi",
    "addressCountry": "IN"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+918006680092",
    "contactType": "customer service",
    "areaServed": "IN",
    "availableLanguage": ["English", "Hindi"]
  },
  "sameAs": [
    "https://www.instagram.com/pizi.in",
    "https://www.facebook.com/piziindia"
  ]
}
</script>

<style>
/* Scroll animations */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-12px); }
}
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.reveal {
    opacity: 0;
}
.reveal.active {
    animation: fadeUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.reveal-scale {
    opacity: 0;
}
.reveal-scale.active {
    animation: scaleIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.float-icon {
    animation: float 4s ease-in-out infinite;
}
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }

.gradient-text {
    background: linear-gradient(120deg, #ff6b5b, #ed4e3d);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
@endpush

@section('content')

{{-- ===== HERO ===== --}}
<section class="grain relative overflow-hidden py-16 lg:py-24">
    {{-- Floating background blobs --}}
    <div class="absolute top-20 right-10 w-72 h-72 bg-coral-500/10 rounded-full blur-3xl float-icon"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-coral-400/5 rounded-full blur-3xl"></div>

    <div class="relative max-w-5xl mx-auto px-4 lg:px-8">
        <span class="reveal text-coral-600 font-semibold text-sm tracking-wider uppercase">About Pizi</span>
        <h1 class="reveal delay-1 font-display font-black text-4xl sm:text-5xl lg:text-7xl mt-4 leading-[1.05]">
            We're fixing how India <span class="gradient-text italic">finds PGs.</span>
        </h1>
        <p class="reveal delay-2 text-lg lg:text-xl text-ink-900/70 mt-6 max-w-2xl leading-relaxed">
            For too long, finding a PG meant fake photos, hidden broker fees, and endless WhatsApp groups. Pizi exists to fix that — one verified property at a time.
        </p>
        <div class="reveal delay-3 mt-8 flex flex-wrap gap-4">
            <a href="{{ route('search') }}" class="px-6 py-3 rounded-full bg-coral-500 text-white font-semibold hover:bg-coral-600 transition shadow-lg shadow-coral-500/30">
                Browse Verified PGs →
            </a>
            <a href="{{ route('register') }}" class="px-6 py-3 rounded-full border-2 border-ink-900/15 font-semibold hover:border-coral-500 transition">
                List your PG
            </a>
        </div>
    </div>
</section>

{{-- ===== STATS BAR ===== --}}
<section class="py-12 bg-ink-950 text-cream">
    <div class="max-w-5xl mx-auto px-4 lg:px-8 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        <div class="reveal-scale">
            <div class="font-display font-black text-4xl lg:text-5xl text-coral-400">100%</div>
            <p class="text-cream/60 text-sm mt-2">Field Verified</p>
        </div>
        <div class="reveal-scale delay-1">
            <div class="font-display font-black text-4xl lg:text-5xl text-coral-400">₹0</div>
            <p class="text-cream/60 text-sm mt-2">Brokerage to Tenants</p>
        </div>
        <div class="reveal-scale delay-2">
            <div class="font-display font-black text-4xl lg:text-5xl text-coral-400">5+</div>
            <p class="text-cream/60 text-sm mt-2">Cities in NCR</p>
        </div>
        <div class="reveal-scale delay-3">
            <div class="font-display font-black text-4xl lg:text-5xl text-coral-400">30min</div>
            <p class="text-cream/60 text-sm mt-2">Response Time</p>
        </div>
    </div>
</section>

{{-- ===== OUR STORY ===== --}}
<section class="py-16 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 lg:px-8">
        <div class="reveal">
            <span class="text-coral-600 font-semibold text-sm tracking-wider uppercase">Our Story</span>
            <h2 class="font-display font-black text-3xl lg:text-4xl mt-3">Built by people who struggled to find a PG.</h2>
        </div>
        <div class="reveal delay-1 mt-6 space-y-4 text-ink-900/70 leading-relaxed text-lg">
            <p>
                Like thousands of students and working professionals moving to Delhi NCR, our founders knew the pain first-hand — paying brokers for a single phone number, visiting PGs that looked nothing like their photos, and chasing owners who never picked up.
            </p>
            <p>
                We believed there had to be a better way. So we built Pizi: a platform where every property is physically visited, every photo is real, and every owner is reachable directly. No brokers. No fake listings. No surprises.
            </p>
            <p class="font-semibold text-ink-950">
                Today, Pizi helps thousands of tenants find homes they can trust — and helps PG owners reach genuine tenants without paying hefty commissions.
            </p>
        </div>
    </div>
</section>

{{-- ===== WHY PIZI (Values Grid) ===== --}}
<section class="py-16 bg-gradient-to-b from-cream to-white">
    <div class="max-w-5xl mx-auto px-4 lg:px-8">
        <div class="text-center mb-12">
            <span class="reveal text-coral-600 font-semibold text-sm tracking-wider uppercase">What makes us different</span>
            <h2 class="reveal delay-1 font-display font-black text-3xl lg:text-4xl mt-3">The Pizi promise.</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="reveal group p-8 rounded-2xl bg-white border border-ink-900/10 hover:border-coral-300 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="text-5xl mb-4 inline-block group-hover:scale-110 transition"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5.5c0 4.6-3 8.1-7 9.5-4-1.4-7-4.9-7-9.5V6l7-3z"/></svg>️</div>
                <h3 class="font-display font-bold text-2xl">Verified properties</h3>
                <p class="text-ink-900/70 mt-3 leading-relaxed">Every PG on Pizi is physically visited by our field team. We check the photos, the amenities, and the owner — before it ever goes live.</p>
            </div>

            <div class="reveal delay-1 group p-8 rounded-2xl bg-white border border-ink-900/10 hover:border-coral-300 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="text-5xl mb-4 inline-block group-hover:scale-110 transition"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12l3-3 3 3 2.5-2.5a2 2 0 0 1 2.8 0l1 1a2 2 0 0 1 0 2.8L14 19.6a2.8 2.8 0 0 1-4 0l-6.3-6.3"/><path d="M6.5 15.5l-2-2a2 2 0 0 1 0-2.8l2-2"/></svg></div>
                <h3 class="font-display font-bold text-2xl">Owner-direct</h3>
                <p class="text-ink-900/70 mt-3 leading-relaxed">No brokers in the middle. You talk to the owner. You visit. You decide. Zero brokerage to tenants — ever.</p>
            </div>

            <div class="reveal delay-2 group p-8 rounded-2xl bg-white border border-ink-900/10 hover:border-coral-300 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="text-5xl mb-4 inline-block group-hover:scale-110 transition"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="14" r="3.5"/></svg></div>
                <h3 class="font-display font-bold text-2xl">Real photos only</h3>
                <p class="text-ink-900/70 mt-3 leading-relaxed">What you see is what you get. Our team uploads honest photos of every room, common area, and bathroom — no filters, no fakes.</p>
            </div>

            <div class="reveal delay-3 group p-8 rounded-2xl bg-white border border-ink-900/10 hover:border-coral-300 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="text-5xl mb-4 inline-block group-hover:scale-110 transition"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="13" cy="4" r="1.8"/><path d="M10 8l3 1 2.5 4.5-1.5 6M13 9l-3 2-2 5.5M10 11l-4 2"/></svg></div>
                <h3 class="font-display font-bold text-2xl">Free site visits</h3>
                <p class="text-ink-900/70 mt-3 leading-relaxed">Schedule a visit and our field executive shows you around and helps you negotiate. Completely free — no strings attached.</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== VERIFICATION PROCESS ===== --}}
<section class="py-16 lg:py-20 bg-ink-950 text-cream relative overflow-hidden">
    <div class="absolute -top-20 left-1/4 w-96 h-96 bg-coral-500/10 rounded-full blur-3xl"></div>
    <div class="relative max-w-5xl mx-auto px-4 lg:px-8">
        <div class="text-center mb-14">
            <span class="reveal text-coral-400 font-semibold text-sm tracking-wider uppercase">Behind every <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" stroke-width="2.5"/></svg> Verified badge</span>
            <h2 class="reveal delay-1 font-display font-black text-3xl lg:text-4xl mt-3">How we verify every PG.</h2>
            <p class="reveal delay-2 text-cream/60 mt-4 max-w-2xl mx-auto text-lg">No property goes live on Pizi until it clears every one of these checks.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="reveal p-6 rounded-2xl bg-white/5 border border-cream/10">
                <div class="text-4xl mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg></div>
                <div class="text-xs font-bold text-coral-400 uppercase tracking-wider mb-1">Step 1</div>
                <h3 class="font-display font-bold text-lg">Site visit</h3>
                <p class="text-cream/60 text-sm mt-2 leading-relaxed">Our field team physically visits the property — no listing goes up from a phone call alone.</p>
            </div>
            <div class="reveal delay-1 p-6 rounded-2xl bg-white/5 border border-cream/10">
                <div class="text-4xl mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="14" r="3.5"/></svg></div>
                <div class="text-xs font-bold text-coral-400 uppercase tracking-wider mb-1">Step 2</div>
                <h3 class="font-display font-bold text-lg">Real photos</h3>
                <p class="text-cream/60 text-sm mt-2 leading-relaxed">Every room, bathroom &amp; common area is shot on-site the same day — no stock images, no filters.</p>
            </div>
            <div class="reveal delay-2 p-6 rounded-2xl bg-white/5 border border-cream/10">
                <div class="text-4xl mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke-width="2.2"/></svg></div>
                <div class="text-xs font-bold text-coral-400 uppercase tracking-wider mb-1">Step 3</div>
                <h3 class="font-display font-bold text-lg">Amenity check</h3>
                <p class="text-cream/60 text-sm mt-2 leading-relaxed">WiFi, food, security, power backup — every claimed amenity is checked in person before it's listed.</p>
            </div>
            <div class="reveal delay-3 p-6 rounded-2xl bg-white/5 border border-cream/10">
                <div class="text-4xl mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12l3-3 3 3 2.5-2.5a2 2 0 0 1 2.8 0l1 1a2 2 0 0 1 0 2.8L14 19.6a2.8 2.8 0 0 1-4 0l-6.3-6.3"/><path d="M6.5 15.5l-2-2a2 2 0 0 1 0-2.8l2-2"/></svg></div>
                <div class="text-xs font-bold text-coral-400 uppercase tracking-wider mb-1">Step 4</div>
                <h3 class="font-display font-bold text-lg">Owner verification</h3>
                <p class="text-cream/60 text-sm mt-2 leading-relaxed">We confirm the owner's identity and ownership before publishing — so you always know who you're renting from.</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== HOW IT WORKS (Timeline) ===== --}}
<section class="py-16 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 lg:px-8">
        <div class="text-center mb-12">
            <span class="reveal text-coral-600 font-semibold text-sm tracking-wider uppercase">How it works</span>
            <h2 class="reveal delay-1 font-display font-black text-3xl lg:text-4xl mt-3">Find your PG in 4 simple steps.</h2>
        </div>

        <div class="space-y-6">
            <div class="reveal flex gap-5 items-start">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-coral-500 text-white flex items-center justify-center font-display font-black text-lg">1</div>
                <div>
                    <h3 class="font-display font-bold text-xl">Search & filter</h3>
                    <p class="text-ink-900/70 mt-1">Browse PGs by city, locality, budget, gender, and amenities. Find exactly what you need.</p>
                </div>
            </div>

            <div class="reveal delay-1 flex gap-5 items-start">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-coral-500 text-white flex items-center justify-center font-display font-black text-lg">2</div>
                <div>
                    <h3 class="font-display font-bold text-xl">View verified details</h3>
                    <p class="text-ink-900/70 mt-1">See real photos, exact location on map, rent, deposit, and complete amenity list.</p>
                </div>
            </div>

            <div class="reveal delay-2 flex gap-5 items-start">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-coral-500 text-white flex items-center justify-center font-display font-black text-lg">3</div>
                <div>
                    <h3 class="font-display font-bold text-xl">Schedule a free visit</h3>
                    <p class="text-ink-900/70 mt-1">Book a site visit. Our team helps you tour the property and connect with the owner.</p>
                </div>
            </div>

            <div class="reveal delay-3 flex gap-5 items-start">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-coral-500 text-white flex items-center justify-center font-display font-black text-lg">4</div>
                <div>
                    <h3 class="font-display font-bold text-xl">Move in with confidence</h3>
                    <p class="text-ink-900/70 mt-1">No brokerage, no surprises. Finalize directly with the owner and move into your new home.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== FOR OWNERS ===== --}}
<section class="py-16 bg-gradient-to-br from-coral-50 to-cream">
    <div class="max-w-5xl mx-auto px-4 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div class="reveal">
            <span class="text-coral-600 font-semibold text-sm tracking-wider uppercase">For PG Owners</span>
            <h2 class="font-display font-black text-3xl lg:text-4xl mt-3">Reach genuine tenants. Pay zero commission.</h2>
            <p class="text-ink-900/70 mt-4 leading-relaxed text-lg">
                List your property on Pizi and start receiving verified tenant leads. No recurring subscriptions, no hidden charges — just buy credits and unlock leads when you need them.
            </p>
            <a href="{{ route('register') }}" class="inline-block mt-6 px-6 py-3 rounded-full bg-coral-500 text-white font-semibold hover:bg-coral-600 transition shadow-lg shadow-coral-500/30">
                List your PG free →
            </a>
        </div>
        <div class="reveal delay-1 grid grid-cols-2 gap-4">
            <div class="p-6 rounded-2xl bg-white border border-ink-900/10">
                <div class="text-3xl mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/></svg></div>
                <p class="font-bold text-ink-950">Verified Leads</p>
                <p class="text-sm text-ink-900/60 mt-1">Only serious tenants</p>
            </div>
            <div class="p-6 rounded-2xl bg-white border border-ink-900/10">
                <div class="text-3xl mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="17" cy="14.2" r="1.1" fill="currentColor" stroke="none"/></svg></div>
                <p class="font-bold text-ink-950">No Commission</p>
                <p class="text-sm text-ink-900/60 mt-1">Keep all earnings</p>
            </div>
            <div class="p-6 rounded-2xl bg-white border border-ink-900/10">
                <div class="text-3xl mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg></div>
                <p class="font-bold text-ink-950">Direct Contact</p>
                <p class="text-sm text-ink-900/60 mt-1">Talk to tenants</p>
            </div>
            <div class="p-6 rounded-2xl bg-white border border-ink-900/10">
                <div class="text-3xl mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"/></svg></div>
                <p class="font-bold text-ink-950">Instant Listing</p>
                <p class="text-sm text-ink-900/60 mt-1">Go live in minutes</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== FINAL CTA ===== --}}
<section class="py-16 lg:py-24 bg-ink-950 text-cream relative overflow-hidden">
    <div class="absolute top-0 right-1/4 w-96 h-96 bg-coral-500/10 rounded-full blur-3xl"></div>
    <div class="relative max-w-3xl mx-auto px-4 lg:px-8 text-center">
        <h2 class="reveal font-display font-black text-3xl lg:text-5xl">Our promise to you.</h2>
        <p class="reveal delay-1 text-cream/70 mt-6 text-lg max-w-2xl mx-auto leading-relaxed">
            Every property on Pizi is verified. Every owner is real. Every photo is honest. We're not just another listing site — we're your trusted partner in finding a home you'll love.
        </p>
        <div class="reveal delay-2 mt-8 flex flex-wrap gap-4 justify-center">
            <a href="{{ route('search') }}" class="px-8 py-4 rounded-full bg-coral-500 text-white font-semibold hover:bg-coral-600 transition shadow-lg shadow-coral-500/30">
                Browse Verified PGs →
            </a>
            <a href="tel:8006680092" class="px-8 py-4 rounded-full border-2 border-cream/20 font-semibold hover:border-coral-400 hover:text-coral-400 transition">
                <svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C11.4 21 3 12.6 3 2.3 3 1.7 3.4 1.3 4 1.3h3.4c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.2 1.1l-2.2 2.2z"/></svg> Talk to us
            </a>
        </div>
    </div>
</section>

{{-- Scroll reveal animation script --}}
<script>
(function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.reveal, .reveal-scale').forEach(el => {
        observer.observe(el);
    });
})();
</script>

@endsection