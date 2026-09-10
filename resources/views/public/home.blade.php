@extends('layouts.app')
@section('title', 'Pizi — Find Verified PGs in Delhi NCR & Noida')
@section('meta_description', 'Browse verified PGs, hostels & coliving across Delhi, Noida, Gurgaon, Ghaziabad. Real photos, owner-direct, no brokerage.')

@push('head')
<style>
    @keyframes piziFadeUp { from { opacity: 0; transform: translateY(34px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes piziFloat  { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-16px); } }
    @keyframes piziFloat2 { 0%,100% { transform: translateY(0) rotateZ(0deg); } 50% { transform: translateY(-22px) rotateZ(-2deg); } }
    @keyframes piziFloatBadge { 0%,100% { transform: perspective(600px) translateY(0) rotateX(0deg) rotateY(0deg); } 50% { transform: perspective(600px) translateY(-10px) rotateX(4deg) rotateY(-3deg); } }
    @keyframes piziBlob   { 0% { transform: translate(0,0) scale(1); } 33% { transform: translate(40px,-30px) scale(1.15); } 66% { transform: translate(-30px,25px) scale(0.9); } 100% { transform: translate(0,0) scale(1); } }
    @keyframes piziBlob2  { 0% { transform: translate(0,0) scale(1); } 50% { transform: translate(-50px,40px) scale(1.2); } 100% { transform: translate(0,0) scale(1); } }
    @keyframes piziPulse  { 0%,100% { opacity: .5; } 50% { opacity: 1; } }

    /* Guaranteed mobile layout for the hero image — plain CSS so it takes
       effect the instant the page parses, without waiting on the Tailwind
       CDN's runtime JS (on slow connections that JS can lag, letting content
       flash/overflow before Tailwind's rules are injected). Image itself
       stays visible on mobile (smaller height); only the two floating
       corner badges — sized for the big desktop image — are hidden. */
    #pziHeroImageWrap, #pziHeroImageWrap * { max-width: 100%; }
    @media (max-width: 1023.98px) {
        .pz-float2, .pz-float-slow { display: none !important; }
    }

    .pz-anim { opacity: 0; animation: piziFadeUp .8s cubic-bezier(.16,1,.3,1) forwards; }
    .pz-d1 { animation-delay: .05s; }
    .pz-d2 { animation-delay: .18s; }
    .pz-d3 { animation-delay: .32s; }
    .pz-d4 { animation-delay: .46s; }
    .pz-d5 { animation-delay: .60s; }
    .pz-d6 { animation-delay: .74s; }

    .pz-float  { animation: piziFloat 5s ease-in-out infinite; }
    .pz-float2 { animation: piziFloatBadge 6.5s ease-in-out infinite; transform-style: preserve-3d; }
    .pz-float-slow { animation: piziFloatBadge 8s ease-in-out infinite reverse; transform-style: preserve-3d; }

    /* 3D tilt-on-hover cards */
    .pz-tilt-card { transition: transform .15s ease-out, box-shadow .15s ease-out; transform-style: preserve-3d; will-change: transform; }

    /* Hero image mouse-parallax tilt */
    #pziHeroImageWrap .pz-float { transition: transform .2s ease-out; transform-style: preserve-3d; }

    .pz-blob  { animation: piziBlob 18s ease-in-out infinite; }
    .pz-blob2 { animation: piziBlob2 22s ease-in-out infinite; }

    .pz-reveal { opacity: 0; transform: translateY(48px); transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
    .pz-reveal.pz-in { opacity: 1; transform: translateY(0); }

    /* Pulse dot inside badges */
    @keyframes pziPulseDot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.6); opacity: 0.5; }
    }
    .pzi-pulse { animation: pziPulseDot 1.4s ease-in-out infinite; }

    /* Bold colored section header boxes */
    @keyframes pziBoxIn {
        from { opacity: 0; transform: translateY(-10px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .pzi-headbox {
        animation: pziBoxIn 0.5s cubic-bezier(.16,1,.3,1) both;
        box-shadow: 0 8px 24px -8px rgba(0,0,0,0.25);
        position: relative;
        overflow: hidden;
    }
    .pzi-headbox::after {
        content: '';
        position: absolute;
        top: 0; left: -60%;
        width: 40%; height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255,255,255,0.25), transparent);
        transform: skewX(-20deg);
        animation: pziSheen 4s ease-in-out infinite;
    }
    @keyframes pziSheen {
        0%   { left: -60%; }
        50%  { left: 130%; }
        100% { left: 130%; }
    }
    .pzi-headbox-coral   { background: linear-gradient(120deg, #ff6b5b, #e6483a); }
    .pzi-headbox-emerald { background: linear-gradient(120deg, #10b981, #047857); }
    .pzi-headbox-amber   { background: linear-gradient(120deg, #f59e0b, #b45309); }
    .pzi-headbox-blue    { background: linear-gradient(120deg, #3b82f6, #1d4ed8); }
    .pzi-headbox-violet  { background: linear-gradient(120deg, #8b5cf6, #6d28d9); }
    .pzi-headbox-rose    { background: linear-gradient(120deg, #f43f5e, #be123c); }

    .pzi-icon-circle {
        flex-shrink: 0;
        width: 52px; height: 52px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15), inset 0 1px 0 rgba(255,255,255,0.2);
    }

    .pzi-seeall {
        flex-shrink: 0;
        color: white;
        font-weight: 700;
        font-size: 13px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(255,255,255,0.18);
        backdrop-filter: blur(4px);
        transition: background 0.2s ease, transform 0.2s ease;
        white-space: nowrap;
    }
    .pzi-seeall:hover {
        background: rgba(255,255,255,0.3);
        transform: translateX(2px);
    }

    @media (max-width: 640px) {
        .pzi-headbox h2 { font-size: 1.25rem; }
        .pzi-icon-circle { width: 42px; height: 42px; font-size: 18px; border-radius: 12px; }
        .pzi-seeall { font-size: 11px; padding: 6px 12px; }
    }
</style>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "What makes Pizi the best platform to find PGs in India?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Pizi is one of the best platforms to find PGs in India because it offers verified listings, easy search filters, transparent pricing, and a seamless booking experience. Whether you're a student or a working professional, Pizi helps you discover safe, comfortable, and affordable PG accommodations across multiple cities, making the entire process faster and hassle-free."
    }
  },{
    "@type": "Question",
    "name": "What information is available in Pizi PG listings?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Pizi PG listings include essential details such as property photos, room types, monthly rent, available amenities, location, occupancy options, and contact information. This helps users make informed decisions and find the right PG accommodation based on their preferences and budget."
    }
  },{
    "@type": "Question",
    "name": "Can I compare PGs on Pizi before making a decision?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Yes, Pizi allows you to compare different PGs based on factors such as rent, room types, amenities, location, and occupancy options. This makes it easier to evaluate your choices and select the PG that best matches your needs and budget."
    }
  },{
    "@type": "Question",
    "name": "How can I search for PGs based on my budget on Pizi?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Pizi makes it easy to find PGs within your budget. You can use filters to narrow down listings based on your preferred price range, location, room type, and amenities, helping you quickly discover PG accommodations that suit your needs and budget."
    }
  }]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org/", 
  "@type": "Product", 
  "name": "Pizi",
  "image": "",
  "description": "Pizi is an online platform that helps users find and compare verified PG accommodations across India. It offers detailed listings with photos, room types, rent, amenities, location information, and easy search filters to simplify the accommodation search process.",
  "offers": {
    "@type": "Offer",
    "url": "",
    "priceCurrency": "INR",
    "price": ""
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "ratingCount": "500"
  }
}
</script>
@endpush

@section('content')

{{-- HERO --}}
<section class="relative grain overflow-hidden">
    {{-- Background image --}}
    <div class="pointer-events-none absolute inset-0 -z-20 bg-cover bg-center"
         style="background-image: url('https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1600&q=80');"></div>
    {{-- Light overlay so dark text stays readable --}}
    <div class="pointer-events-none absolute inset-0 -z-10 bg-cream/85 backdrop-blur-[1px]"></div>

    {{-- Animated blobs --}}
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div class="pz-blob absolute -top-24 -left-20 w-[28rem] h-[28rem] bg-coral-500/15 rounded-full blur-3xl"></div>
        <div class="pz-blob2 absolute top-1/3 -right-24 w-[32rem] h-[32rem] bg-coral-400/10 rounded-full blur-3xl"></div>
        <div class="pz-blob absolute bottom-0 left-1/3 w-80 h-80 bg-ink-900/5 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 lg:px-8 pt-12 pb-20 lg:pt-20 lg:pb-32">
        <div class="grid lg:grid-cols-2 gap-12 items-stretch">
            <div>
                <span class="pz-anim pz-d1 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-coral-50 text-coral-700 text-xs font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-coral-500" style="animation: piziPulse 1.6s ease-in-out infinite;"></span>
                    Verified by Pizi field team
                </span>
                <h1 class="pz-anim pz-d2 font-display text-5xl sm:text-6xl lg:text-7xl leading-[1.05] font-black text-ink-950">
                    Find your PG that <span class="italic text-coral-600">feels like home.</span>
                </h1>
                <p class="pz-anim pz-d3 text-lg text-ink-900/70 mt-6 max-w-xl leading-relaxed">
                    Verified listings, real photos, honest rents. Across Delhi, Noida, Gurgaon &amp; Ghaziabad — book a free site visit in 60 seconds.
                </p>
<form action="{{ route('search') }}" method="GET" class="mt-8 bg-white shadow-xl shadow-ink-900/5 rounded-2xl p-3 flex flex-wrap gap-2 w-full max-w-5xl">
    
    {{-- Location Input (MAIN) --}}
    <input type="text" name="q" placeholder="Locality, college, metro station..." 
           class="flex-1 min-w-[150px] px-4 py-2.5 text-sm border border-ink-900/10 rounded-xl focus:outline-none focus:border-coral-500 placeholder:text-ink-900/40">
    
    {{-- Gender --}}
    <select name="gender" class="px-4 py-2.5 text-sm border border-ink-900/10 rounded-xl bg-cream">
        <option value="">Any gender</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        <option value="unisex">Unisex</option>
    </select>
    
   
{{-- Budget slider --}}
    <div class="flex-1 min-w-[160px] px-3 py-2.5 border border-ink-900/10 rounded-xl bg-cream flex items-center gap-2">
        <span class="text-xs font-semibold text-ink-900/50 shrink-0">Budget</span>
        <input type="range" id="budgetSlider" min="3000" max="30000" step="1000" value="30000"
               class="flex-1 min-w-0 accent-coral-500"
               oninput="piziUpdateBudget(this.value)">
        <span id="budgetValue" class="text-xs text-coral-600 font-bold shrink-0 w-12 text-right">Any</span>
        <input type="hidden" name="budget_max" id="budgetMaxHidden" value="">
    </div>
    
    {{-- University (NEW) --}}
    <select name="nearby_university_id" class="px-4 py-2.5 text-sm border border-ink-900/10 rounded-xl bg-cream">
        <option value="">University</option>
        @php
            $universities = \DB::table('universities')->orderBy('name')->get();
        @endphp
        @foreach($universities as $uni)
            <option value="{{ $uni->id }}">{{ $uni->abbreviation }}</option>
        @endforeach
    </select>
    
    {{-- Search Button --}}
    <button type="submit" class="px-6 py-2.5 bg-coral-500 hover:bg-coral-600 text-white font-bold text-sm rounded-xl transition shadow-coral-500/30 whitespace-nowrap">
        Search →
    </button>
</form>

                <!--<div class="pz-anim pz-d5 mt-4 flex flex-wrap items-center gap-2 max-w-xl">-->
                <!--    <span class="text-xs text-ink-900/50 font-semibold mr-1">Popular:</span>-->
                <!--    <a href="{{ route('search', ['gender' => 'male']) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 text-xs font-semibold text-ink-900 hover:border-coral-500 hover:text-coral-600 transition">👨 Boys PG</a>-->
                <!--    <a href="{{ route('search', ['gender' => 'female']) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 text-xs font-semibold text-ink-900 hover:border-coral-500 hover:text-coral-600 transition">👩 Girls PG</a>-->
                <!--    <a href="{{ route('search', ['budget_max' => '8000']) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 text-xs font-semibold text-ink-900 hover:border-coral-500 hover:text-coral-600 transition">💰 Under ₹8k</a>-->
                <!--    <a href="{{ route('search', ['gender' => 'unisex']) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 text-xs font-semibold text-ink-900 hover:border-coral-500 hover:text-coral-600 transition">👥 Unisex</a>-->
                <!--</div>-->

                <!--<div class="pz-anim pz-d5 mt-6 flex flex-wrap items-center gap-4">-->
                <!--    <a href="tel:8006680092" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-ink-950 text-cream font-semibold hover:bg-ink-900 transition shadow-lg shadow-ink-900/20">-->
                <!--        <span class="text-lg">📞</span> Call 8006680092-->
                <!--    </a>-->
                <!--    <a href="https://wa.me/918006680092" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700">💬 Or chat on WhatsApp</a>-->
                <!--</div>-->

                <div class="pz-anim pz-d6 mt-12 grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 max-w-xl">
                    <div class="bg-white/60 rounded-2xl border border-ink-900/5 p-4 text-center sm:text-left" data-counter="{{ $stats['properties'] ?? 500 }}">
                        <div class="font-display font-black text-2xl sm:text-3xl text-ink-950"><span class="pz-count">0</span>+</div>
                        <div class="text-xs text-ink-900/60 mt-1">Listed PGs</div>
                    </div>
                    <div class="bg-white/60 rounded-2xl border border-ink-900/5 p-4 text-center sm:text-left" data-counter="{{ $stats['cities'] ?? 4 }}">
                        <div class="font-display font-black text-2xl sm:text-3xl text-ink-950"><span class="pz-count">0</span></div>
                        <div class="text-xs text-ink-900/60 mt-1">Cities</div>
                    </div>
                    <div class="bg-white/60 rounded-2xl border border-ink-900/5 p-4 text-center sm:text-left" data-counter="{{ (int) str_replace(',', '', $stats['tenants'] ?? 12000) }}">
                        <div class="font-display font-black text-2xl sm:text-3xl text-ink-950"><span class="pz-count">0</span>+</div>
                        <div class="text-xs text-ink-900/60 mt-1">Happy Residents</div>
                    </div>
                    <div class="bg-white/60 rounded-2xl border border-ink-900/5 p-4 text-center sm:text-left" data-counter="{{ $stats['owners'] ?? 60 }}">
                        <div class="font-display font-black text-2xl sm:text-3xl text-ink-950"><span class="pz-count">0</span>+</div>
                        <div class="text-xs text-ink-900/60 mt-1">Verified Owners</div>
                    </div>
                </div>
            </div>

            <div id="pziHeroImageWrap" class="relative flex items-stretch self-stretch pz-anim pz-d3 mt-6 lg:mt-0">
                <div class="pz-float relative rounded-3xl overflow-hidden shadow-2xl shadow-ink-900/20 w-full flex">
                    <img src="{{ asset('assets/images/pizi-img.jpeg') }}"
                     alt="Modern PG room"
                     class="w-full h-[240px] sm:h-[300px] lg:h-full lg:min-h-[460px] object-cover">
                    <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-ink-950 via-ink-950/40 to-transparent"></div>
                    <div class="absolute top-5 left-5 px-3 py-1.5 rounded-full bg-white/95 backdrop-blur text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Verified Property
                    </div>
                </div>
                <div class="pz-float2 hidden lg:flex absolute -bottom-4 -left-4 bg-white rounded-2xl shadow-2xl shadow-ink-900/15 px-4 py-3 items-center gap-3 max-w-[240px] z-20">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">✓</div>
                    <div><div class="font-semibold text-sm">Free site visit</div><div class="text-xs text-ink-900/60">Our team accompanies you</div></div>
                </div>
                <div class="pz-float-slow hidden lg:flex absolute top-3 right-3 bg-white rounded-2xl shadow-2xl shadow-ink-900/15 px-4 py-3 items-center gap-3 max-w-[220px] z-20">
                    <div class="w-10 h-10 rounded-full bg-coral-100 flex items-center justify-center text-coral-600 flex-shrink-0">⚡</div>
                    <div><div class="font-semibold text-sm">30 min response</div><div class="text-xs text-ink-900/60">Telecaller calls fast</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- TRUST BADGES --}}
<section class="border-y border-ink-900/8 bg-white/60">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-4">
        <div class="flex flex-wrap items-center justify-center sm:justify-between gap-x-8 gap-y-3 text-sm">
            <div class="flex items-center gap-2 text-ink-900/70 font-semibold">
                <span class="text-emerald-600">✓</span> No Brokerage
            </div>
            <div class="flex items-center gap-2 text-ink-900/70 font-semibold">
                <span class="text-emerald-600">✓</span> Verified Owners
            </div>
            <div class="flex items-center gap-2 text-ink-900/70 font-semibold">
                <span class="text-emerald-600">✓</span> Free Site Visit
            </div>
            <div class="flex items-center gap-2 text-ink-900/70 font-semibold">
                <span class="text-emerald-600">✓</span> 24x7 Support
            </div>
            <div class="flex items-center gap-2 text-ink-900/70 font-semibold">
                <span class="text-emerald-600">✓</span> Real Photos Only       
            </div>
        </div>
    </div>
</section>


{{--
    ========================================================================
    QUICK LINKS — har group ek alag RECTANGLE BOX me
    home.blade.php me purane quicklinks section ko isse REPLACE karo.
    ========================================================================
--}}
<section class="relative py-6 overflow-hidden bg-gray-50 text-slate-900 pz-reveal">

    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="pz-blob absolute -top-20 left-1/4 w-96 h-96 bg-coral-500/20 rounded-full blur-3xl"></div>
        <div class="pz-blob2 absolute bottom-0 -right-20 w-[28rem] h-[28rem] bg-coral-400/15 rounded-full blur-3xl"></div>
    </div>
    <!--<div class="pointer-events-none absolute inset-0 opacity-[0.02]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;"></div>-->

    <div class="relative max-w-7xl mx-auto px-4 lg:px-8">

        <div class="text-center max-w-2xl mx-auto mb-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur border border-white/15 text-slate-900 text-xs font-bold tracking-wider uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-coral-400" style="animation: piziPulse 1.6s ease-in-out infinite;"></span> Find faster
            </span>
            <h2 class="font-display font-black text-3xl lg:text-5xl mt-4 text-slate-900">
                Browse by what <span class="text-coral-400">matters.</span>
            </h2>
            <p class="text-slate-900 mt-3 text-lg">Pick your budget, type or city — jump straight to matching PGs.</p>
        </div>

        <div class="space-y-4">

            {{-- ===== BOX 1: BY BUDGET ===== --}}
            <div class="bg-white border border-gray-200 shadow-lg rounded-3xl p-6 lg:p-8 shadow-xl shadow-black/20">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-5 flex items-center gap-2">💰 By Budget</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 lg:gap-4">
                   @foreach([
    ['budget-8000rs-img.jpeg', 'Under ₹8,000', '8000', 'Popular choice'],
    ['budget-10000rs-img.jpeg', 'Under ₹10,000', '10000', 'Comfort stays'],
    ['budget-15000rs-img.jpeg', 'Under ₹15,000', '15000', 'Premium PGs'],
    ] as [$image, $label, $val, $tag])
                        <a href="{{ route('search', ['budget_max' => $val]) }}"
                           class="group relative overflow-hidden rounded-2xl bg-white border border-gray-200 p-5 shadow-sm hover:border-coral-400 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            <div class="mb-3">
                            <img src="{{ asset('assets/images/' . $image) }}"
                             alt="{{ $label }}"
                           class="w-40 h-40 object-contain mx-auto group-hover:scale-110 transition duration-300">
                          </div>
                            <div class="font-display font-bold text-base mt-4 text-slate-900">{{ $label }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $tag }}</div>
                            <div class="text-xs text-coral-400 font-semibold mt-2 inline-flex items-center gap-1">View PGs <span class="group-hover:translate-x-0.5 transition">→</span></div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ===== BOX 2: BY TYPE ===== --}}
            <div class="bg-white/[0.06] backdrop-blur border border-white/10 rounded-3xl p-6 lg:p-8 shadow-xl shadow-black/20">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-5 flex items-center gap-2">🏠 By Type</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:gap-4">
                  @foreach([
    ['boys-img.jpeg', 'Boys PG', 'PGs for men', ['gender' => 'male']],
    ['girls-img.jpeg', 'Girls PG', 'PGs for women', ['gender' => 'female']],
    ['unisex-img.jpeg', 'Unisex PG', 'Co-living spaces', ['gender' => 'unisex']],
    ] as [$image, $title, $sub, $params])
                        <a href="{{ route('search', $params) }}"
                           class="group relative overflow-hidden rounded-2xl bg-white border border-gray-200 p-6 flex items-center gap-4  hover:border-coral-400/60 hover:bg-white/10 hover:-translate-y-1 transition duration-300">
                      <div class="flex-shrink-0">
                     <img src="{{ asset('assets/images/' . $image) }}"
                      alt="{{ $title }}"
                      class="w-32 h-32 object-contain group-hover:scale-110 transition duration-300">
