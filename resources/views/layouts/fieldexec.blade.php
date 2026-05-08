<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2748">
    <title>@yield('title', 'Field Exec') — PGFind</title>

    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230f2748'/%3E%3Cpath d='M50 25 L70 50 L60 50 L60 75 L40 75 L40 50 L30 50 Z' fill='%23ff6b5b'/%3E%3C/svg%3E">

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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f6f5f1; }
        h1, h2, h3, .font-display { font-family: 'Fraunces', serif; letter-spacing: -0.02em; }
    </style>
</head>
<body class="text-ink-950">

<div class="min-h-screen flex">

    {{-- SIDEBAR (same style as admin/owner) --}}
    <aside class="w-64 bg-ink-950 text-cream flex-shrink-0 hidden md:flex flex-col">
        <a href="/" class="px-6 py-5 flex items-center gap-2 border-b border-cream/10">
            <div class="w-8 h-8 rounded-lg bg-coral-500 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="w-4 h-4 text-cream" fill="currentColor"><path d="M12 2 2 9v13h7v-7h6v7h7V9z"/></svg>
            </div>
            <span class="font-display font-bold text-lg">PGFind</span>
        </a>

        <nav class="flex-1 p-4 space-y-1 text-sm">
            <a href="{{ route('fieldexec.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('fieldexec.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">
                🏠 Today's Visits
            </a>
            <a href="{{ route('fieldexec.visits.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('fieldexec.visits.*') ? 'bg-cream/10 text-coral-500' : '' }}">
                📋 All Visits
            </a>
            <a href="{{ route('fieldexec.visits.index', ['filter' => 'closed']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5">
                ✅ Closed Visits
            </a>
            <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('notifications.*') ? 'bg-cream/10 text-coral-500' : '' }}">
                🔔 Notifications
                @php $u = auth()->user()->unreadNotifications->count(); @endphp
                @if($u > 0)
                    <span class="ml-auto px-2 py-0.5 rounded-full bg-coral-500 text-white text-xs font-bold">{{ $u }}</span>
                @endif
            </a>

            <div class="pt-6 mt-6 border-t border-cream/10">
                <a href="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 text-cream/70">← View site</a>
            </div>
        </nav>

        <div class="p-4 border-t border-cream/10">
            <div class="px-3 text-xs text-cream/50">Signed in as</div>
            <div class="px-3 text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
                <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-cream/5">Logout</button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <div class="flex-1 min-w-0">

        {{-- Top header (DESKTOP) - shows name + logout --}}
        <header class="hidden md:flex bg-white border-b border-ink-900/10 px-6 py-3 items-center justify-end gap-4">
            <span class="text-sm text-ink-900/70">Hi, <strong>{{ auth()->user()->name }}</strong></span>
            <span class="px-2 py-1 rounded-full text-xs bg-coral-500 text-white font-bold">🚗 Field Executive</span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm px-3 py-1.5 rounded-lg border border-ink-900/15 hover:bg-cream">Logout</button>
            </form>
        </header>

        {{-- Mobile header --}}
        <header class="md:hidden bg-ink-950 text-cream px-4 py-3 flex items-center justify-between">
            <div>
                <div class="text-xs text-cream/60">PGFind Field</div>
                <div class="font-display font-bold">@yield('title', 'Dashboard')</div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-cream/80">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="text-xs px-3 py-1.5 rounded-full bg-cream/10">Logout</button>
                </form>
            </div>
        </header>

        @if(session('success'))
            <div class="m-6 bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="m-6 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="p-6 lg:p-10">
            @yield('content')
        </div>
    </div>
</div>

{{-- MOBILE bottom tab bar (only on mobile, sidebar handles desktop) --}}
<nav class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-ink-900/10 md:hidden">
    <div class="grid grid-cols-4 max-w-md mx-auto">
        <a href="{{ route('fieldexec.dashboard') }}" class="flex flex-col items-center py-3 {{ request()->routeIs('fieldexec.dashboard') ? 'text-coral-500' : 'text-ink-900/60' }}">
            <div class="text-xl">🏠</div>
            <div class="text-xs font-semibold mt-0.5">Today</div>
        </a>
        <a href="{{ route('fieldexec.visits.index') }}" class="flex flex-col items-center py-3 {{ request()->routeIs('fieldexec.visits.*') ? 'text-coral-500' : 'text-ink-900/60' }}">
            <div class="text-xl">📋</div>
            <div class="text-xs font-semibold mt-0.5">Visits</div>
        </a>
        <a href="{{ route('notifications.index') }}" class="relative flex flex-col items-center py-3 {{ request()->routeIs('notifications.*') ? 'text-coral-500' : 'text-ink-900/60' }}">
            <div class="text-xl">🔔</div>
            @if($u ?? 0 > 0)
                <span class="absolute top-2 right-1/4 w-4 h-4 rounded-full bg-coral-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $u }}</span>
            @endif
            <div class="text-xs font-semibold mt-0.5">Alerts</div>
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