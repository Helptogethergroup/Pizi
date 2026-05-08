<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="theme-color" content="#0f2748">

    <title>@yield('title', 'pizi — PGs & Hostels in Delhi NCR & Noida')</title>
    <meta name="description" content="@yield('meta_description', 'Find verified PGs, hostels & co-living spaces across Delhi NCR and Noida. Filter by budget, locality, gender. Free site visits, zero brokerage.')">
    <meta name="keywords" content="@yield('meta_keywords', 'pg in delhi, pg in noida, hostel delhi, coliving noida, paying guest delhi ncr, ladies pg, boys pg')">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Pizi')">
    <meta property="og:description" content="@yield('meta_description', 'Verified PGs across Delhi NCR & Noida.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">

    {{-- Favicon (inline SVG for zero-asset setup) --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230f2748'/%3E%3Cpath d='M30 70V35l20-15 20 15v35H55V52H45v18z' fill='%23ff6b5b'/%3E%3C/svg%3E">

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
    </style>

    @yield('schema')
    @stack('head')
</head>
<body class="text-ink-950 antialiased">

{{-- NAV --}}
<header class="sticky top-0 z-40 bg-cream/80 backdrop-blur-md border-b border-ink-900/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 h-16 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <!-- <div class="w-9 h-9 rounded-xl bg-ink-900 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="w-5 h-5 text-coral-500" fill="currentColor"><path d="M12 2 2 9v13h7v-7h6v7h7V9z"/></svg>
            </div> -->
            <img src="{{asset("assets/images/logo.png")}}" height="100" width="150" alt="">
            <!-- <span class="font-display font-bold text-xl tracking-tight">Pizi</span> -->
        </a>

        <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
            <a href="{{ route('search') }}" class="hover:text-coral-600 transition">Browse PGs</a>
            <a href="{{ route('city.show', 'delhi') }}" class="hover:text-coral-600 transition">Delhi</a>
            <a href="{{ route('city.show', 'noida') }}" class="hover:text-coral-600 transition">Noida</a>
            <a href="{{ route('city.show', 'gurgaon') }}" class="hover:text-coral-600 transition">Gurgaon</a>
            <a href="{{ route('blog.index') }}" class="hover:text-coral-600 transition">Blog</a>
        </nav>

        <div class="flex items-center gap-2">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline-block text-sm font-medium px-3 py-2 hover:text-coral-600">Admin</a>
                @elseif(auth()->user()->isOwner())
                    <a href="{{ route('owner.dashboard') }}" class="hidden sm:inline-block text-sm font-medium px-3 py-2 hover:text-coral-600">Dashboard</a>
                @elseif(auth()->user()->isTeleCaller())
                    <a href="{{ route('telecaller.dashboard') }}" class="hidden sm:inline-block text-sm font-medium px-3 py-2 hover:text-coral-600">Leads</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
                    <button class="text-sm font-medium px-3 py-2 hover:text-coral-600">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hidden sm:inline-block text-sm font-medium px-3 py-2 hover:text-coral-600">Login</a>
                <a href="{{ route('register') }}" class="text-sm font-semibold px-4 py-2 rounded-full bg-ink-900 text-cream hover:bg-ink-800 transition">List your PG</a>
            @endauth
        </div>
    </div>
</header>

{{-- FLASH --}}
@if(session('success'))
    <div class="max-w-7xl mx-auto px-4 lg:px-8 mt-4">
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm">
            {{ session('success') }}
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

{{-- FOOTER --}}
<footer class="bg-ink-950 text-cream/80 mt-24">
   <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16 grid grid-cols-2 md:grid-cols-5 gap-8">
        <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-9 h-9 rounded-xl bg-cream flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-coral-500" fill="currentColor"><path d="M12 2 2 9v13h7v-7h6v7h7V9z"/></svg>
                </div>
                <span class="font-display font-bold text-xl text-cream">PGFind</span>
            </div>
            <p class="text-sm leading-relaxed">Verified PGs, hostels & coliving across Delhi NCR. Zero brokerage. Free site visits.</p>
        </div>
        <div>
            <h4 class="font-display font-bold text-cream mb-4">Cities</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('city.show', 'delhi') }}" class="hover:text-coral-400">PGs in Delhi</a></li>
                <li><a href="{{ route('city.show', 'noida') }}" class="hover:text-coral-400">PGs in Noida</a></li>
                <li><a href="{{ route('city.show', 'gurgaon') }}" class="hover:text-coral-400">PGs in Gurgaon</a></li>
                <li><a href="{{ route('city.show', 'ghaziabad') }}" class="hover:text-coral-400">PGs in Ghaziabad</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-display font-bold text-cream mb-4">Near Landmarks</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('landmarks.index') }}" class="hover:text-coral-400">All landmarks</a></li>
                <li><a href="{{ route('landmark.show', 'delhi-university-north-campus') }}" class="hover:text-coral-400">Near DU</a></li>
                <li><a href="{{ route('landmark.show', 'amity-university-noida') }}" class="hover:text-coral-400">Near Amity</a></li>
                <li><a href="{{ route('landmark.show', 'dlf-cyber-city') }}" class="hover:text-coral-400">Near Cyber City</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-display font-bold text-cream mb-4">Company</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('about') }}" class="hover:text-coral-400">About</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-coral-400">Contact</a></li>
                <li><a href="{{ route('blog.index') }}" class="hover:text-coral-400">Blog</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-coral-400">List your PG</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-display font-bold text-cream mb-4">Get in touch</h4>
            <ul class="space-y-2 text-sm">
                <li>📞 {{ env('BRAND_PHONE', '+91 97582 85929') }}</li>
                <li>✉ {{ env('BRAND_EMAIL', 'contact@pgfind.in') }}</li>
                <li>
                    <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '+91 99999 99999') }}" class="inline-flex items-center gap-2 mt-2 px-4 py-2 rounded-full bg-emerald-500 text-white font-semibold hover:bg-emerald-600">
                        WhatsApp Us
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="border-t border-cream/10 py-6 text-center text-xs text-cream/50">
        &copy; {{ date('Y') }} Pizi. All rights reserved.
    </div>
</footer>

@stack('scripts')
</body>
</html>
