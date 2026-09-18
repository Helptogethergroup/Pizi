
@php
    $activeCitySlugs = \Illuminate\Support\Facades\Cache::remember('active_city_slugs', 600, function () {
        return \Illuminate\Support\Facades\DB::table('properties')
            ->join('cities', 'cities.id', '=', 'properties.city_id')
            ->where('properties.is_active', 1)
            ->whereNull('properties.deleted_at')
            ->distinct()
            ->pluck('cities.slug')
            ->toArray();
    });
@endphp




<!DOCTYPE html>

<html lang="en">
<head>
    
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0f2748">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pizi">
<link rel="apple-touch-icon" href="/assets/images/logo.png">

<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => console.log('SW registered'))
      .catch(err => console.log('SW failed:', err));
  });
}
</script>

<meta name="google-site-verification" content="JPhU1vm1gkBU61TtvQ1T1nOBYkWEu4qNw0_PvjAehQY">
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

<title>@yield('title', 'Pizi — Live Better. Stay Smarter.')</title>
<meta name="description" content="@yield('meta_description', 'Find verified PGs, hostels & co-living spaces across Delhi NCR and Noida. Filter by budget, locality, gender. Free site visits, zero brokerage.')">
<meta name="keywords" content="@yield('meta_keywords', 'pg in delhi, pg in noida, hostel delhi, coliving noida, paying guest delhi ncr, ladies pg, boys pg')">
<link rel="canonical" href="{{ url()->current() }}">

<meta property="og:type" content="website">
<meta property="og:title" content="@yield('title', 'Pizi')">
<meta property="og:description" content="@yield('meta_description', 'Verified PGs across Delhi NCR & Noida.')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    ink: { 950: '#0a1a30', 900: '#0f2748', 800: '#15355f', 700: '#1d4577' },
                    coral: { 50: '#fff3f1', 100: '#ffe3df', 400: '#ff8c7e', 500: '#ff6b5b', 600: '#ed4e3d', 700: '#c93b2c' },
                    cream: '#fefcf6',
                },
                fontFamily: {
                    display: ['"Fraunces"', 'serif'],
                    sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                },
            }
        }
    }