</div>
                            <div>
                                <div class="font-display font-bold text-lg text-slate-900 group-hover:text-coral-400 transition">{{ $title }}</div>
                                <div class="text-sm text-slate-500">{{ $sub }}</div>
                            </div>
                            <span class="ml-auto text-coral-400 group-hover:translate-x-1 transition">→</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ===== BOX 3: BY CITY ===== --}}
            <div class="bg-white/[0.06] backdrop-blur border border-white/10 rounded-3xl p-6 lg:p-8 shadow-xl shadow-black/20">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-5 flex items-center gap-2">📍 By City</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 lg:gap-4">
                  @foreach([
    ['Delhi', 'delhi', 'delhi-img.jpeg'],
    ['Noida', 'noida', 'noida-img.jpeg'],
    ['Gurgaon', 'gurgaon', 'gurgaon-img.jpeg'],
    ['Ghaziabad', 'ghaziabad', 'ghaziabad-img.jpeg'],
    ['Faridabad', 'faridabad', 'faridabad-img.jpeg'],
] as [$cityName, $slug, $image])
                   <a href="{{ route('city.show', $slug) }}"
   class="group bg-white border border-gray-200 rounded-2xl p-6 text-center hover:border-coral-400 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

   <div class="flex justify-center mb-4">
    <img src="{{ asset('assets/images/' . $image) }}"
         alt="{{ $cityName }}"
         class="w-24 h-24 object-contain group-hover:scale-110 transition duration-300">
