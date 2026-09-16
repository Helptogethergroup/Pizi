<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') Pizi</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
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
        
        /* Mobile sidebar - ONLY apply transform on mobile */
        @media (max-width: 767px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                /* Let top:0 + bottom:0 size this to the REAL visible screen —
                   the h-screen (100vh) utility class conflicts with that on
                   phones/in-app browsers whose address bar isn't counted in
                   100vh, which was pushing the Logout button off-screen. */
                height: auto;
                z-index: 50;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            #sidebar.open {
                transform: translateX(0);
            }
        }
        
        /* Desktop - always visible, no transform */
        @media (min-width: 768px) {
            #sidebar {
                position: relative;
                transform: none !important;
                display: flex;
            }
        }
    </style>
    @stack('head')
</head>
<body class="text-ink-950">

<div class="min-h-screen flex">

    {{-- ===== MOBILE BACKDROP ===== --}}
    <div id="sidebarBackdrop" 
         onclick="closeSidebar()" 
         class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden"></div>

    {{-- ===== SIDEBAR ===== --}}
    <aside id="sidebar"
           class="w-64 bg-ink-950 text-cream flex-shrink-0 flex flex-col h-screen md:h-auto overflow-y-auto">
        
        {{-- Logo + Close button --}}
        <div class="px-6 py-5 flex items-center justify-between border-b border-cream/10 flex-shrink-0">
            <a href="/" class="flex items-center gap-2 bg-white rounded-xl px-3 py-2">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Pizi" class="h-9 w-auto">
            </a>
            <button onclick="closeSidebar()" class="md:hidden p-1 text-cream/70 hover:text-cream">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 p-4 space-y-1 text-sm overflow-y-auto">
            @php $role = auth()->user()->role; @endphp

            @if($role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('admin.analytics.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.analytics.*') ? 'bg-cream/10 text-coral-500' : '' }}">📈 Analytics</a>
              <a href="/admin/chat-analytics" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->path() === 'admin/chat-analytics' ? 'bg-cream/10 text-coral-500' : '' }}">💬 Chat Analytics</a>
                <a href="{{ route('admin.properties.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.properties.*') ? 'bg-cream/10 text-coral-500' : '' }}">🏠 Properties</a>
                <a href="{{ route('admin.properties.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5">+ Add Property</a>
                <a href="{{ route('admin.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 All Leads</a>
                <a href="{{ route('admin.telecallers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.telecallers.*') ? 'bg-cream/10 text-coral-500' : '' }}">📞 Telecallers</a>
                <a href="{{ route('admin.ad-lead-forms.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.ad-lead-forms.*') ? 'bg-cream/10 text-coral-500' : '' }}">📋 Ad Form Mapping</a>
                <a href="{{ route('admin.invoices.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.invoices.*') ? 'bg-cream/10 text-coral-500' : '' }}">🧾 Invoices</a>
                <a href="{{ route('leads.manual.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('leads.manual.*') ? 'bg-cream/10 text-coral-500' : '' }}">+ Add Lead</a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.users.*') ? 'bg-cream/10 text-coral-500' : '' }}">👥 Users</a>
                <a href="{{ route('admin.users.activity') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.users.activity') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Login Activity</a>
                <a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.wallets.*') ? 'bg-cream/10 text-coral-500' : '' }}">💰 Wallets</a>
                <a href="{{ route('admin.pricing.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.pricing.*') ? 'bg-cream/10 text-coral-500' : '' }}">💲 Pricing</a>
                <a href="{{ route('admin.blogs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.blogs.*') ? 'bg-cream/10 text-coral-500' : '' }}">📝 Blogs</a>
                <a href="{{ route('admin.field-tracker.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.field-tracker.*') ? 'bg-cream/10 text-coral-500' : '' }}">🚗 Field Tracker</a>
                <a href="{{ route('admin.packages.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.packages.*') ? 'bg-cream/10 text-coral-500' : '' }}">💳 Packages</a>

                <div class="mt-4 mb-2 px-3 text-xs font-bold uppercase text-cream/40">PG Management</div>
                <a href="{{ route('admin.rooms.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.rooms.*') ? 'bg-cream/10 text-coral-500' : '' }}">🛏️ All Rooms</a>
                <a href="{{ route('admin.agreements.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.agreements.*') ? 'bg-cream/10 text-coral-500' : '' }}">📄 All Agreements</a>
                <a href="{{ route('admin.pg.overview') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.pg.*') ? 'bg-cream/10 text-coral-500' : '' }}">📊 PG Overview</a>
                <a href="{{ route('admin.tenants.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.tenants.*') ? 'bg-cream/10 text-coral-500' : '' }}">👥 All Tenants</a>
                <a href="{{ route('admin.rent.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.rent.*') ? 'bg-cream/10 text-coral-500' : '' }}">💰 All Rent Bills</a>
                <a href="{{ route('admin.complaints.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.complaints.*') ? 'bg-cream/10 text-coral-500' : '' }}">🛠️ All Complaints</a>
                <a href="{{ route('admin.reviews.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('admin.reviews.*') ? 'bg-cream/10 text-coral-500' : '' }}">⭐ Reviews</a>

