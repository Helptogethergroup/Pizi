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
    @stack('head')
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
                <a href="{{ route('admin.analytics.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.analytics.*') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Analytics</a>
                <a href="{{ route('admin.properties.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.properties.*') ? 'bg-cream/10 text-coral-500' : '' }}"> Properties</a>
                <a href="{{ route('admin.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 All Leads</a>
                <a href="{{ route('leads.manual.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('leads.manual.*') ? 'bg-cream/10 text-coral-500' : '' }}">+ Add Lead Manually</a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.users.*') ? 'bg-cream/10 text-coral-500' : '' }}">👥 Users</a>
                <a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.wallets.*') ? 'bg-cream/10 text-coral-500' : '' }}">💰 Wallets</a>
                <a href="{{ route('admin.pricing.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.pricing.*') ? 'bg-cream/10 text-coral-500' : '' }}">💲 Pricing</a>
                <a href="{{ route('admin.blogs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.blogs.*') ? 'bg-cream/10 text-coral-500' : '' }}">📝 Blogs</a>
                <a href="{{ route('admin.field-tracker.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.field-tracker.*') ? 'bg-cream/10 text-coral-500' : '' }}">Field Tracker</a>
                <a href="{{ route('admin.packages.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.packages.*') ? 'bg-cream/10 text-coral-500' : '' }}"> Packages</a>
            @elseif($role === 'owner')
                <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('owner.analytics') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.analytics') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Analytics</a>
                <a href="{{ route('owner.properties.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.properties.*') ? 'bg-cream/10 text-coral-500' : '' }}">🏠 My Properties</a>
                <a href="{{ route('owner.properties.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5">+ Add new</a>
                <a href="{{ route('owner.wallet') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.wallet') ? 'bg-cream/10 text-coral-500' : '' }}">💰 Wallet</a>
                <a href="{{ route('owner.blogs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.blogs.*') ? 'bg-cream/10 text-coral-500' : '' }}">📝 My Blogs</a>
                <a href="{{ route('owner.packages') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.packages') || request()->routeIs('owner.checkout') ? 'bg-cream/10 text-coral-500' : '' }}">💳 Buy Credits</a>
                <a href="{{ route('owner.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 Leads</a>
            @elseif($role === 'telecaller')
                <a href="{{ route('telecaller.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('telecaller.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('telecaller.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('telecaller.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 My Leads</a>
                <a href="{{ route('leads.manual.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('leads.manual.*') ? 'bg-cream/10 text-coral-500' : '' }}">+ Add Lead</a>

                @elseif($role === 'seo_manager')
                <a href="{{ route('seo.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('seo.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('seo.settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('seo.settings.*') ? 'bg-cream/10 text-coral-500' : '' }}">🔍 SEO Settings</a>
                <a href="{{ route('seo.blogs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('seo.blogs.*') ? 'bg-cream/10 text-coral-500' : '' }}">📝 Blogs</a>
       

            @endif
             <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 mt-4 {{ request()->routeIs('notifications.*') ? 'bg-cream/10 text-coral-500' : '' }}">
                🔔 Notifications
                @php $unread = auth()->user()->unreadNotifications->count(); @endphp
                @if($unread > 0)
                    <span class="ml-auto px-2 py-0.5 rounded-full bg-coral-500 text-white text-xs font-bold">{{ $unread }}</span>
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

    {{-- Main --}}
    <div class="flex-1 min-w-0">
        <header class="bg-white border-b border-ink-900/10 px-6 py-4 flex items-center justify-between md:hidden">
            <div class="font-display font-bold">PGFind</div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm">Logout</button></form>
        </header>

        {{-- Desktop top header with notifications --}}
        <header class="hidden md:flex bg-white border-b border-ink-900/10 px-6 py-3 items-center justify-end gap-4">
            <div id="notifBell" class="relative">
                <button onclick="toggleNotifPanel()" class="relative p-2 hover:bg-ink-900/5 rounded-lg">
                    <span class="text-2xl">🔔</span>
                    <span id="notifCount" class="hidden absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-coral-500 text-white text-xs font-bold flex items-center justify-center">0</span>
                </button>

                {{-- Notification dropdown --}}
                <div id="notifPanel" class="hidden absolute top-full right-0 mt-2 w-96 max-h-[500px] bg-white rounded-2xl border border-ink-900/10 shadow-xl overflow-hidden z-50">
                    <div class="p-4 border-b border-ink-900/10 flex justify-between items-center">
                        <strong class="font-display">Notifications</strong>
                        <a href="{{ route('notifications.index') }}" class="text-xs text-coral-600 font-semibold hover:underline">View all</a>
                    </div>
                    <div id="notifList" class="overflow-y-auto max-h-[400px]">
                        <div class="p-8 text-center text-sm text-ink-900/50">Loading...</div>
                    </div>
                </div>
            </div>
            <span class="text-sm text-ink-900/70">Hi, <strong>{{ auth()->user()->name }}</strong></span>
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

<script>
let notifPanelOpen = false;

function toggleNotifPanel() {
    const panel = document.getElementById('notifPanel');
    notifPanelOpen = !notifPanelOpen;
    panel.classList.toggle('hidden', !notifPanelOpen);
    if (notifPanelOpen) loadNotifications();
}

document.addEventListener('click', function(e) {
    const bell = document.getElementById('notifBell');
    if (bell && !bell.contains(e.target) && notifPanelOpen) {
        document.getElementById('notifPanel').classList.add('hidden');
        notifPanelOpen = false;
    }
});

async function loadNotifications() {
    try {
        const res = await fetch('{{ route('notifications.recent') }}');
        const data = await res.json();

        // Update count badge
        const countEl = document.getElementById('notifCount');
        if (data.unread_count > 0) {
            countEl.classList.remove('hidden');
            countEl.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
        } else {
            countEl.classList.add('hidden');
        }

        // Update list
        const list = document.getElementById('notifList');
        if (data.notifications.length === 0) {
            list.innerHTML = '<div class="p-8 text-center text-sm text-ink-900/50">No notifications yet</div>';
            return;
        }

        list.innerHTML = data.notifications.map(n => `
            <a href="${n.url}" class="flex items-start gap-3 p-4 border-b border-ink-900/5 hover:bg-cream ${!n.read_at ? 'bg-coral-50' : ''}">
                <div class="text-2xl">${n.icon}</div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm">${n.title}</div>
                    <div class="text-xs text-ink-900/60 mt-0.5">${n.message}</div>
                    <div class="text-xs text-ink-900/40 mt-1">${n.created_at}</div>
                </div>
                ${!n.read_at ? '<span class="flex-shrink-0 w-2 h-2 rounded-full bg-coral-500 mt-2"></span>' : ''}
            </a>
        `).join('');
    } catch (err) {
        console.error('Failed to load notifications', err);
    }
}

// Initial load + auto-refresh every 30 sec
loadNotifications();
setInterval(loadNotifications, 30000);
</script>
</body>
</html>