</div>

    <h4 class="font-display font-bold text-xl text-slate-900">
        {{ $cityName }}
    </h4>

    <p class="text-coral-500 font-semibold mt-3">
        Explore →
    </p>

</a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>



{{-- FEATURED PROPERTIES --}}
@if(isset($featured) && $featured->count())
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="pzi-headbox pzi-headbox-coral flex items-center justify-between mb-5 px-5 py-4 rounded-2xl">
            <div class="flex items-center gap-4">
                <span class="pzi-icon-circle bg-coral-500">🏆</span>
                <div>
                    <span class="inline-flex items-center gap-1.5 text-coral-100 font-bold text-xs tracking-widest uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-white pzi-pulse"></span>
                        Hand-picked
                    </span>
                    <h2 class="font-display font-black text-2xl lg:text-3xl text-white leading-tight">Featured stays</h2>
                </div>
            </div>
            <a href="{{ route('search') }}" class="pzi-seeall">See all →</a>
        </div>
       <div class="pzi-slider flex gap-4 overflow-x-auto pb-3 scrollbar-hide">
            @foreach($featured as $property)
            @php
                $cover = $property->cover_image
                    ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
                    : null;
            @endphp
            <a href="{{ route('property.show', $property->slug) }}"
               class="pz-tilt-card group flex-shrink-0 w-56 snap-start bg-white rounded-xl border border-ink-900/10 overflow-hidden hover:border-coral-500 hover:shadow-lg transition">
                <div class="h-36 bg-cream relative overflow-hidden">
                    @if($cover)
                        <img src="{{ $cover }}" alt="{{ $property->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl bg-gradient-to-br from-coral-50 to-cream">🏠</div>
                    @endif
                    @if($property->is_verified)
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-white/95 text-xs font-semibold text-emerald-700 flex items-center gap-1">✓ Verified</span>
                    @endif
                    @if($property->is_featured)
                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-coral-500 text-white text-xs font-semibold">⭐</span>
                    @endif
                </div>
                <div class="p-3">
                    <div class="flex items-start justify-between gap-1">
                        <h3 class="font-bold text-sm leading-tight line-clamp-1 group-hover:text-coral-600 transition">{{ $property->name }}</h3>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-ink-100 text-ink-700 capitalize whitespace-nowrap">{{ $property->gender }}</span>
                    </div>
                    <div class="text-xs text-ink-900/60 mt-1 truncate">📍 {{ $property->locality?->name }}{{ $property->city?->name ? ', '.$property->city->name : '' }}</div>
                    @if($property->rent_min)
                        <div class="mt-2 flex items-center justify-between">
                            <span class="font-black text-base text-ink-950">₹{{ number_format($property->rent_min) }}<span class="text-xs font-normal text-ink-900/50">/mo</span></span>
                            <span class="text-xs px-2 py-1 rounded-lg bg-coral-500 text-white font-semibold">View →</span>
                        </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