@elseif($role === 'owner' || $role === 'pg_manager')
                <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                @if(auth()->user()->hasFeature('analytics'))
                <a href="{{ route('owner.analytics') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.analytics') ? 'bg-cream/10 text-coral-500' : '' }}">📈 Analytics</a>
                @endif
                @if(auth()->user()->hasFeature('properties'))
                <a href="{{ route('owner.properties.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.properties.*') ? 'bg-cream/10 text-coral-500' : '' }}">🏠 My Properties</a>
                @endif
                @if(auth()->user()->role === 'owner')
                <a href="{{ route('owner.properties.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5">+ Add new</a>
                @endif
               @if(auth()->user()->role === 'owner')
                <a href="{{ route('owner.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 Leads</a>
                @endif
               @if(auth()->user()->hasFeature('tenants'))
                <a href="{{ route('owner.tenants.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.tenants.*') ? 'bg-cream/10 text-coral-500' : '' }}">👥 My Tenants</a>
                @endif
                @if(auth()->user()->role === 'owner')
                <a href="{{ route('owner.pg-managers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.pg-managers.*') ? 'bg-cream/10 text-coral-500' : '' }}">👨‍💼 PG Managers</a>
                @endif

                @if(auth()->user()->hasFeature('rent'))
                <a href="{{ route('owner.rent.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.rent.*') ? 'bg-cream/10 text-coral-500' : '' }}">💰 Rent Collection</a>
                @endif
                @if(auth()->user()->role === 'owner')
