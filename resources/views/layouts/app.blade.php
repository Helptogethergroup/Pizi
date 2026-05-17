<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="theme-color" content="#0f2748">
    <meta name="description" content="@yield('meta_description', 'PGFind — Find verified PGs, hostels & co-living in Delhi NCR & Noida. Trusted by 10,000+ tenants. Real photos, verified owners, instant booking.')">
    <meta name="keywords" content="@yield('meta_keywords', 'pg in delhi, pg in noida, paying guest, hostel delhi ncr, coliving, girls pg, boys pg')">

    <title>@yield('title', 'PGFind — Find Verified PGs in Delhi NCR & Noida')</title>

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('title', 'PGFind — Find Verified PGs')">
    <meta property="og:description" content="@yield('meta_description', 'Find verified PGs near you in Delhi NCR.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230f2748'/%3E%3Cpath d='M30 70V35l20-15 20 15v35H55V52H45v18z' fill='%23ff6b5b'/%3E%3C/svg%3E">

    {{-- PWA manifest for future app --}}
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            ink: { 50: '#f6f7f9', 100: '#eceff4', 200: '#d4dae5', 300: '#a8b5cb', 400: '#7185a8', 500: '#4d628a', 600: '#3a4d70', 700: '#2f3f5c', 800: '#15355f', 900: '#0f2748', 950: '#0a1a30' },
            coral: { 50: '#fff3f1', 100: '#ffe3df', 200: '#ffc6bd', 300: '#ff9d8d', 400: '#ff8170', 500: '#ff6b5b', 600: '#ed4e3d', 700: '#c93b2c', 800: '#a53428', 900: '#883026' },
            cream: { DEFAULT: '#fefcf6', 100: '#fefcf6', 200: '#f6f5f1', 300: '#ede9dc' }
        }, fontFamily: {
            display: ['Fraunces','serif'],
            sans: ['Plus Jakarta Sans','sans-serif']
        }, animation: {
            'fade-up': 'fadeUp 0.6s ease-out',
            'fade-in': 'fadeIn 0.5s ease-out',
        }, keyframes: {
            fadeUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
            fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } }
        } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fefcf6; }
        h1, h2, h3, h4, .font-display { font-family: 'Fraunces', serif; letter-spacing: -0.02em; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .gradient-mesh {
            background-image:
                radial-gradient(at 0% 0%, rgba(255,107,91,0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(15,39,72,0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255,107,91,0.1) 0px, transparent 50%);
        }
        .hover-lift { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(15,39,72,0.15); }
        @media (max-width: 640px) {
            .text-balance { text-wrap: balance; }
        }
    </style>

    @stack('head')
</head>
<body class="text-ink-950 antialiased">

{{-- ===== HEADER ===== --}}
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-lg border-b border-ink-900/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-xl bg-ink-950 flex items-center justify-center group-hover:bg-coral-500 transition-colors">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-coral-500 group-hover:text-white transition-colors" fill="currentColor">
                        <path d="M12 2 2 9v13h7v-7h6v7h7V9z"/>
                    </svg>
                </div>
                <span class="font-display font-black text-xl text-ink-950">PG<span class="text-coral-500">Find</span></span>
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden lg:flex items-center gap-1">
                <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-ink-700 hover:text-coral-500 hover:bg-coral-50 transition">Home</a>
                <a href="{{ route('search') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-ink-700 hover:text-coral-500 hover:bg-coral-50 transition">Browse PGs</a>
                <a href="{{ route('blog.index') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-ink-700 hover:text-coral-500 hover:bg-coral-50 transition">Blog</a>
                <a href="{{ route('about') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-ink-700 hover:text-coral-500 hover:bg-coral-50 transition">About</a>
                <a href="{{ route('contact') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-ink-700 hover:text-coral-500 hover:bg-coral-50 transition">Contact</a>
            </nav>

            {{-- Right actions --}}
            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ url(match(auth()->user()->role) {
                        'admin' => '/admin',
                        'owner' => '/owner',
                        'telecaller' => '/telecaller',
                        'field_executive' => '/field',
                        'seo_manager' => '/seo',
                        default => '/'
                    }) }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-ink-950 text-cream hover:bg-ink-900 text-sm font-bold transition">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-block px-4 py-2 rounded-xl text-sm font-bold text-ink-700 hover:text-coral-500 transition">Login</a>
                    <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-coral-500 hover:bg-coral-600 text-white text-sm font-bold transition shadow-lg shadow-coral-500/30">
                        List Your PG
                    </a>
                @endauth

                {{-- Mobile menu button --}}
                <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="lg:hidden p-2 -mr-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobileMenu" class="hidden lg:hidden border-t border-ink-900/5 bg-white">
        <nav class="px-4 py-4 space-y-1">
            <a href="{{ route('home') }}" class="block px-4 py-3 rounded-xl text-sm font-semibold hover:bg-coral-50 hover:text-coral-500">🏠 Home</a>
            <a href="{{ route('search') }}" class="block px-4 py-3 rounded-xl text-sm font-semibold hover:bg-coral-50 hover:text-coral-500">🔍 Browse PGs</a>
            <a href="{{ route('blog.index') }}" class="block px-4 py-3 rounded-xl text-sm font-semibold hover:bg-coral-50 hover:text-coral-500">📝 Blog</a>
            <a href="{{ route('about') }}" class="block px-4 py-3 rounded-xl text-sm font-semibold hover:bg-coral-50 hover:text-coral-500">ℹ️ About</a>
            <a href="{{ route('contact') }}" class="block px-4 py-3 rounded-xl text-sm font-semibold hover:bg-coral-50 hover:text-coral-500">📞 Contact</a>

            <div class="pt-4 mt-4 border-t border-ink-900/5 grid grid-cols-2 gap-2">
                @auth
                    <a href="{{ url('/' . (auth()->user()->role === 'admin' ? 'admin' : (auth()->user()->role === 'owner' ? 'owner' : ''))) }}" class="text-center px-4 py-3 rounded-xl bg-ink-950 text-cream text-sm font-bold">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="w-full px-4 py-3 rounded-xl border border-ink-900/15 text-sm font-bold">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-center px-4 py-3 rounded-xl border border-ink-900/15 text-sm font-bold">Login</a>
                    <a href="{{ route('register') }}" class="text-center px-4 py-3 rounded-xl bg-coral-500 text-white text-sm font-bold">List PG</a>
                @endauth
            </div>
        </nav>
    </div>
</header>

{{-- Flash messages --}}
@if(session('success'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm font-semibold animate-fade-up">{{ session('success') }}</div>
    </div>
@endif
@if($errors->any())
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm animate-fade-up">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    </div>
@endif

<main>
    @yield('content')
</main>

{{-- ===== FOOTER ===== --}}
<footer class="bg-ink-950 text-cream mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 lg:gap-12">

            {{-- Brand --}}
            <div class="col-span-2">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-coral-500 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" class="w-5 h-5 text-white" fill="currentColor">
                            <path d="M12 2 2 9v13h7v-7h6v7h7V9z"/>
                        </svg>
                    </div>
                    <span class="font-display font-black text-2xl">PG<span class="text-coral-500">Find</span></span>
                </a>
                <p class="mt-4 text-sm text-cream/70 leading-relaxed max-w-sm">
                    India's most trusted verified PG aggregator. Real photos, verified owners, instant booking — find your next home in Delhi NCR & Noida.
                </p>
                <div class="mt-6 flex gap-3">
                    <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}" target="_blank" class="w-10 h-10 rounded-full bg-cream/10 hover:bg-emerald-500 flex items-center justify-center transition" title="WhatsApp">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-.607z"/></svg>
                    </a>
                    <a href="https://instagram.com/pgfind" target="_blank" class="w-10 h-10 rounded-full bg-cream/10 hover:bg-coral-500 flex items-center justify-center transition" title="Instagram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <a href="mailto:{{ env('BRAND_EMAIL', 'contact@pgfind.in') }}" class="w-10 h-10 rounded-full bg-cream/10 hover:bg-coral-500 flex items-center justify-center transition" title="Email">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </a>
                </div>
            </div>

            {{-- For tenants --}}
            <div>
                <h4 class="font-display font-bold text-base mb-4">For Tenants</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('search') }}" class="text-cream/70 hover:text-coral-500 transition">Browse PGs</a></li>
                    <li><a href="{{ route('city.show', 'delhi') }}" class="text-cream/70 hover:text-coral-500 transition">PG in Delhi</a></li>
                    <li><a href="{{ route('city.show', 'noida') }}" class="text-cream/70 hover:text-coral-500 transition">PG in Noida</a></li>
                    <li><a href="{{ route('city.show', 'gurgaon') }}" class="text-cream/70 hover:text-coral-500 transition">PG in Gurgaon</a></li>
                    <li><a href="{{ route('blog.index') }}" class="text-cream/70 hover:text-coral-500 transition">Tips & Guides</a></li>
                </ul>
            </div>

            {{-- For owners --}}
            <div>
                <h4 class="font-display font-bold text-base mb-4">For Owners</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('register') }}" class="text-cream/70 hover:text-coral-500 transition">List Your PG</a></li>
                    <li><a href="{{ route('login') }}" class="text-cream/70 hover:text-coral-500 transition">Owner Login</a></li>
                    <li><a href="{{ route('about') }}" class="text-cream/70 hover:text-coral-500 transition">How It Works</a></li>
                    <li><a href="{{ route('contact') }}" class="text-cream/70 hover:text-coral-500 transition">Pricing</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <h4 class="font-display font-bold text-base mb-4">Company</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('about') }}" class="text-cream/70 hover:text-coral-500 transition">About Us</a></li>
                    <li><a href="{{ route('contact') }}" class="text-cream/70 hover:text-coral-500 transition">Contact</a></li>
                    <li><a href="{{ route('blog.index') }}" class="text-cream/70 hover:text-coral-500 transition">Blog</a></li>
                    <li><a href="#" class="text-cream/70 hover:text-coral-500 transition">Privacy</a></li>
                    <li><a href="#" class="text-cream/70 hover:text-coral-500 transition">Terms</a></li>
                </ul>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-12 pt-8 border-t border-cream/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <p class="text-xs text-cream/50">
                &copy; {{ date('Y') }} PGFind. All rights reserved. Made with 🧡 in India.
            </p>
            <div class="flex items-center gap-4 text-xs text-cream/50">
                <a href="tel:{{ env('BRAND_PHONE', '+919999999999') }}" class="hover:text-coral-500 transition">📞 {{ env('BRAND_PHONE', '+919999999999') }}</a>
                <a href="mailto:{{ env('BRAND_EMAIL', 'contact@pgfind.in') }}" class="hover:text-coral-500 transition">✉️ {{ env('BRAND_EMAIL', 'contact@pgfind.in') }}</a>
            </div>
        </div>
    </div>
</footer>

{{-- Floating WhatsApp button (mobile-first, always visible) --}}
<a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}?text={{ urlencode('Hi PGFind, I need help finding a PG.') }}" target="_blank"
   class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full bg-emerald-500 hover:bg-emerald-600 shadow-2xl shadow-emerald-500/40 flex items-center justify-center hover:scale-110 transition-all"
   title="Chat on WhatsApp">
    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-.607z"/></svg>
</a>

@stack('scripts')
</body>
</html>