<hr class="border-ink-900/8 max-w-7xl mx-auto">
@endif








{{-- ALL AMENITIES PROPERTIES --}}
@if(isset($allAmenityProperties) && $allAmenityProperties->count())
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="pzi-headbox pzi-headbox-emerald flex items-center justify-between mb-5 px-5 py-4 rounded-2xl">
            <div class="flex items-center gap-4">
                <span class="pzi-icon-circle bg-emerald-500">✨</span>
                <div>
                    <span class="inline-flex items-center gap-1.5 text-emerald-100 font-bold text-xs tracking-widest uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-white pzi-pulse"></span>
                        Fully loaded
                    </span>
                    <h2 class="font-display font-black text-2xl lg:text-3xl text-white leading-tight">Every amenity included.</h2>
                </div>
            </div>
            <a href="{{ route('search') }}" class="pzi-seeall">See all →</a>
        </div>
        <div class="pzi-slider flex gap-4 overflow-x-auto pb-3 scrollbar-hide">
            @foreach($allAmenityProperties as $property)
            @php
                $cover = $property->cover_image
                    ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
                    : null;
            @endphp
            <a href="{{ route('property.show', $property->slug) }}"
               class="pz-tilt-card group flex-shrink-0 w-56 snap-start bg-white rounded-xl border border-ink-900/10 overflow-hidden hover:border-coral-500 hover:shadow-lg transition">
                <div class="h-36 bg-cream relative overflow-hidden">
                    @if($cover)
                        <img src="{{ $cover }}" alt="{{ $property->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl bg-gradient-to-br from-coral-50 to-cream">🏠</div>
                    @endif
                    @if($property->is_verified)
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-white/95 text-xs font-semibold text-emerald-700">✓ Verified</span>
                    @endif
                </div>
                <div class="p-3">
                    <div class="flex items-start justify-between gap-1">
                        <h3 class="font-bold text-sm leading-tight line-clamp-1 group-hover:text-coral-600 transition">{{ $property->name }}</h3>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-ink-100 text-ink-700 capitalize whitespace-nowrap">{{ $property->gender }}</span>
                    </div>
                    <div class="text-xs text-ink-900/60 mt-1 truncate">📍 {{ $property->locality?->name }}{{ $property->city?->name ? ', '.$property->city->name : '' }}</div>
                    @if($property->rent_min)
                        <div class="mt-2 flex items-center justify-between">
                            <span class="font-black text-base text-ink-950">₹{{ number_format($property->rent_min) }}<span class="text-xs font-normal text-ink-900/50">/mo</span></span>
                            <span class="text-xs px-2 py-1 rounded-lg bg-coral-500 text-white font-semibold">View →</span>
                        </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