</script>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #fefcf6; }
    h1, h2, h3, .font-display { font-family: 'Fraunces', serif; letter-spacing: -0.02em; }
    .grain { background-image: radial-gradient(rgba(15, 39, 72, 0.04) 1px, transparent 1px); background-size: 16px 16px; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    @media (min-width:1024px){
        .logo-img{ height:5rem !important; }
    }
</style>

@yield('schema')
@stack('head')

<!-- Google Tag Manager -->
<script>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-NZW95H82');
</script>
<!-- End Google Tag Manager -->

</head>
<body class="text-ink-950 antialiased">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NZW95H82" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

{{-- ===== HEADER ===== --}}

@php
    $phone = '8006680092';
    $email = 'info@pizi.in';
    $whatsapp = '918006680092';
@endphp

{{-- ===== TOP CONTACT BAR ===== --}}
<div class="bg-ink-950 text-cream py-2">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 flex items-center justify-between text-xs">
        <div class="flex items-center gap-4">
            <a href="tel:{{ $phone }}" class="flex items-center gap-1 hover:text-coral-400">📞 {{ $phone }}</a>
            <a href="mailto:{{ $email }}" class="hidden sm:flex items-center gap-1 hover:text-coral-400">✉ {{ $email }}</a>
        </div>
        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noreferrer" class="flex items-center gap-1 hover:text-emerald-400">💬 WhatsApp</a>
    </div>
</div>
<header class="sticky top-0 z-40 bg-cream/80 backdrop-blur-md border-b border-ink-900/10">
    <div class="max-w-[1600px] mx-auto px-4 lg:px-8 h-20 flex items-center justify-between">

        {{-- LEFT: Logo --}}
        <a href="{{ route('home') }}" class="flex items-center group">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Pizi" class="logo-img" style="height:5rem; width:auto;">
        </a>

    {{-- CENTER: Desktop nav --}}
        <nav class="hidden md:flex items-center gap-1 lg:gap-2 text-sm font-medium">
            <a href="{{ route('search') }}" class="px-3 py-2 rounded-lg hover:bg-coral-50 hover:text-coral-600 transition whitespace-nowrap">Browse PGs</a>

            {{-- Cities dropdown --}}
            <div class="relative group">
                <button class="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-coral-50 hover:text-coral-600 transition">
                    Cities
                    <svg class="w-3.5 h-3.5 transition group-hover:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                {{-- dropdown panel --}}
                <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition absolute left-0 top-full pt-2 w-52 z-50">
                    <div class="bg-white rounded-2xl shadow-2xl shadow-ink-900/15 border border-ink-900/10 p-2">
                        <a href="{{ route('city.show', 'delhi') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Delhi</a>
                        <a href="{{ route('city.show', 'noida') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Noida</a>
                        <!--<a href="{{ route('city.show', 'gurgaon') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Gurgaon</a>-->
                        <!--<a href="{{ route('city.show', 'ghaziabad') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Ghaziabad</a>-->
                        <!--<a href="{{ route('city.show', 'faridabad') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Faridabad</a>-->
                        
                        @if(in_array('gurgaon', $activeCitySlugs))
                        <a href="{{ route('city.show', 'gurgaon') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Gurgaon</a>
                        @endif
                        @if(in_array('ghaziabad', $activeCitySlugs))
                        <a href="{{ route('city.show', 'ghaziabad') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Ghaziabad</a>
                        @endif
                        @if(in_array('faridabad', $activeCitySlugs))
                        <a href="{{ route('city.show', 'faridabad') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition">📍 Faridabad</a>
                        @endif
                    </div>
                </div>
            </div>
            <a href="{{ route('universities.index') }}" class="px-3 py-2 rounded-lg hover:bg-coral-50 hover:text-coral-600 transition whitespace-nowrap">🎓  pg's Near Universities</a>
           <a href="{{ route('blog.index') }}" class="px-3 py-2 rounded-lg hover:bg-coral-50 hover:text-coral-600 transition whitespace-nowrap">Blog</a>
<a href="{{ route('about') }}" class="hidden xl:inline-block px-3 py-2 rounded-lg hover:bg-coral-50 hover:text-coral-600 transition whitespace-nowrap">About</a>
<button type="button" onclick="openContactPopup()" class="hidden xl:inline-block px-3 py-2 rounded-lg hover:bg-coral-50 hover:text-coral-600 transition whitespace-nowrap">Contact</button>
<a href="{{ route('chat.page') }}" class="hidden xl:inline-block px-3 py-2 rounded-lg hover:bg-coral-50 hover:text-coral-600 transition whitespace-nowrap">💬 AI Chat</a>
        </nav>

             {{-- RIGHT: Phone box + Desktop buttons + Mobile hamburger --}}
        <div class="flex items-center gap-2 lg:gap-3">

            {{-- Prominent phone box --}}
            <a href="tel:{{ $phone }}" class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl border-2 border-coral-500/30 hover:border-coral-500 hover:bg-coral-50 transition">
                <span class="w-8 h-8 rounded-lg bg-coral-500 text-white flex items-center justify-center text-sm flex-shrink-0">📞</span>
                <span class="leading-tight">
                    <span class="block text-[10px] text-ink-900/50 font-semibold uppercase tracking-wide">Call us</span>
                    <span class="block text-sm font-bold text-ink-950">{{ $phone }}</span>
                </span>
            </a>
            <div class="hidden md:flex items-center gap-2">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium px-3 py-2 hover:text-coral-600">Admin</a>
                    @elseif(auth()->user()->isOwner())
                        <a href="{{ route('owner.dashboard') }}" class="text-sm font-medium px-3 py-2 hover:text-coral-600">Dashboard</a>
                    @elseif(auth()->user()->isTeleCaller())
                        <a href="{{ route('telecaller.dashboard') }}" class="text-sm font-medium px-3 py-2 hover:text-coral-600">Leads</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
                        <button class="text-sm font-medium px-3 py-2 hover:text-coral-600">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium px-3 py-2 hover:text-coral-600 whitespace-nowrap">Login</a>
                    <a href="https://play.google.com/store/apps/details?id=com.pizi.india" target="_blank" rel="noopener" class="hidden xl:flex items-center gap-1.5 text-sm font-semibold px-3 py-2 rounded-full bg-coral-50 text-coral-600 hover:bg-coral-100 transition whitespace-nowrap">📱 Get App</a>
                    <a href="{{ route('register') }}" class="text-sm font-semibold px-4 py-2 rounded-full bg-ink-900 text-cream hover:bg-ink-800 transition whitespace-nowrap">List your PG</a>
                @endauth
            </div>
            
            
            {{-- Mobile call icon --}}
            <a href="tel:{{ $phone }}"
               class="md:hidden w-10 h-10 rounded-xl bg-coral-500 text-white flex items-center justify-center shadow-md shadow-coral-500/30"
               aria-label="Call {{ $phone }}">
                <span class="text-lg">📞</span>
            </a>
                
            {{-- Mobile hamburger --}}
            <button type="button"
                    onclick="document.getElementById('piziMobileMenu').classList.remove('hidden'); document.body.style.overflow='hidden';"
                    class="md:hidden p-2 text-ink-900 hover:text-coral-600 transition"
                    aria-label="Open menu">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
</header>

{{-- ===== MOBILE MENU OVERLAY ===== --}}
<div id="piziMobileMenu" class="hidden fixed inset-0 z-[100] md:hidden">
    <div onclick="document.getElementById('piziMobileMenu').classList.add('hidden'); document.body.style.overflow='';"
         class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="absolute right-0 top-0 bottom-0 w-80 max-w-[85vw] bg-cream shadow-2xl overflow-y-auto">
        <div class="p-5 border-b border-ink-900/10 flex items-center justify-between bg-cream sticky top-0 z-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Pizi" style="height:32px; width:auto;">
            </a>
            <button type="button"
                    onclick="document.getElementById('piziMobileMenu').classList.add('hidden'); document.body.style.overflow='';"
                    class="p-2 text-ink-900 hover:text-coral-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="p-4 space-y-1">
            {{-- Login / Dashboard — kept right at the top so owners find it
                 immediately instead of scrolling through the whole menu. --}}
            <div class="space-y-2 pb-3 mb-2 border-b border-ink-900/10">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-ink-900 text-cream font-bold">⚡ Admin Dashboard</a>
                    @elseif(auth()->user()->isOwner())
                        <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-ink-900 text-cream font-bold">📊 My Dashboard</a>
                    @elseif(auth()->user()->isTeleCaller())
                        <a href="{{ route('telecaller.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-ink-900 text-cream font-bold">📞 My Leads</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-xl bg-ink-900 text-cream font-bold">🔐 Login</a>
                    <a href="{{ route('register') }}" class="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-xl bg-coral-500 text-white font-bold hover:bg-coral-600">➕ List your PG</a>
                @endauth
            </div>

            <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-coral-50 hover:text-coral-600 font-medium">🏠 Home</a>
            <a href="{{ route('search') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-coral-50 hover:text-coral-600 font-medium">🔍 Browse PGs</a>

       <div class="border-t border-ink-900/10 my-2 pt-2">
                <div class="px-4 py-1 text-xs uppercase font-bold text-ink-900/40">Cities</div>
                <!--@foreach(['delhi' => 'Delhi', 'noida' => 'Noida', 'gurgaon' => 'Gurgaon', 'ghaziabad' => 'Ghaziabad', 'faridabad' => 'Faridabad'] as $slug => $cityName)-->
                <!--    <a href="{{ route('city.show', $slug) }}"-->
                <!--       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('city.show') && request()->segment(2) === $slug ? 'bg-coral-50 text-coral-600' : 'text-ink-900 hover:bg-coral-50 hover:text-coral-600' }}">-->
                <!--        📍 {{ $cityName }}-->
                <!--    </a>-->
                <!--@endforeach-->
                
                
                @foreach(['delhi' => 'Delhi', 'noida' => 'Noida', 'gurgaon' => 'Gurgaon', 'ghaziabad' => 'Ghaziabad', 'faridabad' => 'Faridabad'] as $slug => $cityName)
                    @continue(!in_array($slug, $activeCitySlugs))
                    <a href="{{ route('city.show', $slug) }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition {{ request()->routeIs('city.show') && request()->segment(2) === $slug ? 'bg-coral-50 text-coral-600' : 'text-ink-900 hover:bg-coral-50 hover:text-coral-600' }}">
                        📍 {{ $cityName }}
                    </a>
                @endforeach
            </div>

            <div class="border-t border-ink-900/10 my-2 pt-2">
                
                <a href="{{ route('universities.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-coral-50 hover:text-coral-600 font-medium">🎓Pg's near Universities</a>
                
                <a href="{{ route('blog.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-coral-50 hover:text-coral-600 font-medium">📝 Blog</a>
                <a href="{{ route('about') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-coral-50 hover:text-coral-600 font-medium">ℹ About Us</a>
                
                <button type="button" onclick="document.getElementById('piziMobileMenu').classList.add('hidden'); document.body.style.overflow=''; openContactPopup();" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-coral-50 hover:text-coral-600 font-medium">✉ Contact</button>
                <a href="{{ route('chat.page') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-coral-50 hover:text-coral-600 font-medium">💬 AI Chat</a>
            </div>

            <div class="border-t border-ink-900/10 my-2 pt-3 space-y-2">
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-xl text-ink-900 hover:bg-rose-50 hover:text-rose-600 font-medium">🚪 Logout</button>
                    </form>
                @else
                    <a href="https://play.google.com/store/apps/details?id=com.pizi.india" target="_blank" rel="noopener" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-coral-50 text-coral-600 font-bold">📱 Get the App</a>
                @endauth
            </div>

            <div class="border-t border-ink-900/10 my-2 pt-3 space-y-2">
                <a href="tel:8006680092" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 font-bold">📞 Call: 8006680092</a>
                <a href="mailto:info@pizi.in" class="flex items-center gap-3 px-4 py-2 rounded-xl text-ink-900/80 text-sm">✉ info@pizi.in</a>
                <!--<a href="https://wa.me/918006680092" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-500 text-white font-bold hover:bg-emerald-600">💬 WhatsApp Us</a>-->
            </div>
        </nav>
    </div>