<a href="{{ route('owner.rent.archive') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.rent.archive') ? 'bg-cream/10 text-coral-500' : '' }}">🗄️ Bill Archive</a>
@endif
                @if(auth()->user()->hasFeature('complaints'))
                <a href="{{ route('owner.complaints.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.complaints.*') ? 'bg-cream/10 text-coral-500' : '' }}">🛠️ Complaints</a>
                @endif
                @if(auth()->user()->hasFeature('rooms'))
                <a href="{{ route('owner.rooms.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.rooms.*') ? 'bg-cream/10 text-coral-500' : '' }}">🛏️ Rooms & Beds</a>
                @endif
                @if(auth()->user()->hasFeature('agreements'))
                <a href="{{ route('owner.agreements.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.agreements.*') ? 'bg-cream/10 text-coral-500' : '' }}">📄 Agreements</a>
                @endif
                @if(auth()->user()->hasFeature('blogs'))
             <a href="{{ route('owner.blogs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.blogs.*') ? 'bg-cream/10 text-coral-500' : '' }}">📝 My Blogs</a>
                @endif
                             @if(auth()->user()->role === 'owner')
                <a href="{{ route('owner.wallet') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.wallet') ? 'bg-cream/10 text-coral-500' : '' }}">💰 Wallet</a>
                <a href="{{ route('owner.packages') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.packages') || request()->routeIs('owner.checkout') ? 'bg-cream/10 text-coral-500' : '' }}">💳 Buy Credits</a>
                <a href="{{ route('owner.invoices.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.invoices.*') ? 'bg-cream/10 text-coral-500' : '' }}">🧾 My Invoices</a>
                <a href="{{ route('owner.billing.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.billing.*') ? 'bg-cream/10 text-coral-500' : '' }}">🏢 Billing & GST Info</a>
                <a href="{{ route('owner.reviews.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.reviews.*') ? 'bg-cream/10 text-coral-500' : '' }}">⭐ Reviews</a>
                @endif

                @if(auth()->user()->hasFeature('leads') && auth()->user()->role === 'pg_manager')
                <a href="{{ route('owner.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('owner.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 Leads</a>
                @endif

            @elseif($role === 'telecaller')
                <a href="{{ route('telecaller.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('telecaller.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('telecaller.leads.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('telecaller.leads.*') ? 'bg-cream/10 text-coral-500' : '' }}">🎯 My Leads</a>
                <a href="{{ route('leads.manual.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('leads.manual.*') ? 'bg-cream/10 text-coral-500' : '' }}">+ Add Lead</a>
                <a href="{{ route('telecaller.owner-prospects.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('telecaller.owner-prospects.*') ? 'bg-cream/10 text-coral-500' : '' }}">🏠 Owner Prospects</a>

            @elseif($role === 'field_executive')
                <a href="{{ route('field.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('field.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 Dashboard</a>
                <a href="{{ route('field.visits.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('field.visits.*') ? 'bg-cream/10 text-coral-500' : '' }}">📅 My Visits</a>
                <a href="{{ route('field.visits.index', ['status' => 'scheduled']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5">⏳ Pending</a>
                <a href="{{ route('field.visits.index', ['status' => 'completed']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5">✅ Completed</a>

            @elseif($role === 'seo_manager')
                <a href="{{ route('seo.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('seo.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}">📊 SEO Dashboard</a>
                <a href="{{ route('seo.settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('seo.settings.*') ? 'bg-cream/10 text-coral-500' : '' }}">🔍 SEO Pages</a>
                <a href="{{ route('seo.blogs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('seo.blogs.*') ? 'bg-cream/10 text-coral-500' : '' }}">📝 Blogs</a>
            @endif
            
              <a href="{{ route('account.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 mt-4 {{ request()->routeIs('account.*') ? 'bg-cream/10 text-coral-500' : '' }}">
                👤 My Profile
            </a>

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

        <div class="p-4 border-t border-cream/10 flex-shrink-0">
            <div class="px-3 text-xs text-cream/50">Signed in as</div>
            <div class="px-3 text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
                <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-cream/5">Logout</button>
            </form>
        </div>
    </aside>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="flex-1 min-w-0">

        {{-- MOBILE TOP BAR --}}
        <header class="md:hidden bg-white border-b border-ink-900/10 px-4 py-3 flex items-center justify-between sticky top-0 z-30">
            <button onclick="openSidebar()" class="p-2 hover:bg-ink-900/5 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Pizi" class="h-7 w-auto">
            </div>
            <a href="{{ route('notifications.index') }}" class="relative p-2 hover:bg-ink-900/5 rounded-lg">
                <span class="text-xl">🔔</span>
                @if($unread > 0)
                    <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-coral-500 text-white text-xs font-bold flex items-center justify-center">{{ $unread > 99 ? '99+' : $unread }}</span>
                @endif
            </a>
        </header>

        {{-- DESKTOP TOP HEADER --}}
        <header class="hidden md:flex bg-white border-b border-ink-900/10 px-6 py-3 items-center justify-end gap-4">
            <div id="notifBell" class="relative">
                <button onclick="toggleNotifPanel()" class="relative p-2 hover:bg-ink-900/5 rounded-lg">
                    <span class="text-2xl">🔔</span>
                    <span id="notifCount" class="hidden absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-coral-500 text-white text-xs font-bold flex items-center justify-center">0</span>
                </button>
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
            <div class="m-4 md:m-6 bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="m-4 md:m-6 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="p-4 md:p-6 lg:p-10">
            @yield('content')
        </div>
    </div>
</div>

@stack('scripts')

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarBackdrop').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.add('hidden');
    document.body.style.overflow = '';
}

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

        const countEl = document.getElementById('notifCount');
        if (data.unread_count > 0) {
            countEl.classList.remove('hidden');
            countEl.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
        } else {
            countEl.classList.add('hidden');
        }

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

loadNotifications();
setInterval(loadNotifications, 30000);
</script>
</body>
</html>