<hr class="border-ink-900/8 max-w-7xl mx-auto">
@endif

{{-- LOW BUDGET PROPERTIES --}}
@if(isset($lowBudgetProperties) && $lowBudgetProperties->count())
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="pzi-headbox pzi-headbox-amber flex items-center justify-between mb-5 px-5 py-4 rounded-2xl">
            <div class="flex items-center gap-4">
                <span class="pzi-icon-circle bg-amber-500">💰</span>
                <div>
                    <span class="inline-flex items-center gap-1.5 text-amber-100 font-bold text-xs tracking-widest uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-white pzi-pulse"></span>
                        Pocket friendly
                    </span>
                    <h2 class="font-display font-black text-2xl lg:text-3xl text-white leading-tight">Great stays, low budget.</h2>
                </div>
            </div>
            <a href="{{ route('search', ['budget_max' => 8000]) }}" class="pzi-seeall">See all →</a>
        </div>
        <div class="pzi-slider flex gap-4 overflow-x-auto pb-3 scrollbar-hide">
            @foreach($lowBudgetProperties as $property)
            @php
                $cover = $property->cover_image
                    ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
                    : null;
            @endphp
            <a href="{{ route('property.show', $property->slug) }}"
               class="pz-tilt-card group flex-shrink-0 w-56 snap-start bg-white rounded-xl border border-ink-900/10 overflow-hidden hover:border-coral-500 hover:shadow-lg transition">
                <div class="h-36 bg-cream relative overflow-hidden">
                    @if($cover)
                        <img src="{{ $cover }}" alt="{{ $property->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl bg-gradient-to-br from-coral-50 to-cream">🏠</div>
                    @endif
                    @if($property->is_verified)
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-white/95 text-xs font-semibold text-emerald-700">✓ Verified</span>
                    @endif
                </div>
                <div class="p-3">
                    <div class="flex items-start justify-between gap-1">
                        <h3 class="font-bold text-sm leading-tight line-clamp-1 group-hover:text-coral-600 transition">{{ $property->name }}</h3>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-ink-100 text-ink-700 capitalize whitespace-nowrap">{{ $property->gender }}</span>
                    </div>
                    <div class="text-xs text-ink-900/60 mt-1 truncate">📍 {{ $property->locality?->name }}{{ $property->city?->name ? ', '.$property->city->name : '' }}</div>
                    @if($property->rent_min)
                        <div class="mt-2 flex items-center justify-between">
                            <span class="font-black text-base text-ink-950">₹{{ number_format($property->rent_min) }}<span class="text-xs font-normal text-ink-900/50">/mo</span></span>
                            <span class="text-xs px-2 py-1 rounded-lg bg-coral-500 text-white font-semibold">View →</span>
                        </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
<hr class="border-ink-900/8 max-w-7xl mx-auto">
@endif



{{-- CITY GRID (disabled) --}}
{{--
@if(isset($cities) && $cities->count())
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="pzi-headbox pzi-headbox-violet flex items-center justify-between mb-5 px-5 py-4 rounded-2xl">
            <div class="flex items-center gap-4">
                <span class="pzi-icon-circle bg-violet-500">📍</span>
                <div>
                    <span class="inline-flex items-center gap-1.5 text-violet-100 font-bold text-xs tracking-widest uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-white pzi-pulse"></span>
                        Locations
                    </span>
                    <h2 class="font-display font-black text-2xl lg:text-3xl text-white leading-tight">PGs across NCR</h2>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($cities as $city)
                <a href="{{ route('city.show', $city->slug) }}"
                   class="group relative p-5 rounded-2xl bg-white border border-ink-900/10 hover:border-violet-500 hover:shadow-lg transition overflow-hidden">
                    <div class="text-3xl mb-2 group-hover:scale-110 transition-transform duration-300">🏙️</div>
                    <h3 class="font-display font-bold text-lg text-ink-950">{{ $city->name }}</h3>
                    <p class="text-xs text-ink-900/60 mt-1">{{ $city->properties_count ?? 0 }} PGs available</p>
                    <span class="absolute top-4 right-4 text-violet-500 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition">→</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
<hr class="border-ink-900/8 max-w-7xl mx-auto">
@endif
--}}