</div>

{{-- FLASH MESSAGES --}}
@if(session('success'))
    <div class="max-w-7xl mx-auto px-4 lg:px-8 mt-4">
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    </div>
@endif
@if(session('error'))
    <div class="max-w-7xl mx-auto px-4 lg:px-8 mt-4">
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    </div>
@endif
@if($errors->any())
    <div class="max-w-7xl mx-auto px-4 lg:px-8 mt-4">
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    </div>
@endif

<main>@yield('content')</main>

<!--@php-->
<!--    $phone = '8006680092';-->
<!--    $email = 'info@pizi.in';-->
<!--    $whatsapp = '918006680092';-->
<!--@endphp-->

<!--{{-- ===== TOP CONTACT BAR ===== --}}-->
<!--<div class="bg-ink-950 text-cream py-2">-->
<!--    <div class="max-w-7xl mx-auto px-4 lg:px-8 flex items-center justify-between text-xs">-->
<!--        <div class="flex items-center gap-4">-->
<!--            <a href="tel:{{ $phone }}" class="flex items-center gap-1 hover:text-coral-400">📞 {{ $phone }}</a>-->
<!--            <a href="mailto:{{ $email }}" class="hidden sm:flex items-center gap-1 hover:text-coral-400">✉ {{ $email }}</a>-->
<!--        </div>-->
<!--        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noreferrer" class="flex items-center gap-1 hover:text-emerald-400">💬 WhatsApp</a>-->
<!--    </div>-->
<!--</div>-->

