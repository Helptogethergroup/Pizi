<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — PGFind</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230f2748'/%3E%3Cpath d='M30 70V35l20-15 20 15v35H55V52H45v18z' fill='%23ff6b5b'/%3E%3C/svg%3E">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            ink: { 950: '#0a1a30', 900: '#0f2748', 800: '#15355f' },
            coral: { 500: '#ff6b5b', 600: '#ed4e3d' },
            cream: '#fefcf6'
        }, fontFamily: { display: ['Fraunces','serif'], sans: ['Plus Jakarta Sans','sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Plus Jakarta Sans',sans-serif;background:#f6f5f1}h1,h2,h3,.font-display{font-family:'Fraunces',serif;letter-spacing:-0.02em}</style>
</head>
<body class="text-ink-950">

<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="w-64 bg-ink-950 text-cream flex-shrink-0 hidden md:flex flex-col">
        <a href="/" class="px-6 py-5 flex items-center gap-2 border-b border-cream/10">
            <div class="w-8 h-8 rounded-lg bg-coral-500 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="w-4 h-4 text-cream" fill="currentColor"><path d="M12 2 2 9v13h7v-7h6v7h7V9z"/></svg>
            </div>
            <span class="font-display font-bold text-lg">PGFind</span>
        </a>

        <nav class="flex-1 p-4 space-y-1 text-sm">
            @php $role = auth()->user()->role; @endphp

            @if($role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('admin.properties.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.properties.*') ? 'bg-cream/10 text-coral-500' : '' }}"> Properties</a>
                <a href="{{ route('admin.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 All Leads</a>
                <a href="{{ route('leads.manual.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('leads.manual.*') ? 'bg-cream/10 text-coral-500' : '' }}">+ Add Lead Manually</a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.users.*') ? 'bg-cream/10 text-coral-500' : '' }}">👥 Users</a>
                <a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.wallets.*') ? 'bg-cream/10 text-coral-500' : '' }}">💰 Wallets</a>
                <a href="{{ route('admin.pricing.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.pricing.*') ? 'bg-cream/10 text-coral-500' : '' }}">💲 Pricing</a>
                <a href="{{ route('admin.field-tracker.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.field-tracker.*') ? 'bg-cream/10 text-coral-500' : '' }}">Field Tracker</a>
                <a href="{{ route('admin.packages.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.packages.*') ? 'bg-cream/10 text-coral-500' : '' }}"> Packages</a>
            @elseif($role === 'owner')
                <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('owner.properties.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.properties.*') ? 'bg-cream/10 text-coral-500' : '' }}">🏠 My Properties</a>
                <a href="{{ route('owner.properties.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5">+ Add new</a>
                <a href="{{ route('owner.wallet') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.wallet') ? 'bg-cream/10 text-coral-500' : '' }}">💰 Wallet</a>
                <a href="{{ route('owner.packages') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.packages') || request()->routeIs('owner.checkout') ? 'bg-cream/10 text-coral-500' : '' }}">💳 Buy Credits</a>
                <a href="{{ route('owner.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 Leads</a>
            @elseif($role === 'telecaller')
                <a href="{{ route('telecaller.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('telecaller.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('telecaller.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('telecaller.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 My Leads</a>
                <a href="{{ route('leads.manual.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('leads.manual.*') ? 'bg-cream/10 text-coral-500' : '' }}">+ Add Lead</a>
            @endif

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

    {{-- Main --}}
    <div class="flex-1 min-w-0">
        <header class="bg-white border-b border-ink-900/10 px-6 py-4 flex items-center justify-between md:hidden">
            <div class="font-display font-bold">PGFind</div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm">Logout</button></form>
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

@stack('scripts')
</body>
</html>