{{-- VERIFIED PROPERTIES --}}
@if(isset($verifiedProperties) && $verifiedProperties->count())
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="pzi-headbox pzi-headbox-blue flex items-center justify-between mb-5 px-5 py-4 rounded-2xl">
            <div class="flex items-center gap-4">
                <span class="pzi-icon-circle bg-blue-500">🛡️</span>
                <div>
                    <span class="inline-flex items-center gap-1.5 text-blue-100 font-bold text-xs tracking-widest uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-white pzi-pulse"></span>
                        Trust guaranteed
                    </span>
                    <h2 class="font-display font-black text-2xl lg:text-3xl text-white leading-tight">100% verified PGs.</h2>
                </div>
            </div>
            <a href="{{ route('search') }}" class="pzi-seeall">See all →</a>
        </div>
        <div class="pzi-slider flex gap-4 overflow-x-auto pb-3 scrollbar-hide">
            @foreach($verifiedProperties as $property)
            @php
                $cover = $property->cover_image
                    ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
                    : null;
            @endphp
            <a href="{{ route('property.show', $property->slug) }}"
               class="pz-tilt-card group flex-shrink-0 w-56 snap-start bg-white rounded-xl border border-ink-900/10 overflow-hidden hover:border-coral-500 hover:shadow-lg transition">
                <div class="h-36 bg-cream relative overflow-hidden">
                    @if($cover)
                        <img src="{{ $cover }}" alt="{{ $property->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl bg-gradient-to-br from-coral-50 to-cream">🏠</div>
                    @endif
                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-white/95 text-xs font-semibold text-emerald-700">✓ Verified</span>
                </div>
                <div class="p-3">
                    <div class="flex items-start justify-between gap-1">
                        <h3 class="font-bold text-sm leading-tight line-clamp-1 group-hover:text-coral-600 transition">{{ $property->name }}</h3>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-ink-100 text-ink-700 capitalize whitespace-nowrap">{{ $property->gender }}</span>
                    </div>
                    <div class="text-xs text-ink-900/60 mt-1 truncate">📍 {{ $property->locality?->name }}{{ $property->city?->name ? ', '.$property->city->name : '' }}</div>
                    @if($property->rent_min)
                        <div class="mt-2 flex items-center justify-between">
                            <span class="font-black text-base text-ink-950">₹{{ number_format($property->rent_min) }}<span class="text-xs font-normal text-ink-900/50">/mo</span></span>
                            <span class="text-xs px-2 py-1 rounded-lg bg-coral-500 text-white font-semibold">View →</span>
                        </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
<hr class="border-ink-900/8 max-w-7xl mx-auto">
@endif

{{-- RECENTLY ADDED PGs --}}
@if(isset($recentProperties) && $recentProperties->count())
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="pzi-headbox pzi-headbox-rose flex items-center justify-between mb-5 px-5 py-4 rounded-2xl">
            <div class="flex items-center gap-4">
                <span class="pzi-icon-circle bg-rose-500">🆕</span>
                <div>
                    <span class="inline-flex items-center gap-1.5 text-rose-100 font-bold text-xs tracking-widest uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-white pzi-pulse"></span>
                        Just listed
                    </span>
                    <h2 class="font-display font-black text-2xl lg:text-3xl text-white leading-tight">Freshly added PGs.</h2>
                </div>
            </div>
            <a href="{{ route('search') }}" class="pzi-seeall">See all →</a>
        </div>
        <div class="pzi-slider flex gap-4 overflow-x-auto pb-3 scrollbar-hide">
            @foreach($recentProperties as $property)
            @php
                $cover = $property->cover_image
                    ? (str_starts_with($property->cover_image, 'http') ? $property->cover_image : asset('storage/' . $property->cover_image))
                    : null;
            @endphp
            <a href="{{ route('property.show', $property->slug) }}"
               class="pz-tilt-card group flex-shrink-0 w-56 snap-start bg-white rounded-xl border border-ink-900/10 overflow-hidden hover:border-rose-500 hover:shadow-lg transition">
                <div class="h-36 bg-cream relative overflow-hidden">
                    @if($cover)
                        <img src="{{ $cover }}" alt="{{ $property->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl bg-gradient-to-br from-rose-50 to-cream">🏠</div>
                    @endif
                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-rose-500 text-white text-xs font-semibold">New</span>
                </div>
                <div class="p-3">
                    <div class="flex items-start justify-between gap-1">
                        <h3 class="font-bold text-sm leading-tight line-clamp-1 group-hover:text-rose-600 transition">{{ $property->name }}</h3>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-ink-100 text-ink-700 capitalize whitespace-nowrap">{{ $property->gender }}</span>
                    </div>
                    <div class="text-xs text-ink-900/60 mt-1 truncate">📍 {{ $property->locality?->name }}{{ $property->city?->name ? ', '.$property->city->name : '' }}</div>
                    @if($property->rent_min)
                        <div class="mt-2 flex items-center justify-between">
                            <span class="font-black text-base text-ink-950">₹{{ number_format($property->rent_min) }}<span class="text-xs font-normal text-ink-900/50">/mo</span></span>
                            <span class="text-xs px-2 py-1 rounded-lg bg-rose-500 text-white font-semibold">View →</span>
                        </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
<hr class="border-ink-900/8 max-w-7xl mx-auto">
@endif

