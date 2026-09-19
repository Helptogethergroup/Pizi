<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tenant Portal') Pizi</title>
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
        @media (max-width: 767px) {
            #sidebar {
                position: fixed; top: 0; left: 0; bottom: 0; z-index: 50;
                transform: translateX(-100%); transition: transform 0.3s ease;
            }
            #sidebar.open { transform: translateX(0); }
        }
        @media (min-width: 768px) {
            #sidebar { position: relative; transform: none !important; display: flex; }
        }
    </style>
@include('partials.emoji-icons')
</head>
<body class="text-ink-950">

<div class="min-h-screen flex">

    <div id="sidebarBackdrop" onclick="closeSidebar()" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden"></div>

    <aside id="sidebar" class="w-64 bg-ink-950 text-cream flex-shrink-0 flex-col h-screen md:h-auto overflow-y-auto">
        
        <div class="px-6 py-5 flex items-center justify-between border-b border-cream/10">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-coral-500 flex items-center justify-center font-display font-black text-cream">P</div>
                <span class="font-display font-bold text-lg">Pizi Tenant</span>
            </a>
            <button onclick="closeSidebar()" class="md:hidden p-1 text-cream/70 hover:text-cream">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

<nav class="flex-1 p-4 space-y-1 text-sm overflow-y-auto">
    @php
        $journeyComplete = auth()->user()->journey_stage >= 4;
    @endphp

    <a href="{{ route('tenant.onboarding') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.onboarding') ? 'bg-cream/10 text-coral-500' : '' }}">
        <i class="fa-solid fa-clipboard-list fa-fw"></i> Verification Steps   
        @if(!$journeyComplete)
            <span class="ml-auto text-xs bg-coral-500 text-white px-2 py-0.5 rounded-full font-bold">{{ auth()->user()->journey_stage ?? 0 }}/4</span>
        @endif
    </a>

    @if($journeyComplete)
     <a href="{{ route('tenant.onboarding', ['view' => 1]) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.onboarding') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-clipboard-list fa-fw"></i> Verification Steps</a>
         <a href="{{ route('tenant.profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.profile') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-user fa-fw"></i> My Profile</a>
        <a href="{{ route('tenant.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.dashboard') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-house fa-fw"></i> Dashboard</a>
        <a href="{{ route('tenant.kyc') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.kyc') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-file-lines fa-fw"></i> Upload KYC</a>
     
      
        <a href="{{ route('tenant.room') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.room') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-bed fa-fw"></i> My Room</a>
        <a href="{{ route('tenant.rent.history') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.rent.*') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-sack-dollar fa-fw"></i> Rent History</a>
        <a href="{{ route('tenant.complaints.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.complaints.*') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-screwdriver-wrench fa-fw"></i> Complaints</a>
        <a href="{{ route('tenant.agreement') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.agreement') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-file-lines fa-fw"></i> Agreement</a>
        <a href="{{ route('tenant.notice') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 {{ request()->routeIs('tenant.notice') ? 'bg-cream/10 text-coral-500' : '' }}"><i class="fa-solid fa-arrow-up-from-bracket fa-fw"></i> Notice Period</a>
    @endif



    <div class="pt-6 mt-6 border-t border-cream/10">
        <a href="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-cream/5 text-cream/70">← View Pizi.in</a>
    </div>
</nav>

        <div class="p-4 border-t border-cream/10">
            <div class="px-3 text-xs text-cream/50">Logged in as</div>
            <div class="px-3 text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
                <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-cream/5">Logout</button>
            </form>
        </div>
    </aside>

    <div class="flex-1 min-w-0">

        <header class="md:hidden bg-white border-b border-ink-900/10 px-4 py-3 flex items-center justify-between sticky top-0 z-30">
            <button onclick="openSidebar()" class="p-2 hover:bg-ink-900/5 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-coral-500 flex items-center justify-center font-display font-black text-cream text-sm">P</div>
                <span class="font-display font-bold text-base">Pizi Tenant</span>
            </div>
            <div class="w-10"></div>
        </header>

        <header class="hidden md:flex bg-white border-b border-ink-900/10 px-6 py-3 items-center justify-end gap-4">
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
</script>
</body>
</html>