<!--footer\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\-->


<footer class="relative bg-gradient-to-b from-ink-900 to-ink-950 text-cream/80 mt-24 overflow-hidden">

    {{-- glow + texture --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-24 right-1/4 w-96 h-96 bg-coral-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 -left-20 w-80 h-80 bg-coral-400/5 rounded-full blur-3xl"></div>
    </div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;"></div>

    {{-- ===== COMPACT ENQUIRY STRIP ===== --}}
    <div class="relative border-b border-cream/10">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-8 grid lg:grid-cols-5 gap-6 items-center">
                     <div class="lg:col-span-2">
                            <a href="https://play.google.com/store/apps/details?id=com.pizi.india" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-3 mb-5 group hover:opacity-90 transition">
                    <div class="relative">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-coral-400 to-coral-600 flex items-center justify-center shadow-xl shadow-coral-500/30 group-hover:scale-105 transition">
                            <span class="text-white font-display font-black text-3xl">P</span>
                        </div>
                        <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-400 border-2 border-ink-950 flex items-center justify-center text-xs">✓</span>
                    </div>
                    <div>
                        <div class="font-display font-black text-2xl text-cream leading-none">Pizi App</div>
                        <div class="text-[10px] text-coral-400 font-bold tracking-widest uppercase mt-1">📱 Download Now</div>
                    </div>
                </a>
                <h3 class="font-display font-black text-2xl lg:text-3xl text-cream">Looking for a PG?</h3>
                <p class="text-cream/60 mt-2">Fill the form — our team will call you within 30 minutes.</p>
                <div class="mt-4 flex flex-wrap gap-5 text-sm">
                    <a href="tel:{{ $phone }}" class="flex items-center gap-2 hover:text-coral-400 transition">📞 <span class="font-bold text-cream">{{ $phone }}</span></a>
                    <a href="mailto:{{ $email }}" class="flex items-center gap-2 hover:text-coral-400 transition">✉ <span class="font-bold text-cream">{{ $email }}</span></a>
                </div>
            </div>

            <form id="piziEnquiryForm" class="lg:col-span-3 bg-white text-ink-950 rounded-2xl p-4 lg:p-5 shadow-2xl shadow-black/30">
                @csrf
                <div id="piziEnquirySuccess" class="hidden mb-3 p-2.5 bg-emerald-50 border border-emerald-300 rounded-lg text-sm text-emerald-800 font-semibold">✅ Thanks! We will contact you soon.</div>
                <div id="piziEnquiryError" class="hidden mb-3 p-2.5 bg-rose-50 border border-rose-300 rounded-lg text-sm text-rose-800 font-semibold">❌ Failed to send. Please try again.</div>
                <div class="grid sm:grid-cols-2 gap-2.5">
                    <input type="text" id="pizi_enq_name" name="name" required placeholder="Your Name *" class="w-full px-4 py-2.5 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">
                    <input type="tel" id="pizi_enq_phone" name="phone" required pattern="[0-9]{10}" placeholder="Phone (10-digit) *" class="w-full px-4 py-2.5 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">
                </div>
                <input type="text" id="pizi_enq_message" name="message" placeholder="What kind of PG are you looking for? (Optional)" class="w-full mt-2.5 px-4 py-2.5 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">
                <button type="submit" id="pizi_enq_btn" class="w-full mt-2.5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold shadow-lg shadow-coral-500/30 transition">Submit Enquiry →</button>
            </form>
        </div>
    </div>

    {{-- ===== LINKS ===== --}}
    <div class="relative max-w-7xl mx-auto px-4 lg:px-8 py-14 grid grid-cols-2 md:grid-cols-4 gap-10">
        <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-4">
                
                
                  <a href="{{ route('home') }}" class="flex items-center group flex-shrink-0">
            <img src="{{ asset('assets/images/pizi-logo.jpeg') }}" alt="Pizi" class="logo-img" style="height:5rem; width:auto;">
        </a>
                <!--<div class="w-11 h-11 rounded-xl bg-coral-500 flex items-center justify-center font-display font-black text-cream text-xl shadow-lg shadow-coral-500/30">P</div>-->
                <!--<span class="font-display font-black text-2xl text-cream">Pizi</span>-->
            </div>
            <p class="text-sm leading-relaxed text-cream/60">Verified PGs, hostels & co-living across Delhi NCR. Zero brokerage. Free site visits.</p>

       {{-- social icons --}}
            <div class="flex items-center gap-2 mt-5">
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noreferrer" class="w-9 h-9 rounded-full bg-cream/10 hover:bg-[#25D366] text-cream flex items-center justify-center transition" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="https://www.instagram.com/pizi.in?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" rel="noreferrer" class="w-9 h-9 rounded-full bg-cream/10 hover:bg-gradient-to-tr hover:from-[#feda75] hover:via-[#d62976] hover:to-[#4f5bd5] text-cream flex items-center justify-center transition" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.facebook.com/piziindia" target="_blank" rel="noreferrer" class="w-9 h-9 rounded-full bg-cream/10 hover:bg-[#1877F2] text-cream flex items-center justify-center transition" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <!--<a href="https://www.youtube.com" target="_blank" rel="noreferrer" class="w-9 h-9 rounded-full bg-cream/10 hover:bg-[#FF0000] text-cream flex items-center justify-center transition" title="YouTube"><i class="fa-brands fa-youtube"></i></a>-->
                <a href="mailto:{{ $email }}" class="w-9 h-9 rounded-full bg-cream/10 hover:bg-coral-500 text-cream flex items-center justify-center transition" title="Email"><i class="fa-solid fa-envelope"></i></a>
            </div>
        </div>

        <div>
            <h4 class="font-display font-bold text-cream mb-4">Cities</h4>
            <ul class="space-y-2.5 text-sm">
                <li><a href="{{ url('/pg-in-delhi') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Delhi</a></li>
                <li><a href="{{ url('/pg-in-noida') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Noida</a></li>
                <!--<li><a href="{{ url('/pg-in-gurgaon') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Gurgaon</a></li>-->
                <!--<li><a href="{{ url('/pg-in-ghaziabad') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Ghaziabad</a></li>-->
                <!--<li><a href="{{ url('/pg-in-faridabad') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Faridabad</a></li>-->
                
                
                @if(in_array('gurgaon', $activeCitySlugs))
                <li><a href="{{ url('/pg-in-gurgaon') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Gurgaon</a></li>
                @endif
                @if(in_array('ghaziabad', $activeCitySlugs))
                <li><a href="{{ url('/pg-in-ghaziabad') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Ghaziabad</a></li>
                @endif
                @if(in_array('faridabad', $activeCitySlugs))
                <li><a href="{{ url('/pg-in-faridabad') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">PGs in Faridabad</a></li>
                @endif
                
                
            </ul>
        </div>

             <div>
            <h4 class="font-display font-bold text-cream mb-4">Company</h4>
            <ul class="space-y-2.5 text-sm">
                <li><a href="{{ route('about') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">About</a></li>
                <li><a href="{{ route('contact') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">Contact</a></li>
                <li><a href="{{ route('blog.index') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">Blog</a></li>
                <li><a href="{{ route('register') }}" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">List your PG</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=com.pizi.india" target="_blank" rel="noopener" class="text-cream/60 hover:text-coral-400 hover:translate-x-1 inline-block transition">📱 Get the App</a></li>
               <li> <a href="{{ route('privacy-policy') }}" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
            </ul>
        </div>

        <div>
            <h4 class="font-display font-bold text-cream mb-4">Get in touch</h4>
            <ul class="space-y-3 text-sm">
                <li class="flex items-start gap-2"><span>📞</span><a href="tel:{{ $phone }}" class="text-cream/60 hover:text-coral-400 font-medium transition">{{ $phone }}</a></li>
                <li class="flex items-start gap-2"><span>✉</span><a href="mailto:{{ $email }}" class="text-cream/60 hover:text-coral-400 break-all transition">{{ $email }}</a></li>
                <li><a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 mt-2 px-4 py-2 rounded-full bg-emerald-500 text-white font-semibold hover:bg-emerald-600 text-xs transition shadow-lg shadow-emerald-500/20">💬 WhatsApp Us</a></li>
            </ul>
        </div>
    </div>

    <div class="relative border-t border-cream/10 py-6">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-cream/50">
            <span>&copy; {{ date('Y') }} Pizi.in · All rights reserved</span>
            <span class="flex items-center gap-4">
                <a href="#" class="hover:text-coral-400 transition">Privacy</a>
                <a href="#" class="hover:text-coral-400 transition">Terms</a>
                <span>Developed By <span class="text-coral-400">♥</span>HelpTogetherGroup</span>
            </span>
        </div>
    </div>
</footer>
{{--
   floating icon
--}}
<style>
    @keyframes pzPop { from { opacity:0; transform: translateY(12px) scale(.85); } to { opacity:1; transform: translateY(0) scale(1); } }
    @keyframes pzRing { 0%,100% { box-shadow: 0 0 0 0 rgba(255,107,91,.5); } 50% { box-shadow: 0 0 0 12px rgba(255,107,91,0); } }
    #pzContactMenu.open { display: flex; }
    #pzContactMenu .pz-item { animation: pzPop .35s cubic-bezier(.16,1,.3,1) both; }
    #pzContactMenu .pz-item:nth-child(1){ animation-delay:.03s; }
    #pzContactMenu .pz-item:nth-child(2){ animation-delay:.08s; }
    #pzContactMenu .pz-item:nth-child(3){ animation-delay:.13s; }
    #pzContactMenu .pz-item:nth-child(4){ animation-delay:.18s; }
    #pzContactToggle.ringing { animation: pzRing 2s ease-in-out infinite; }
</style>

<div id="pzContactFab" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

    {{-- Expandable menu --}}
    <div id="pzContactMenu" class="hidden flex-col items-end gap-3">
        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noreferrer"
           class="pz-item group flex items-center gap-3" title="WhatsApp">
            <span class="px-3 py-1.5 rounded-lg bg-white text-ink-950 text-sm font-semibold shadow-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap">WhatsApp</span>
            <span class="w-12 h-12 rounded-full bg-[#25D366] text-white flex items-center justify-center shadow-xl hover:scale-110 transition text-xl"><i class="fa-brands fa-whatsapp"></i></span>
        </a>
        <a href="tel:{{ $phone }}"
           class="pz-item group flex items-center gap-3" title="Call us">
            <span class="px-3 py-1.5 rounded-lg bg-white text-ink-950 text-sm font-semibold shadow-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap">Call {{ $phone }}</span>
            <span class="w-12 h-12 rounded-full bg-coral-500 text-white flex items-center justify-center shadow-xl hover:scale-110 transition text-lg"><i class="fa-solid fa-phone"></i></span>
        </a>
        <a href="https://www.instagram.com/pizi.in?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" rel="noreferrer"
           class="pz-item group flex items-center gap-3" title="Instagram">
            <span class="px-3 py-1.5 rounded-lg bg-white text-ink-950 text-sm font-semibold shadow-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap">Instagram</span>
            <span class="w-12 h-12 rounded-full text-white flex items-center justify-center shadow-xl hover:scale-110 transition text-xl" style="background:linear-gradient(45deg,#feda75,#fa7e1e,#d62976,#962fbf,#4f5bd5);"><i class="fa-brands fa-instagram"></i></span>
        </a>
        <a href="https://www.facebook.com/piziindia" target="_blank" rel="noreferrer"
           class="pz-item group flex items-center gap-3" title="Facebook">
            <span class="px-3 py-1.5 rounded-lg bg-white text-ink-950 text-sm font-semibold shadow-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap">Facebook</span>
            <span class="w-12 h-12 rounded-full bg-[#1877F2] text-white flex items-center justify-center shadow-xl hover:scale-110 transition text-xl"><i class="fa-brands fa-facebook-f"></i></span>
        </a>
    </div>

    {{-- Main toggle button --}}
    <button id="pzContactToggle" type="button" onclick="pzToggleContact()"
            class="ringing w-16 h-16 bg-coral-500 hover:bg-coral-600 rounded-full flex items-center justify-center shadow-2xl shadow-coral-500/50 transition-transform hover:scale-105"
            title="Contact us" aria-label="Contact us">
        <i id="pzContactIcon" class="fa-solid fa-comment-dots text-white text-2xl transition-transform duration-300"></i>
    </button>
</div>

<script>
function pzToggleContact() {
    var menu = document.getElementById('pzContactMenu');
    var icon = document.getElementById('pzContactIcon');
    var btn  = document.getElementById('pzContactToggle');
    var isOpen = menu.classList.toggle('open');
    if (isOpen) {
        icon.classList.remove('fa-comment-dots');
        icon.classList.add('fa-xmark');
        icon.style.transform = 'rotate(90deg)';
        btn.classList.remove('ringing');
    } else {
        icon.classList.remove('fa-xmark');
        icon.classList.add('fa-comment-dots');
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>

{{-- ===== AJAX FORM SUBMIT ===== --}}
<script>
document.getElementById('piziEnquiryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var btn = document.getElementById('pizi_enq_btn');
    var successBox = document.getElementById('piziEnquirySuccess');
    var errorBox = document.getElementById('piziEnquiryError');
    successBox.classList.add('hidden');
    errorBox.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = 'Sending...';

    var data = {
        name: document.getElementById('pizi_enq_name').value,
        phone: document.getElementById('pizi_enq_phone').value,
        message: document.getElementById('pizi_enq_message').value || 'Footer enquiry - Looking for PG',
        source: 'footer_enquiry',
        _token: document.querySelector('input[name="_token"]').value
    };

    try {
        var res = await fetch('/leads', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': data._token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        if (res.ok) {
            successBox.classList.remove('hidden');
            document.getElementById('pizi_enq_name').value = '';
            document.getElementById('pizi_enq_phone').value = '';
            document.getElementById('pizi_enq_message').value = '';
        } else {
            errorBox.classList.remove('hidden');
        }
    } catch (err) {
        errorBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Submit Enquiry →';
    }
});
</script>




{{-- ===== CONTACT POPUP MODAL ===== --}}
<div id="contactPopup" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4">
    {{-- Backdrop --}}
    <div onclick="closeContactPopup()" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    {{-- Modal --}}
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-popup">
        
        {{-- Header --}}
        <div class="bg-gradient-to-br from-coral-500 to-coral-600 px-6 py-5 relative">
            <button onclick="closeContactPopup()" class="absolute top-4 right-4 text-white/80 hover:text-white text-2xl leading-none">×</button>
            <h2 class="font-display font-black text-2xl text-white">Yes, I'm interested! 🎉</h2>
            <p class="text-white/90 text-sm mt-1">Fill your details — we'll call within 30 minutes.</p>
        </div>

        {{-- Body --}}
        <div class="p-6">
            <div id="contactPopupSuccess" class="hidden mb-4 p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-sm text-emerald-800 font-semibold">
                ✅ Thanks! Our team will contact you soon.
            </div>
            <div id="contactPopupError" class="hidden mb-4 p-3 bg-rose-50 border border-rose-300 rounded-xl text-sm text-rose-800 font-semibold">
                ❌ Failed to send. Please try again.
            </div>

            <form id="contactPopupForm" class="space-y-3">
                @csrf
                <input type="text" id="cp_name" name="name" required placeholder="Your Name *" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">
                
                <input type="email" id="cp_email" name="email" placeholder="Email (optional)" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">
                
                <input type="tel" id="cp_phone" name="phone" required pattern="[0-9]{10}" maxlength="10" placeholder="Phone (10-digit) *" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">
                
                <select id="cp_type" name="lookingfor" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm text-ink-900/70">
                    <option value="">What are you looking for?</option>
                    <option value="Find a PG">🏠 Find a PG</option>
                    <option value="List my PG">🏢 List my PG</option>
                    <option value="General Enquiry">💬 General Enquiry</option>
                </select>
                
                <textarea id="cp_message" name="message" rows="2" placeholder="Message (optional)" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm"></textarea>

                <button type="submit" id="cp_btn" class="w-full py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold shadow-lg shadow-coral-500/30 transition">
                    Submit →
                </button>
            </form>

            {{-- Quick contact --}}
            <div class="mt-4 pt-4 border-t border-ink-900/10 flex items-center justify-center gap-4 text-sm">
                <a href="tel:8006680092" class="flex items-center gap-1.5 text-coral-600 font-semibold hover:underline">📞 Call</a>
                <span class="text-ink-900/20">|</span>
                <a href="https://wa.me/918006680092" target="_blank" class="flex items-center gap-1.5 text-emerald-600 font-semibold hover:underline">💬 WhatsApp</a>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes popupIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-popup { animation: popupIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
</style>

<script>
function openContactPopup() {
    document.getElementById('contactPopup').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('cp_name').focus(), 300);
}

function closeContactPopup() {
    document.getElementById('contactPopup').classList.add('hidden');
    document.body.style.overflow = '';
}

// ESC key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeContactPopup();
});

// Form submit (AJAX → admin leads)
document.getElementById('contactPopupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var btn = document.getElementById('cp_btn');
    var successBox = document.getElementById('contactPopupSuccess');
    var errorBox = document.getElementById('contactPopupError');
    successBox.classList.add('hidden');
    errorBox.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = 'Sending...';

    var lookingfor = document.getElementById('cp_type').value;
    var msg = document.getElementById('cp_message').value;
    var fullMessage = (lookingfor ? '[' + lookingfor + '] ' : '') + (msg || 'Contact popup enquiry');

    var data = {
        name: document.getElementById('cp_name').value,
        phone: document.getElementById('cp_phone').value,
        email: document.getElementById('cp_email').value || null,
        message: fullMessage,
        source: 'contact_popup',
        _token: document.querySelector('meta[name=csrf-token]').content
    };

    try {
        var res = await fetch('/leads', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': data._token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        if (res.ok) {
            successBox.classList.remove('hidden');
            document.getElementById('contactPopupForm').reset();
            setTimeout(() => closeContactPopup(), 2000);
        } else {
            errorBox.classList.remove('hidden');
        }
    } catch (err) {
        errorBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Submit →';
    }
});
</script>
{{-- ===== END CONTACT POPUP ===== --}}

<script>
// Property-card hover carousel — auto-cycles the stacked photos while the
// card is hovered, with a matching dot indicator. Works for however many
// property-card components are on the page (search results, homepage
// rows, etc.) without needing per-page wiring.
(function () {
    document.querySelectorAll('.pz-card-carousel').forEach(function (card) {
        var slides = card.querySelectorAll('.pz-carousel-slide');
        var dots = card.querySelectorAll('.pz-carousel-dots span');
        if (slides.length < 2) return;
        var i = 0, timer = null;
        function show(n) {
            slides.forEach(function (s, idx) { s.style.opacity = idx === n ? '1' : '0'; });
            dots.forEach(function (d, idx) { d.style.background = idx === n ? '#fff' : 'rgba(255,255,255,.6)'; });
        }
        card.addEventListener('mouseenter', function () {
            timer = setInterval(function () { i = (i + 1) % slides.length; show(i); }, 900);
        });
        card.addEventListener('mouseleave', function () {
            clearInterval(timer);
            i = 0;
            show(0);
        });
    });
})();
</script>

@stack('scripts')



@include('chat.professional')




</body>
</html>