{{-- TESTIMONIALS --}}
@if(isset($testimonials) && $testimonials->count())
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="pzi-headbox pzi-headbox-amber flex items-center justify-between mb-5 px-5 py-4 rounded-2xl">
            <div class="flex items-center gap-4">
                <span class="pzi-icon-circle bg-amber-500">💬</span>
                <div>
                    <span class="inline-flex items-center gap-1.5 text-amber-100 font-bold text-xs tracking-widest uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-white pzi-pulse"></span>
                        Real tenants
                    </span>
                    <h2 class="font-display font-black text-2xl lg:text-3xl text-white leading-tight">What residents say.</h2>
                </div>
            </div>
        </div>
        <div class="pzi-slider flex gap-4 overflow-x-auto pb-3 scrollbar-hide">
            @foreach($testimonials as $t)
            <div class="flex-shrink-0 w-72 snap-start bg-white rounded-xl border border-ink-900/10 p-4">
                <div class="flex items-center gap-0.5 text-amber-500 text-sm mb-2">
                    @for($i = 0; $i < 5; $i++)
                        <span>{{ $i < $t->rating ? '★' : '☆' }}</span>
                    @endfor
                </div>
                <p class="text-sm text-ink-900/80 leading-relaxed line-clamp-4">&ldquo;{{ $t->comment }}&rdquo;</p>
                <div class="mt-3 pt-3 border-t border-ink-900/8 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                        {{ strtoupper(substr($t->reviewer_name ?? 'P', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-ink-950 truncate">{{ $t->reviewer_name ?? 'Pizi tenant' }}</div>
                        <div class="text-[11px] text-ink-900/50 truncate">{{ $t->property?->name }}{{ $t->property?->city?->name ? ', '.$t->property->city->name : '' }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
<hr class="border-ink-900/8 max-w-7xl mx-auto">
@endif





<section class="relative py-6 overflow-hidden pz-reveal">

    {{-- soft gradient background --}}
    <div class="absolute inset-0 -z-20 bg-gradient-to-b from-white via-coral-50/40 to-cream"></div>

    {{-- floating glow blobs --}}
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div class="pz-blob absolute -top-16 left-1/4 w-80 h-80 bg-coral-500/10 rounded-full blur-3xl"></div>
        <div class="pz-blob2 absolute bottom-0 right-10 w-96 h-96 bg-coral-400/10 rounded-full blur-3xl"></div>
        <div class="pz-blob absolute top-1/2 -left-20 w-72 h-72 bg-ink-900/5 rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 lg:px-8">

        {{-- Heading --}}
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-coral-100 text-coral-700 text-xs font-bold tracking-wider uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-coral-500"></span> Why choose Pizi
            </span>
            <h2 class="font-display font-black text-3xl lg:text-5xl mt-4 text-ink-950">
                Everything you need, <span class="text-coral-600">sorted.</span>
            </h2>
            <p class="text-ink-900/60 mt-3 text-lg">Real photos, honest rents, and a team that actually shows up.</p>
        </div>

        {{-- Feature grid --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">
         @foreach([
    ['bed-sharing-img.jpeg', 'Bed Sharing', 'From single occupancy to 4-sharing rooms — pick the option that fits your budget and comfort.'],
    ['food-img.jpeg', 'Food Included', 'Hygienic kitchens, branded groceries and home-style meals. Most PGs come with food included.'],
    ['wifi-img.jpeg', 'High-Speed WiFi', 'Free high-speed WiFi across the entire premises — stay connected for work or entertainment.'],
    ['power-backup-img.jpeg', 'Power Backup', '24x7 power backup. Even during outages, your fans, lights and WiFi keep running — no worries.'],
    ['no-maintenance-img.jpeg', 'Zero Maintenance', 'Cleaning, repairs and upkeep are all handled by us. Just relax — we take care of the rest.'],
    ['verified-img.jpeg', 'Verified & Safe', 'Every PG is physically inspected by our field team. CCTV, security and safe localities guaranteed.'],
    ] as [$image, $title, $desc])
                <div class="group relative bg-white/80 backdrop-blur rounded-3xl border border-white shadow-lg shadow-ink-900/5 p-7 hover:shadow-2xl hover:shadow-coral-500/10 hover:-translate-y-1.5 transition duration-300 overflow-hidden">
                    {{-- corner glow on hover --}}
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-coral-500/0 group-hover:bg-coral-500/10 rounded-full blur-2xl transition duration-500"></div>

              <div class="relative w-30 h-30 flex items-center justify-center">
    <img src="{{ asset('assets/images/' . $image) }}"
         alt="{{ $title }}"
         class="w-40 h-40 object-contain group-hover:scale-110 transition duration-300">
</div>
                    <h3 class="relative font-display font-bold text-xl mt-5 text-ink-950 group-hover:text-coral-600 transition">{{ $title }}</h3>
                    <p class="relative text-sm text-ink-900/60 mt-2 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>

    </div>
</section>












{{-- HOW IT WORKS --}}
<section class="py-5 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-coral-600 font-semibold text-sm tracking-wider uppercase">How it works</span>
            <h2 class="font-display font-black text-3xl lg:text-5xl mt-2">Three steps to your new home.</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
           @foreach([
    ['1', 'Search', 'Browse verified PGs in your preferred locality and budget.', 'search-img.jpeg'],
    ['2', 'Visit', 'Schedule a free site visit. Our field team accompanies you.', 'visit-img.jpeg'],
    ['3', 'Move in', 'Pay token, sign agreement, and move into your new home.', 'book-img.jpeg'],
    ] as [$num, $title, $desc, $image])
              <div class="group p-8 rounded-2xl border border-ink-900/10 hover:border-coral-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                   <div class="mb-6 flex justify-center">
    <img src="{{ asset('assets/images/' . $image) }}"
         alt="{{ $title }}"
         class="w-28 h-28 object-contain group-hover:scale-110 transition duration-300">
                 </div>
                    <div class="text-xs text-coral-600 font-semibold mb-2">STEP {{ $num }}</div>
                    <h3 class="font-display font-bold text-2xl">{{ $title }}</h3>
                    <p class="text-ink-900/60 mt-3">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>









{{-- OWNER CTA --}}
<section class="pb-6 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-900 to-ink-950 text-cream p-12 lg:p-16">
            <div class="pz-blob absolute top-0 right-0 w-96 h-96 bg-coral-500/10 rounded-full blur-3xl"></div>
            <div class="relative max-w-2xl">
                <span class="text-coral-400 font-semibold text-sm tracking-wider uppercase">For PG Owners</span>
                <h2 class="font-display font-black text-3xl lg:text-5xl mt-3">List your PG. Get verified tenants.</h2>
                <p class="text-cream/70 mt-4 text-lg">No brokerage. No upfront fees. You only pay credits when you unlock a real, qualified lead.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="px-6 py-3 rounded-full bg-coral-500 text-white font-semibold hover:bg-coral-600 transition">Get started →</a>
                    <a href="{{ route('about') }}" class="px-6 py-3 rounded-full bg-cream/10 text-cream hover:bg-cream/20 transition">How it works</a>
                </div>
            </div>
        </div>
    </div>
</section>









{{-- BLOG PREVIEW --}}
@if(isset($recentBlogs) && $recentBlogs->count())
<section class="pb-6 pz-reveal">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="flex items-end justify-between mb-4">
            <div><span class="text-coral-600 font-semibold text-sm tracking-wider uppercase">Guides &amp; tips</span><h2 class="font-display font-black text-3xl lg:text-5xl mt-2">From the blog</h2></div>
            <a href="{{ route('blog.index') }}" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold hover:text-coral-600">Read all →</a>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($recentBlogs as $blog)
                <a href="{{ route('blog.show', $blog->slug) }}" class="group block rounded-2xl border border-ink-900/10 overflow-hidden hover:border-coral-500 transition">
                    @if($blog->cover_image)
                        <img src="{{ str_starts_with($blog->cover_image, 'http') ? $blog->cover_image : asset('storage/' . $blog->cover_image) }}" class="aspect-[16/10] w-full object-cover" alt="">
                    @else
                        <div class="aspect-[16/10] bg-gradient-to-br from-coral-100 to-coral-50 flex items-center justify-center text-4xl">📝</div>
                    @endif
                    <div class="p-6">
                        <div class="text-xs text-coral-600 font-semibold uppercase tracking-wider">{{ $blog->published_at?->format('d M Y') }}</div>
                        <h3 class="font-display font-bold text-xl mt-2 group-hover:text-coral-600 transition">{{ $blog->title }}</h3>
                        <p class="text-sm text-ink-900/60 mt-2 line-clamp-2">{{ $blog->excerpt }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>



@endif



<style>
@keyframes pziAutoScroll {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.pzi-auto-track {
    display: flex;
    gap: 1rem;
    width: max-content;
    animation: pziAutoScroll 40s linear infinite;
}
.pzi-auto-wrap:hover .pzi-auto-track {
    animation-play-state: paused;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pzi-slider').forEach(function (slider) {
        // Sirf tab auto-scroll jab cards zyada ho (overflow ho)
        if (slider.scrollWidth <= slider.clientWidth) return;

        let speed = 0.5;
        let paused = false;

        slider.addEventListener('mouseenter', () => paused = true);
        slider.addEventListener('mouseleave', () => paused = false);
        slider.addEventListener('touchstart', () => paused = true, {passive: true});
        slider.addEventListener('touchend', () => setTimeout(() => paused = false, 2000));

        function scrollStep() {
            if (!paused) {
                slider.scrollLeft += speed;
                // Loop back to start
                if (slider.scrollLeft >= (slider.scrollWidth - slider.clientWidth - 1)) {
                    slider.scrollLeft = 0;
                }
            }
            requestAnimationFrame(scrollStep);
        }
        requestAnimationFrame(scrollStep);
    });
});
</script>

@endsection

@push('scripts')
<script>
(function () {
    var els = document.querySelectorAll('.pz-reveal');
    if (!('IntersectionObserver' in window) || !els.length) {
        els.forEach(function (el) { el.classList.add('pz-in'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('pz-in'); io.unobserve(e.target); }
        });
    }, { threshold: 0.12 });
    els.forEach(function (el) { io.observe(el); });
})();

function piziUpdateBudget(val) {
    val = parseInt(val);
    const label = document.getElementById('budgetValue');
    const hidden = document.getElementById('budgetMaxHidden');
    if (val >= 30000) {
        label.textContent = 'Any';
        hidden.value = '';
    } else {
        label.textContent = '₹' + val.toLocaleString('en-IN');
        hidden.value = val;
    }
}

// 3D tilt-on-hover for listing cards (skip on touch devices — no hover there anyway)
(function () {
    if (window.matchMedia('(hover: none)').matches) return;
    var cards = document.querySelectorAll('.pz-tilt-card');
    cards.forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
            var rect = card.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = 'perspective(600px) rotateX(' + (-y * 10) + 'deg) rotateY(' + (x * 10) + 'deg) scale3d(1.03,1.03,1.03)';
        });
        card.addEventListener('mouseleave', function () {
            card.style.transform = 'perspective(600px) rotateX(0deg) rotateY(0deg) scale3d(1,1,1)';
        });
    });
})();

