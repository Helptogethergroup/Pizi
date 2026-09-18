@extends('layouts.app')
@section('title', 'Login — PGFind')
@section('content')

@php
    $verifiedPgCount = \Illuminate\Support\Facades\Cache::remember('login_stat_verified_pgs', 3600, fn () =>
        \Illuminate\Support\Facades\DB::table('properties')->where('is_active', 1)->where('is_verified', 1)->whereNull('deleted_at')->count()
    );
    $cityCount = \Illuminate\Support\Facades\Cache::remember('login_stat_city_count', 3600, fn () =>
        \Illuminate\Support\Facades\DB::table('properties')->where('is_active', 1)->whereNull('deleted_at')->distinct('city_id')->count('city_id')
    );
@endphp

<style>
    @keyframes loginFadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes loginBlobFloat { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(20px,-25px) scale(1.08); } }
    @keyframes loginBlobFloat2 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(-25px,20px) scale(1.05); } }
    @keyframes loginChipFloat { 0%,100% { transform: translateY(0) rotate(var(--r,0deg)); } 50% { transform: translateY(-10px) rotate(var(--r,0deg)); } }
    .login-fade-1 { animation: loginFadeUp .6s ease-out both; }
    .login-fade-2 { animation: loginFadeUp .6s ease-out .12s both; }
    .login-chip { animation: loginChipFloat 5s ease-in-out infinite; }
    .login-brand-bg {
        background-image:
            radial-gradient(rgba(255,255,255,0.25) 1px, transparent 1px),
            linear-gradient(135deg, #ff8c7e 0%, #ff6b5b 45%, #c93b2c 100%);
        background-size: 22px 22px, auto;
    }
    .login-glow-ring:focus-within { box-shadow: 0 0 0 4px rgba(255,107,91,0.15); }
    .login-page-bg {
        background: radial-gradient(1200px 600px at 15% 10%, #ffe3df 0%, transparent 55%),
                    radial-gradient(1000px 700px at 90% 90%, #ffd8d2 0%, transparent 55%),
                    #fefcf6;
    }
    .login-card { transition: box-shadow .4s ease, transform .4s ease; }
    .login-card:hover { box-shadow: 0 30px 60px -15px rgba(201,59,44,0.25); transform: translateY(-2px); }
    .login-shimmer-btn { position: relative; overflow: hidden; }
    .login-shimmer-btn::after {
        content: ''; position: absolute; top: 0; left: -60%; width: 40%; height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255,255,255,0.35), transparent);
        transform: skewX(-20deg);
    }
    .login-shimmer-btn:hover::after { animation: loginShimmer 1s ease; }
    @keyframes loginShimmer { from { left: -60%; } to { left: 130%; } }
    @media (prefers-reduced-motion: reduce) {
        .login-fade-1,.login-fade-2,.login-blob-a,.login-blob-b,.login-chip { animation: none; }
        .login-card:hover { transform: none; }
    }
    /* This page only (adjacent-sibling selector) — the footer's global
       mt-24 leaves an oversized flat gap after this page's short,
       vertically-centered content. Every other page keeps mt-24. */
    main:has(.login-page-bg) + footer { margin-top: 1.5rem !important; }

    /* Book-opening entrance — the card starts as a closed spine and both
       halves swing open like covers. Desktop-only: on mobile the two
       halves stack vertically, so a left/right swing doesn't read as a
       book opening — that layout just keeps the plain fade-in instead. */
    @media (min-width: 1024px) {
        .login-card { perspective: 2200px; }
        .login-book-left, .login-book-right {
            backface-visibility: hidden;
            animation-fill-mode: both;
            animation-timing-function: cubic-bezier(.22,1,.36,1);
            animation-duration: 1.1s;
        }
        .login-book-left { transform-origin: right center; animation-name: loginBookOpenLeft; position: relative; }
        .login-book-right { transform-origin: left center; animation-name: loginBookOpenRight; animation-delay: .08s; position: relative; }
        /* The right half is white-on-white — the rotation itself barely
           reads without a shadow to sell the depth. A cast shadow that
           fades out as each half settles flat makes both sides equally
           visible, not just the coral (high-contrast) left half. */
        @keyframes loginBookOpenLeft {
            from { transform: rotateY(-108deg); opacity: 0; box-shadow: 40px 0 50px -15px rgba(0,0,0,0.35); }
            to   { transform: rotateY(0deg);    opacity: 1; box-shadow: 0 0 0 rgba(0,0,0,0); }
        }
        @keyframes loginBookOpenRight {
            from { transform: rotateY(108deg);  opacity: 0; box-shadow: -40px 0 50px -15px rgba(0,0,0,0.35); }
            to   { transform: rotateY(0deg);    opacity: 1; box-shadow: 0 0 0 rgba(0,0,0,0); }
        }
        .login-fade-1 { animation-delay: .55s; }
        .login-fade-2 { animation-delay: .65s; }
    }
    @media (prefers-reduced-motion: reduce) {
        .login-book-left, .login-book-right { animation: none; }
    }
</style>

<section class="login-page-bg min-h-[85vh] py-8 lg:py-14 px-4 flex items-center justify-center">
    <div class="login-card w-full max-w-5xl bg-white rounded-[2rem] shadow-2xl shadow-coral-900/10 overflow-hidden flex flex-col lg:flex-row">

        {{-- Mobile-only compact hero strip --}}
        <div class="lg:hidden relative overflow-hidden px-6 pt-8 pb-10 text-center login-brand-bg">
            <div class="absolute -top-10 -left-10 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>
            <img src="{{ asset('assets/images/logo.png') }}" alt="Pizi" class="w-12 h-12 rounded-xl mx-auto relative z-10">
            <h1 class="font-display font-black text-2xl text-white mt-3 relative z-10">Find your PG that feels like home.</h1>
            <div class="flex items-center justify-center gap-2 mt-4 relative z-10">
                <span class="bg-white/20 text-white text-xs font-bold px-3 py-1.5 rounded-full">{{ $verifiedPgCount }}+ Verified PGs</span>
                <span class="bg-white/20 text-white text-xs font-bold px-3 py-1.5 rounded-full">{{ $cityCount }}+ Cities</span>
            </div>
        </div>

        {{-- Left brand panel — desktop only --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center p-12 login-brand-bg login-book-left">
            <div class="absolute -top-24 -left-16 w-96 h-96 rounded-full bg-white/10 blur-3xl login-blob-a"></div>
            <div class="absolute -bottom-24 -right-10 w-80 h-80 rounded-full bg-ink-900/10 blur-3xl login-blob-b"></div>

            <div class="hidden xl:flex items-center gap-2 absolute top-8 right-8 bg-white/15 border border-white/25 backdrop-blur-sm rounded-2xl px-4 py-2.5 text-white text-sm font-semibold login-chip shadow-lg" style="--r:-4deg">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="17" cy="14.2" r="1.1" fill="currentColor" stroke="none"/></svg>
                Zero Brokerage
            </div>
            <div class="hidden xl:flex items-center gap-2 absolute bottom-8 right-8 bg-white/15 border border-white/25 backdrop-blur-sm rounded-2xl px-4 py-2.5 text-white text-sm font-semibold login-chip shadow-lg" style="--r:3deg; animation-delay:1.2s">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5.5c0 4.6-3 8.1-7 9.5-4-1.4-7-4.9-7-9.5V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
                100% Verified
            </div>

            <div class="relative z-10 max-w-md login-fade-1">
                <div class="flex items-center gap-2 mb-10">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Pizi" class="w-10 h-10 rounded-xl">
                    <span class="font-display font-black text-2xl text-white">Pizi</span>
                </div>

                <h1 class="font-display font-black text-4xl leading-[1.1] text-white">
                    Find your PG that <span class="italic text-ink-900">feels like home.</span>
                </h1>
                <p class="text-white/80 mt-5 text-base leading-relaxed">
                    Manage your leads, tenants and bookings from one dashboard.
                </p>

                <ul class="mt-6 space-y-2.5">
                    @foreach(['Verified, zero-brokerage listings', 'Real-time lead notifications', 'Track tenants & rent in one place'] as $feature)
                        <li class="flex items-center gap-2.5 text-white/90 text-sm font-medium">
                            <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>

                <div class="grid grid-cols-2 gap-4 mt-8">
                    <div class="bg-white/10 border border-white/20 rounded-2xl p-4 backdrop-blur-sm hover:bg-white/20 transition">
                        <div class="font-display font-black text-3xl text-white"><span class="login-countup" data-target="{{ $verifiedPgCount }}">0</span>+</div>
                        <div class="text-white/70 text-xs uppercase font-semibold tracking-wide mt-1">Verified PGs</div>
                    </div>
                    <div class="bg-white/10 border border-white/20 rounded-2xl p-4 backdrop-blur-sm hover:bg-white/20 transition">
                        <div class="font-display font-black text-3xl text-white"><span class="login-countup" data-target="{{ $cityCount }}">0</span>+</div>
                        <div class="text-white/70 text-xs uppercase font-semibold tracking-wide mt-1">Cities Covered</div>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-8">
                    <div class="flex -space-x-2">
                        @foreach(['bg-white text-coral-600','bg-amber-300 text-amber-800','bg-emerald-300 text-emerald-800','bg-sky-300 text-sky-800'] as $c)
                            <div class="w-8 h-8 rounded-full {{ $c }} border-2 border-coral-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg>
                            </div>
                        @endforeach
                    </div>
                    <span class="text-white/80 text-sm">Trusted by owners &amp; tenants across Delhi NCR</span>
                </div>
            </div>
        </div>

        {{-- Right form panel --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-10 lg:p-14 login-book-right">
            <div class="w-full max-w-sm login-fade-2">
                <span class="inline-flex items-center gap-1.5 bg-coral-50 text-coral-600 text-xs font-bold px-3 py-1.5 rounded-full">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    Secure Login
                </span>
                <h1 class="font-display font-black text-3xl mt-4">Welcome back.</h1>
                <p class="text-ink-900/60 mt-2 text-sm">Login to manage your listings or leads.</p>

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4" id="loginForm">
                    @csrf
                    <div class="relative login-glow-ring rounded-xl">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ink-900/35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                        <input name="email" type="email" required value="{{ old('email', session('google_prefill.email')) }}" placeholder="Email"
                               class="w-full pl-11 pr-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 transition">
                    </div>
                    <div class="relative login-glow-ring rounded-xl">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ink-900/35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input id="loginPasswordInput" name="password" type="password" required placeholder="Password"
                               class="w-full pl-11 pr-11 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 transition">
                        <button type="button" id="togglePasswordBtn" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-ink-900/40 hover:text-ink-900/70">
                            <svg id="eyeIcon" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" class="rounded border-ink-900/20 text-coral-500">
                        Remember me
                    </label>
                    <button type="submit" id="loginSubmitBtn" class="login-shimmer-btn w-full py-3 bg-gradient-to-r from-coral-500 to-coral-600 text-white rounded-xl font-bold hover:from-coral-600 hover:to-coral-700 hover:scale-[1.01] active:scale-[0.99] transition-all shadow-lg shadow-coral-500/30 flex items-center justify-center gap-2">
                        <svg id="loginSpinner" class="hidden w-[18px] h-[18px] animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-90" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <span id="loginBtnText">Login</span>
                    </button>
                    <div class="mt-4 text-center">
                        <a href="{{ route('otp.login') }}" class="inline-flex items-center gap-2 px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm w-full justify-center transition">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                            Login with OTP instead
                        </a>
                    </div>
                </form>

                <div class="flex items-center gap-3 my-5">
                    <div class="flex-1 h-px bg-ink-900/10"></div>
                    <span class="text-xs text-ink-900/40 font-semibold uppercase">or</span>
                    <div class="flex-1 h-px bg-ink-900/10"></div>
                </div>

                <a href="{{ route('google.redirect') }}" class="w-full flex items-center justify-center gap-3 px-5 py-3 border border-ink-900/15 rounded-xl font-bold text-sm text-ink-900 hover:bg-ink-900/5 transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.48a5.55 5.55 0 0 1-2.4 3.64v3h3.88c2.27-2.09 3.56-5.17 3.56-8.83z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.96-2.9l-3.88-3c-1.08.72-2.45 1.15-4.08 1.15-3.13 0-5.79-2.12-6.74-4.96H1.26v3.09A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.26 14.29A7.2 7.2 0 0 1 4.88 12c0-.79.14-1.56.38-2.29V6.62H1.26A12 12 0 0 0 0 12c0 1.94.46 3.77 1.26 5.38z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.26 6.62l4 3.09C6.21 6.87 8.87 4.75 12 4.75z"/></svg>
                    Autofill with Google
                </a>
                <div class="mt-4 text-center">
                    <a href="{{ route('password.request') }}" class="inline-flex items-center gap-1.5 text-sm text-coral-500 hover:text-coral-600 font-semibold">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 3l3 3M14 6l2 2"/></svg>
                        Forgot Password?
                    </a>
                </div>

                <p class="text-center text-sm text-ink-900/60 mt-6">
                    New here? <a href="{{ route('register') }}" class="text-coral-600 font-semibold hover:underline">Register</a>
                </p>
            </div>
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('.login-countup').forEach(el => {
        const target = parseInt(el.dataset.target, 10) || 0;
        const duration = 1200;
        const start = performance.now();
        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            el.textContent = Math.floor(progress * target);
            if (progress < 1) requestAnimationFrame(tick);
            else el.textContent = target;
        }
        requestAnimationFrame(tick);
    });

    // Password show/hide
    const pwInput = document.getElementById('loginPasswordInput');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeOpenPath = '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>';
    const eyeOffPath = '<path d="M3 3l18 18"/><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 11 7 11 7a17.6 17.6 0 0 1-3.1 3.8M6.5 6.6C3.7 8.4 2 12 2 12s4 7 11 7c1.4 0 2.7-.3 3.9-.7"/><path d="M14.1 14.1A3 3 0 0 1 9.9 9.9"/>';
    document.getElementById('togglePasswordBtn')?.addEventListener('click', () => {
        const isPw = pwInput.type === 'password';
        pwInput.type = isPw ? 'text' : 'password';
        eyeIcon.innerHTML = isPw ? eyeOffPath : eyeOpenPath;
    });

    // Loading state on submit — disables the button and swaps in a spinner
    // so a slow request doesn't invite a double-click.
    document.getElementById('loginForm')?.addEventListener('submit', () => {
        const btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        document.getElementById('loginSpinner').classList.remove('hidden');
        document.getElementById('loginBtnText').textContent = 'Logging in…';
    });
</script>
@endsection
