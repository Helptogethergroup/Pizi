<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#0f2748">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Field Exec') — PGFind</title>
    
    {{-- PWA manifest --}}
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230f2748'/%3E%3Cpath d='M50 25 L70 50 L60 50 L60 75 L40 75 L40 50 L30 50 Z' fill='%23ff6b5b'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230f2748'/%3E%3Cpath d='M50 25 L70 50 L60 50 L60 75 L40 75 L40 50 L30 50 Z' fill='%23ff6b5b'/%3E%3C/svg%3E">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            ink: { 950: '#0a1a30', 900: '#0f2748', 800: '#15355f' },
            coral: { 500: '#ff6b5b', 600: '#ed4e3d' },
            cream: '#fefcf6'
        }, fontFamily: { display: ['Fraunces','serif'], sans: ['Plus Jakarta Sans','sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f6f5f1; padding-bottom: 80px; }
        h1, h2, h3, .font-display { font-family: 'Fraunces', serif; letter-spacing: -0.02em; }
        .ios-bottom-safe { padding-bottom: env(safe-area-inset-bottom); }
        @media (min-width: 768px) { body { padding-bottom: 0; } }
    </style>
</head>
<body class="text-ink-950">

{{-- Top header --}}
<header class="sticky top-0 z-30 bg-ink-950 text-cream px-4 py-3 flex items-center justify-between shadow-md">
    <div class="flex items-center gap-3">
        @yield('back-link')
        <div>
            <div class="text-xs text-cream/60">PGFind Field</div>
            <div class="font-display font-bold text-lg leading-none">@yield('title', 'Dashboard')</div>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="text-xs px-3 py-1.5 rounded-full bg-cream/10">Logout</button>
    </form>
</header>

{{-- Flash messages --}}
@if(session('success'))
    <div class="m-4 bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="m-4 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- Main content --}}
<main class="px-4 py-4">
    @yield('content')
</main>

{{-- Bottom tab bar (mobile-first) --}}
<nav class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-ink-900/10 ios-bottom-safe md:relative md:border-0">
    <div class="grid grid-cols-3 max-w-md mx-auto">
        <a href="{{ route('fieldexec.dashboard') }}" class="flex flex-col items-center py-3 {{ request()->routeIs('fieldexec.dashboard') ? 'text-coral-500' : 'text-ink-900/60' }}">
            <div class="text-xl">🏠</div>
            <div class="text-xs font-semibold mt-0.5">Today</div>
        </a>
        <a href="{{ route('fieldexec.visits.index') }}" class="flex flex-col items-center py-3 {{ request()->routeIs('fieldexec.visits.*') ? 'text-coral-500' : 'text-ink-900/60' }}">
            <div class="text-xl">📋</div>
            <div class="text-xs font-semibold mt-0.5">All Visits</div>
        </a>
        <a href="{{ route('fieldexec.visits.index', ['filter' => 'closed']) }}" class="flex flex-col items-center py-3 text-ink-900/60">
            <div class="text-xl">📊</div>
            <div class="text-xs font-semibold mt-0.5">Closed</div>
        </a>
    </div>
</nav>

@stack('scripts')
</body>
</html>