// Hero image — subtle mouse-parallax 3D tilt
(function () {
    var container = document.querySelector('#pziHeroImageWrap .pz-float');
    if (!container || window.matchMedia('(hover: none)').matches) return;
    container.addEventListener('mousemove', function (e) {
        var rect = container.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width - 0.5;
        var y = (e.clientY - rect.top) / rect.height - 0.5;
        container.style.transform = 'rotateX(' + (-y * 6) + 'deg) rotateY(' + (x * 6) + 'deg)';
    });
    container.addEventListener('mouseleave', function () {
        container.style.transform = 'rotateX(0deg) rotateY(0deg)';
    });
})();

// Stat counter count-up animation
(function () {
    var counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    function animate(el) {
        var target = parseInt(el.getAttribute('data-counter'), 10) || 0;
        var span = el.querySelector('.pz-count');
        if (!span) return;
        var start = 0;
        var duration = 1200;
        var startTime = null;
        function step(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            span.textContent = Math.floor(start + (target - start) * eased).toLocaleString('en-IN');
            if (progress < 1) requestAnimationFrame(step);
            else span.textContent = target.toLocaleString('en-IN');
        }
        requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) {
        counters.forEach(animate);
        return;
    }
    var counterIo = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { animate(e.target); counterIo.unobserve(e.target); }
        });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { counterIo.observe(el); });
})();

// Live activity ticker — rotating social-proof toast, bottom-left
// Skipped on small screens — it was overlapping the hero search button.
(function () {
    if (window.innerWidth < 768) return;
    var messages = [
        { name: 'Rahul', action: 'just booked a free visit', place: 'Rohini, Delhi' },
        { name: 'Priya', action: 'shortlisted a PG', place: 'Sector 62, Noida' },
        { name: 'Amit', action: 'scheduled a site visit', place: 'Sector 21, Gurgaon' },
        { name: 'Sneha', action: 'enquired about a PG', place: 'Tilak Nagar, Delhi' },
        { name: 'Vikram', action: 'just moved into a PG', place: 'Sector 126, Noida' },
    ];
    var idx = 0;
    var toast = document.createElement('div');
    toast.id = 'pziActivityToast';
    toast.style.cssText = 'position:fixed;left:16px;bottom:96px;z-index:40;max-width:280px;background:#fff;border-radius:14px;box-shadow:0 12px 30px rgba(15,39,72,.15);padding:12px 14px;display:flex;align-items:center;gap:10px;opacity:0;transform:translateY(12px);transition:opacity .4s ease,transform .4s ease;pointer-events:none;';
    toast.innerHTML = '<div style="width:32px;height:32px;border-radius:9999px;background:#d1fae5;color:#047857;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">●</div><div style="font-size:12.5px;line-height:1.35;color:#0f2748;"><span id="pziActivityText"></span></div>';
    document.body.appendChild(toast);

    function showNext() {
        var m = messages[idx % messages.length];
        idx++;
        document.getElementById('pziActivityText').innerHTML =
            '<strong>' + m.name + '</strong> ' + m.action + ' in <strong>' + m.place + '</strong>';
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(12px)';
        }, 4200);
    }

    setTimeout(function () {
        showNext();
        setInterval(showNext, 7000);
    }, 3000);
})();
</script>

@endpush