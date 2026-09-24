@extends('layouts.app')
@section('title', 'List Your PG on Pizi — Fill Every Bed with Genuine Tenants')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,900&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap');

    .pz-serif { font-family: 'Fraunces', serif; }
    .pz-mono { font-family: 'IBM Plex Mono', monospace; }

    .pz-hero { background: radial-gradient(ellipse 900px 500px at 50% -10%, rgba(255,101,81,0.14), transparent), #FBF3EA; }

    /* keytag shape: rounded rect with a small circular notch at top-left, like a keyring tag */
    .pz-keytag { position: relative; }
    .pz-keytag::before {
        content: '';
        position: absolute; top: 18px; left: 18px;
        width: 10px; height: 10px; border-radius: 50%;
        border: 2px solid currentColor; opacity: .35;
    }

    /* door + key signature in hero */
    .pz-door-wrap { filter: drop-shadow(0 18px 30px rgba(22,35,59,0.18)); }
    .pz-key { transform-origin: 50% 50%; }

    @media (prefers-reduced-motion: no-preference) {
        .pz-door-idle { animation: pzDoorSway 6s ease-in-out infinite; transform-origin: 4px 50%; }
        @keyframes pzDoorSway { 0%,100% { transform: rotateY(0deg);} 50% { transform: rotateY(-4deg);} }

        .pz-key-slide { animation: pzKeySlide 2.4s ease-in-out .4s 1 both; }
        @keyframes pzKeySlide {
            0% { transform: translateX(-46px) rotate(-18deg); opacity: 0; }
            55% { transform: translateX(0) rotate(0deg); opacity: 1; }
            70% { transform: translateX(0) rotate(28deg); }
            100% { transform: translateX(0) rotate(0deg); }
        }

        .pz-reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .6s ease; }
        .pz-reveal.pz-in { opacity: 1; transform: translateY(0); }

        .pz-glow-pulse.pz-in { animation: pzGlow 1.1s ease-out .1s 1; }
        @keyframes pzGlow {
            0% { box-shadow: 0 0 0 0 rgba(255,101,81,0.0); }
            30% { box-shadow: 0 0 0 8px rgba(255,101,81,0.12); }
            100% { box-shadow: 0 0 0 0 rgba(255,101,81,0.0); }
        }

        .pz-shimmer { position: relative; overflow: hidden; }
        .pz-shimmer::after {
            content: ''; position: absolute; top: 0; left: -60%; width: 40%; height: 100%;
            background: linear-gradient(75deg, transparent, rgba(255,255,255,0.35), transparent);
            transform: skewX(-15deg);
        }
        .pz-shimmer:hover::after { animation: pzShimmer 1s ease; }
        @keyframes pzShimmer { from { left: -60%; } to { left: 130%; } }
    }
    @media (prefers-reduced-motion: reduce) {
        .pz-reveal { opacity: 1; transform: none; }
    }

    .pz-timeline-line { background: repeating-linear-gradient(to bottom, #E4D9C8 0 8px, transparent 8px 16px); }

    details.pz-faq > summary { cursor: pointer; list-style: none; }
    details.pz-faq > summary::-webkit-details-marker { display: none; }
details.pz-faq[open] .pz-faq-icon { transform: rotate(45deg); }
    .pz-faq-icon { transition: transform .25s ease; }

    /* ── 2-Step Modal ── */
    #regModal {
        position: fixed; inset: 0; z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        background: rgba(22,35,59,0.6);
        backdrop-filter: blur(4px);
        opacity: 0; pointer-events: none;
        transition: opacity .3s ease;
    }
    #regModal.open { opacity: 1; pointer-events: all; }
    #regModal .modal-box {
        background: #fff; border-radius: 20px;
        width: 95%; max-width: 860px;
        max-height: 92vh; overflow-y: auto;
        position: relative;
        transform: translateY(28px);
        transition: transform .35s cubic-bezier(.22,.61,.36,1);
        box-shadow: 0 24px 60px rgba(22,35,59,0.25);
    }
    #regModal.open .modal-box { transform: translateY(0); }
    .modal-close-btn {
        position: absolute; top: 14px; right: 16px;
        background: none; border: none; cursor: pointer;
        font-size: 22px; color: #8A8071; line-height: 1; z-index: 10;
    }
    .modal-close-btn:hover { color: #16233B; }
    /* Step tabs */
    .modal-step { display: none; }
    .modal-step.active { display: block; }

</style>

<!-- ============ 2-STEP REGISTRATION MODAL ============ -->
<div id="regModal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeModal()" aria-label="Close">✕</button>

        <!-- Step indicator -->
        <div class="flex border-b border-[#EBE2D3]">
            <div id="mTab1" class="flex-1 text-center py-4 text-sm font-semibold text-[#FF6551] border-b-2 border-[#FF6551] cursor-pointer">1. Select Plan</div>
            <div id="mTab2" class="flex-1 text-center py-4 text-sm font-semibold text-[#8A8071] cursor-default">2. Create Account</div>
        </div>

        <!-- STEP 1: Plan Selection -->
        <div id="mStep1" class="modal-step active p-6 md:p-8">
            <div class="mb-6">
                <h2 class="pz-serif text-2xl font-bold text-[#16233B] mb-1">Choose a plan to get started</h2>
                <p class="text-sm text-[#8A8071]">Select a credit pack, or start for free with no payment required.</p>
            </div>

            <a href="/register-free" class="flex items-center justify-between gap-4 bg-[#EFFAF4] border-2 border-[#2E9E6E] rounded-2xl px-5 py-4 hover:bg-[#E3F5EB] transition group mb-5">
                <div>
                    <p class="font-bold text-[#16233B]">Start free — no credits needed</p>
                    <p class="text-xs text-[#2E9E6E]">List your PG in minutes, no payment required.</p>
                </div>
                <span class="pz-mono text-sm font-bold text-white bg-[#2E9E6E] px-4 py-2 rounded-lg whitespace-nowrap group-hover:bg-[#248059] transition">Start free →</span>
            </a>

            <div class="space-y-3">
                @if($packages && $packages->count())
                    @foreach($packages as $pkg)
                        <div class="m-package-card p-5 rounded-2xl border-2 border-[#EBE2D3] hover:border-[#FF6551] hover:bg-[#FFF6F4] transition cursor-pointer text-[#16233B]"
                             onclick="mSelectPackage(this, {{ $pkg->id }}, '{{ $pkg->name }}', {{ $pkg->price_inr }}, {{ $pkg->total_credits }})">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold">{{ $pkg->name }}</h3>
                                    <p class="pz-mono text-xs text-[#5C5344] mt-1">{{ $pkg->total_credits }} credits</p>
                                    @if($pkg->description)
                                        <p class="text-sm text-[#8A8071] mt-1">{{ $pkg->description }}</p>
                                    @endif
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="pz-mono text-2xl font-bold text-[#FF6551]">₹{{ number_format($pkg->price_inr) }}</div>
                                    @if($pkg->is_popular)
                                        <span class="inline-block mt-1 bg-[#E3A130] text-white text-xs px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-star fa-fw" style="color:#f59e0b"></i> Popular</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-[#5C5344]">No packages available.</p>
                @endif
            </div>

            <button id="mNextBtn" onclick="goToStep2()" disabled
                class="w-full mt-6 bg-[#FF6551] hover:bg-[#D9432E] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl transition">
                Continue with selected plan →
            </button>
        </div>

        <!-- STEP 2: Registration Form -->
        <div id="mStep2" class="modal-step p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left: Selected plan summary -->
                <div>
                    <button onclick="goToStep1()" class="text-sm text-[#8A8071] hover:text-[#16233B] mb-4 flex items-center gap-1">← Back to plans</button>
                    <div id="mSelectedSummary" class="bg-[#EFFAF4] border border-[#BEE8D2] rounded-2xl p-5 mb-5">
                        <p class="text-xs text-[#2E9E6E] font-semibold uppercase tracking-wide mb-1">Selected plan</p>
                        <p id="mPkgName" class="font-bold text-xl text-[#16233B]"></p>
                        <p id="mPkgPrice" class="pz-mono text-[#FF6551] font-bold text-2xl"></p>
                        <p id="mPkgCredits" class="text-sm text-[#5C5344] mt-1"></p>
                    </div>
                    <div class="bg-[#16233B] rounded-2xl p-5 text-sm text-[#B9C2D4]">
                        <h3 class="font-bold text-white mb-3">Why Pizi?</h3>
                        <div class="space-y-2">
                            <p><i class="fa-solid fa-rocket fa-fw"></i> <span class="text-white font-semibold">Instant visibility</span> — leads within hours</p>
                            <p><i class="fa-solid fa-sack-dollar fa-fw"></i> <span class="text-white font-semibold">Zero commission</span> — no hidden charges</p>
                            <p><i class="fa-solid fa-mobile-screen fa-fw"></i> <span class="text-white font-semibold">Direct contact</span> — real phone numbers</p>
                            <p><i class="fa-solid fa-circle-check fa-fw"></i> <span class="text-white font-semibold">Verified tenants</span> — no spam profiles</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Form -->
                <div>
                    <h2 class="text-xl font-bold text-[#16233B] mb-1">Create your account</h2>
                    <p class="text-sm text-[#8A8071] mb-5">Looking for a PG? <a href="{{ route('search') }}" class="text-[#D9432E] font-semibold">Browse PGs →</a></p>

                    <a href="{{ route('google.redirect') }}" class="w-full flex items-center justify-center gap-3 px-5 py-3 border border-[#DCD2BF] rounded-xl font-bold text-sm text-[#16233B] hover:bg-black/5 transition mb-4">
                        <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.48a5.55 5.55 0 0 1-2.4 3.64v3h3.88c2.27-2.09 3.56-5.17 3.56-8.83z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.96-2.9l-3.88-3c-1.08.72-2.45 1.15-4.08 1.15-3.13 0-5.79-2.12-6.74-4.96H1.26v3.09A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.26 14.29A7.2 7.2 0 0 1 4.88 12c0-.79.14-1.56.38-2.29V6.62H1.26A12 12 0 0 0 0 12c0 1.94.46 3.77 1.26 5.38z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.26 6.62l4 3.09C6.21 6.87 8.87 4.75 12 4.75z"/></svg>
                        Autofill with Google
                    </a>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-1 h-px bg-[#DCD2BF]"></div>
                        <span class="text-xs text-[#8A8071] font-semibold uppercase">or fill manually</span>
                        <div class="flex-1 h-px bg-[#DCD2BF]"></div>
                    </div>

                    <form id="mRegistrationForm" class="space-y-3">
                        @csrf
                        <input type="hidden" id="mSelectedPackageId" name="package_id">

                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-1.5">Full Name *</label>
                            <input type="text" name="name" required placeholder="Your full name" value="{{ session('google_prefill.name') }}"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-1.5">Email *</label>
                            <input type="email" name="email" required placeholder="your@email.com" value="{{ session('google_prefill.email') }}"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-1.5">Phone (10-digit) *</label>
                            <div class="flex gap-2">
                                <input type="tel" name="phone" id="mOwnerPhoneInput" required maxlength="10" placeholder="9876543210" pattern="[0-9]{10}"
                                    class="flex-1 px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] text-sm">
                                <button type="button" id="mOwnerSendOtpBtn" onclick="mOwnerSendPhoneOtp()"
                                    class="px-4 py-2 bg-[#16233B] hover:bg-black text-white rounded-xl font-semibold text-sm whitespace-nowrap">Verify</button>
                            </div>
                            <p id="mOwnerPhoneStatus" class="text-xs text-[#8A8071] mt-1"></p>
                            <div id="mOwnerOtpBox" class="hidden mt-3 p-4 bg-[#FBF3EA] rounded-xl border border-[#DCD2BF]">
                                <label class="block text-xs font-bold uppercase text-[#8A8071] mb-2">Enter 6-digit OTP</label>
                                <div class="flex gap-2">
                                    <input type="text" id="mOwnerOtpInput" maxlength="6" placeholder="••••••"
                                        class="flex-1 px-4 py-2.5 border border-[#DCD2BF] rounded-xl tracking-widest text-center text-sm">
                                    <button type="button" onclick="mOwnerVerifyPhoneOtp()"
                                        class="px-4 py-2 bg-[#FF6551] hover:bg-[#D9432E] text-white rounded-xl font-semibold text-sm">Confirm</button>
                                </div>
                                <p id="mOwnerOtpStatus" class="text-xs mt-2"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-1.5">Password (min 6) *</label>
                            <input type="password" name="password" required minlength="6" placeholder="••••••••"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-1.5">Confirm Password *</label>
                            <input type="password" name="password_confirmation" required placeholder="••••••••"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] text-sm">
                        </div>

                        <button type="button" onclick="mBuyPackage()" id="mBuyButton"
                            class="pz-shimmer w-full bg-[#FF6551] hover:bg-[#D9432E] text-white font-bold py-3.5 rounded-xl transition mt-2">
                            <i class="fa-solid fa-credit-card fa-fw"></i> Create Account &amp; Buy Credits
                        </button>
                    </form>
                    <p class="text-center text-sm text-[#8A8071] mt-4">Already registered? <a href="/login" class="text-[#D9432E] font-semibold">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ HERO ============ -->
<style>
    .pz-hero-dark {
        background: linear-gradient(135deg, #FBF3EA 0%, #FFF0E8 40%, #FBF3EA 100%);
        position: relative;
        overflow: hidden;
        width: 100%;
    }
    .pz-hero-dark::before {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(ellipse 60% 80% at 85% 50%, rgba(255,101,81,0.13), transparent),
            radial-gradient(ellipse 40% 60% at 5% 70%, rgba(255,101,81,0.08), transparent);
        pointer-events: none;
        animation: pzBgPulse 8s ease-in-out infinite;
    }
    @keyframes pzBgPulse {
        0%,100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    /* Floating orbs */
    .pz-orb {
        position: absolute; border-radius: 50%;
        background: rgba(255,101,81,0.06);
        animation: pzFloat 8s ease-in-out infinite;
    }
    .pz-orb:nth-child(2) { animation-delay: -3s; animation-duration: 11s; }
    .pz-orb:nth-child(3) { animation-delay: -6s; animation-duration: 9s; }
    @keyframes pzFloat {
        0%,100% { transform: translateY(0px) scale(1); }
        50% { transform: translateY(-20px) scale(1.04); }
    }
    /* Stat cards */
    .pz-stat-card {
        background: #fff;
        border: 1px solid #EBE2D3;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        animation: pzStatIn .6s ease both;
    }
    .pz-stat-card:nth-child(1) { animation-delay: .1s; }
    .pz-stat-card:nth-child(2) { animation-delay: .2s; }
    .pz-stat-card:nth-child(3) { animation-delay: .3s; }
    .pz-stat-card:nth-child(4) { animation-delay: .4s; }
    @keyframes pzStatIn {
        from { opacity:0; transform: translateY(16px); }
        to   { opacity:1; transform: translateY(0); }
    }
    /* Door in dark bg */
    @media (prefers-reduced-motion: no-preference) {
        .pz-door-idle { animation: pzDoorSway 6s ease-in-out infinite; transform-origin: 4px 50%; }
        @keyframes pzDoorSway { 0%,100% { transform: rotateY(0deg);} 50% { transform: rotateY(-4deg);} }
        .pz-key-slide { animation: pzKeySlide 2.4s ease-in-out .4s 1 both; }
        @keyframes pzKeySlide {
            0% { transform: translateX(-46px) rotate(-18deg); opacity: 0; }
            55% { transform: translateX(0) rotate(0deg); opacity: 1; }
            70% { transform: translateX(0) rotate(28deg); }
            100% { transform: translateX(0) rotate(0deg); }
        }
    }
    /* Hero text fade in */
    .pz-hero-text { animation: pzHeroIn .8s ease both; }
    .pz-hero-text:nth-child(1) { animation-delay: .05s; }
    .pz-hero-text:nth-child(2) { animation-delay: .15s; }
    .pz-hero-text:nth-child(3) { animation-delay: .25s; }
    .pz-hero-text:nth-child(4) { animation-delay: .35s; }
    .pz-hero-text:nth-child(5) { animation-delay: .45s; }
    @keyframes pzHeroIn {
        from { opacity:0; transform: translateY(14px); }
        to   { opacity:1; transform: translateY(0); }
    }
</style>

<div class="pz-hero-dark border-b border-[#E9DFCF]" style="width:100%;box-sizing:border-box;">
    <!-- Floating orbs -->
    <div class="pz-orb" style="width:500px;height:500px;top:-150px;right:-100px;"></div>
    <div class="pz-orb" style="width:300px;height:300px;bottom:-80px;left:5%;animation-delay:-3s;animation-duration:11s;"></div>
    <div class="pz-orb" style="width:200px;height:200px;top:30%;left:3%;animation-delay:-6s;animation-duration:9s;"></div>

    <div class="px-6 md:px-12 lg:px-20 py-16 md:py-24 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center max-w-7xl mx-auto">

            <!-- Left: Text -->
            <div>
                <div class="pz-hero-text inline-flex items-center gap-2 pz-mono text-xs uppercase tracking-widest text-[#D9432E] bg-[#FFE7E1] px-3 py-1.5 rounded-full mb-6">
                    For PG &amp; hostel owners
                </div>
                <h1 class="pz-hero-text pz-serif text-4xl md:text-5xl font-bold text-[#16233B] leading-[1.1] mb-5">
                    List your PG on Pizi —<br><span style="color:#FF6551;">fill every bed</span> with genuine tenants.
                </h1>
                <p class="pz-hero-text text-lg text-[#4B4438] mb-8 max-w-lg">
                    Join PG owners across Delhi NCR who connect directly with verified, ready-to-move tenants. No brokers. No commissions. Just real leads that convert.
                </p>
                <div class="pz-hero-text flex flex-wrap items-center gap-4 mb-8">
                    <button onclick="openModal(1)" class="pz-shimmer inline-flex items-center gap-2 bg-[#FF6551] hover:bg-[#D9432E] text-white font-semibold px-7 py-3.5 rounded-xl transition">
                        Register your PG — free
                    </button>
                    <a href="#plans" class="text-[#16233B] hover:text-[#D9432E] font-semibold text-sm transition">Or view paid plans →</a>
                </div>
                <div class="pz-hero-text flex flex-wrap gap-x-6 gap-y-2 text-sm text-[#16233B] font-medium">
                    <span>✓ 100% verified leads</span>
                    <span>✓ Zero brokerage</span>
                    <span>✓ Free to list</span>
                    <span>✓ Go live in 24hr</span>
                </div>
                <div class="pz-hero-text mt-6 inline-flex flex-wrap items-center gap-3 p-3 pr-4 rounded-2xl bg-white/70 border border-[#E4D9C8]">
                    <span class="w-10 h-10 rounded-xl bg-[#16233B] text-white flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-mobile-screen"></i></span>
                    <span class="text-sm leading-tight">
                        <span class="block font-bold text-[#16233B]">Manage leads from your phone</span>
                        <span class="block text-[#4B4438]">Get instant lead alerts with the Pizi Owner app</span>
                    </span>
                    <a href="https://play.google.com/store/apps/details?id=com.pizi_owner.india" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#16233B] hover:bg-[#0f1a2e] text-white text-sm font-semibold transition">
                        <i class="fa-brands fa-google-play"></i> Get it on Google Play
                    </a>
                </div>
            </div>

            <!-- Right: Door animation + stat cards -->
            <div class="flex flex-col items-center gap-6">
                <!-- Door SVG -->
                <div class="pz-door-wrap pz-door-idle hidden md:block">
                    <svg width="200" height="240" viewBox="0 0 260 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="20" y="10" width="220" height="300" rx="14" fill="#16233B"/>
                        <rect x="34" y="24" width="192" height="272" rx="8" fill="#FF6551" opacity="0.9"/>
                        <circle cx="200" cy="160" r="9" fill="#FBF3EA"/>
                        <g class="pz-key pz-key-slide">
                            <rect x="150" y="152" width="46" height="16" rx="8" fill="#E3A130"/>
                            <circle cx="150" cy="160" r="17" fill="none" stroke="#E3A130" stroke-width="7"/>
                            <rect x="188" y="160" width="6" height="10" fill="#E3A130"/>
                            <rect x="180" y="160" width="6" height="14" fill="#E3A130"/>
                        </g>
                    </svg>
                </div>

                <!-- Stat cards grid -->
                <div class="grid grid-cols-2 gap-3 w-full max-w-sm">
                    <div class="pz-stat-card text-center">
                        <div class="pz-mono text-2xl font-bold text-[#FF6551]">500+</div>
                        <div class="text-xs text-[#5C5344] mt-1">PGs listed</div>
                    </div>
                    <div class="pz-stat-card text-center">
                        <div class="pz-mono text-2xl font-bold text-[#FF6551]">10k+</div>
                        <div class="text-xs text-[#5C5344] mt-1">Monthly searches</div>
                    </div>
                    <div class="pz-stat-card text-center">
                        <div class="pz-mono text-2xl font-bold text-[#FF6551]">24hr</div>
                        <div class="text-xs text-[#5C5344] mt-1">Go live time</div>
                    </div>
                    <div class="pz-stat-card text-center">
                        <div class="pz-mono text-2xl font-bold text-[#FF6551]">0%</div>
                        <div class="text-xs text-[#5C5344] mt-1">Brokerage</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ WHY LIST — benefits ============ -->
<div class="max-w-6xl mx-auto px-4 py-20">
    <div class="max-w-2xl mb-12">
        <p class="pz-mono text-xs uppercase tracking-widest text-[#D9432E] mb-3">Why list with Pizi</p>
        <h2 class="pz-serif text-3xl md:text-4xl font-bold text-[#16233B]">Everything an owner needs, nothing you don't.</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @php
            $benefits = [
                ['Verified, ready-to-move tenants', 'Every lead is screened before it reaches you. Spend your time closing move-ins, not chasing dead enquiries.'],
                ['Zero brokerage, zero middlemen', 'Connect with tenants directly. You keep 100% of your rent — Pizi never takes a cut of your earnings.'],
                ['Free listing to get started', 'Create your PG profile at no cost. Add photos, amenities, room types, and pricing — go live in minutes.'],
                ['Reach thousands of searchers daily', 'Your PG appears on both the Pizi website and mobile app, right where tenants are actively searching in your locality.'],
                ['Full control from your dashboard', 'Update availability, edit pricing, pause listings, and track every lead in one simple dashboard.'],
                ['Direct contact with tenants', 'Get tenant name, phone, and requirement instantly. Call, WhatsApp, or schedule a visit on your own terms.'],
                ['Build trust with a verified badge', 'Verified PGs get a trust badge that boosts visibility and gives tenants the confidence to book faster.'],
            ];
        @endphp
        @foreach($benefits as $i => $b)
            <div class="pz-keytag pz-reveal text-[#16233B] bg-white border border-[#EBE2D3] rounded-2xl p-6 pt-8">
                <h3 class="font-bold text-lg mb-2">{{ $b[0] }}</h3>
                <p class="text-sm text-[#5C5344] leading-relaxed">{{ $b[1] }}</p>
            </div>
        @endforeach
    </div>
</div>

<!-- ============ HOW IT WORKS ============ -->
<div class="bg-[#FBF3EA] border-y border-[#EBE2D3]">
    <div class="max-w-6xl mx-auto px-4 py-20">
        <div class="max-w-2xl mb-12">
            <p class="pz-mono text-xs uppercase tracking-widest text-[#D9432E] mb-3">How it works</p>
            <h2 class="pz-serif text-3xl md:text-4xl font-bold text-[#16233B]">From sign-up to move-in, six steps.</h2>
        </div>

        @php
            $steps = [
                ['Register your account', 'Sign up with your name, phone number, and email. Quick OTP verification and you\'re in.'],
                ['Add your PG details', 'Enter your PG name, location, room types (single/double/triple), rent, deposit, and available beds.'],
                ['Upload photos & amenities', 'Add clear photos and tick your amenities — Wi-Fi, meals, AC, laundry, housekeeping, power backup, and more.'],
                ['Get verified', 'Our team reviews your listing to keep quality high. Once approved, your PG goes live with a verified badge.'],
                ['Start receiving leads', 'Interested tenants send enquiries and visit requests straight to your dashboard and phone.'],
                ['Convert & move them in', 'Connect directly, schedule the visit, close the deal — no commission, no waiting.'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($steps as $i => $s)
                <div class="pz-reveal bg-white border border-[#EBE2D3] rounded-2xl p-6">
                    <div class="pz-mono w-9 h-9 rounded-full bg-[#FF6551] text-white text-sm font-bold flex items-center justify-center mb-4">{{ $i + 1 }}</div>
                    <h3 class="text-[#16233B] font-bold text-base mb-2">{{ $s[0] }}</h3>
                    <p class="text-[#5C5344] text-sm leading-relaxed">{{ $s[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- ============ LEAD TYPES ============ -->
<div class="max-w-6xl mx-auto px-4 py-20">
    <div class="max-w-2xl mb-12">
        <p class="pz-mono text-xs uppercase tracking-widest text-[#D9432E] mb-3">How leads work</p>
        <h2 class="pz-serif text-3xl md:text-4xl font-bold text-[#16233B]">When a tenant is interested, you'll know exactly why.</h2>
        <p class="text-[#5C5344] mt-3">Every lead includes the tenant's name, contact number, preferred room type, and move-in timeline.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @php
            $leadTypes = [
                ['🔥', 'Ready-to-move', 'Tenant is looking to shift immediately. Highest intent — best chance to close fast.'],
                ['📅', 'Visit request', 'Tenant wants to see the PG in person. You approve a slot and confirm the visit.'],
                ['💬', 'Enquiry', 'Tenant has questions about rent, food, sharing, or rules before deciding. A warm lead to nurture.'],
                ['⭐', 'Priority', 'For verified & premium listings. Featured placement means your PG is shown first.'],
            ];
        @endphp
        @foreach($leadTypes as $lt)
            <div class="pz-reveal bg-[#FBF3EA] border border-[#EBE2D3] rounded-2xl p-6">
                <div class="text-3xl mb-3">{{ $lt[0] }}</div>
                <h3 class="font-bold text-[#16233B] mb-2">{{ $lt[1] }}</h3>
                <p class="text-sm text-[#5C5344] leading-relaxed">{{ $lt[2] }}</p>
            </div>
        @endforeach
    </div>
</div>

<!-- ============ PLANS + REGISTRATION (FUNCTIONAL — DO NOT ALTER IDS/JS) ============ -->
<div id="plans" class="bg-white border-y border-[#EBE2D3] scroll-mt-8">
    <div class="max-w-6xl mx-auto px-4 py-20">

     <div class="max-w-2xl mb-4">
            <p class="pz-mono text-xs uppercase tracking-widest text-[#D9432E] mb-3">Get started</p>
            <h1 class="pz-serif text-3xl md:text-4xl font-bold text-[#16233B] mb-5">Select a plan and fill the form</h1>

            <a href="/register-free" class="pz-glow-pulse pz-in flex items-center justify-between gap-4 bg-[#EFFAF4] border-2 border-[#2E9E6E] rounded-2xl px-6 py-4 hover:bg-[#E3F5EB] transition group">
                <div>
                    <p class="font-bold text-[#16233B]">Just want to try it out first?</p>
                    <p class="text-sm text-[#2E9E6E]">Register for free — no credits, no payment, list your PG in minutes.</p>
                </div>
                <span class="pz-mono text-sm font-bold text-white bg-[#2E9E6E] px-4 py-2 rounded-lg whitespace-nowrap group-hover:bg-[#248059] transition">
                    Start free →
                </span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-10">

            <!-- LEFT: PACKAGES -->
            <div class="md:col-span-2">

                <div class="space-y-4">
                    @if($packages && $packages->count())
                        @foreach($packages as $pkg)
                            <div class="package-card pz-keytag pz-reveal p-6 pt-7 rounded-2xl border-2 border-[#EBE2D3] hover:border-[#FF6551] hover:bg-[#FFF6F4] transition cursor-pointer text-[#16233B]"
                                 onclick="selectPackage(this, {{ $pkg->id }}, '{{ $pkg->name }}', {{ $pkg->price_inr }}, {{ $pkg->total_credits }})"
                                 data-package-id="{{ $pkg->id }}">

                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-xl font-bold">{{ $pkg->name }}</h3>
                                        <p class="pz-mono text-sm text-[#5C5344] mt-2">{{ $pkg->total_credits }} credits</p>
                                        @if($pkg->description)
                                            <p class="text-sm text-[#8A8071] mt-3">{{ $pkg->description }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <div class="pz-mono text-3xl font-bold text-[#FF6551]">₹{{ number_format($pkg->price_inr) }}</div>
                                        @if($pkg->is_popular)
                                            <span class="inline-block mt-2 bg-[#E3A130] text-white text-xs px-3 py-1 rounded-full font-bold"><i class="fa-solid fa-star fa-fw" style="color:#f59e0b"></i> Popular</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-[#5C5344]">No packages available.</p>
                    @endif
                </div>

                <!-- Info Box -->
                <div class="bg-[#16233B] rounded-2xl p-6 mt-8">
                    <h3 class="font-bold text-white mb-4">Why choose Pizi?</h3>
                    <div class="grid grid-cols-2 gap-5 text-sm text-[#B9C2D4]">
                        <div>
                            <p class="font-bold text-white"><i class="fa-solid fa-rocket fa-fw"></i> Instant visibility</p>
                            <p class="text-xs mt-0.5">Get leads within hours</p>
                        </div>
                        <div>
                            <p class="font-bold text-white"><i class="fa-solid fa-sack-dollar fa-fw"></i> Zero commission</p>
                            <p class="text-xs mt-0.5">No hidden charges</p>
                        </div>
                        <div>
                            <p class="font-bold text-white"><i class="fa-solid fa-mobile-screen fa-fw"></i> Direct contact</p>
                            <p class="text-xs mt-0.5">Real phone numbers</p>
                        </div>
                        <div>
                            <p class="font-bold text-white"><i class="fa-solid fa-circle-check fa-fw"></i> Verified tenants</p>
                            <p class="text-xs mt-0.5">No spam profiles</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: REGISTRATION FORM -->
            <div>
                <div class="pz-glow-pulse bg-white rounded-2xl p-8 shadow-xl border border-[#EBE2D3] sticky top-8">
                    <h2 class="text-2xl font-bold text-[#16233B] mb-2">Create your account</h2>
<p class="text-sm text-[#8A8071] mb-6">Looking for a PG instead of listing one? <a href="{{ route('search') }}" class="text-[#D9432E] font-semibold">Browse PGs →</a></p>

                    <a href="{{ route('google.redirect') }}" class="w-full flex items-center justify-center gap-3 px-5 py-3 border border-[#DCD2BF] rounded-xl font-bold text-sm text-[#16233B] hover:bg-black/5 transition mb-4">
                        <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.48a5.55 5.55 0 0 1-2.4 3.64v3h3.88c2.27-2.09 3.56-5.17 3.56-8.83z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.96-2.9l-3.88-3c-1.08.72-2.45 1.15-4.08 1.15-3.13 0-5.79-2.12-6.74-4.96H1.26v3.09A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.26 14.29A7.2 7.2 0 0 1 4.88 12c0-.79.14-1.56.38-2.29V6.62H1.26A12 12 0 0 0 0 12c0 1.94.46 3.77 1.26 5.38z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.26 6.62l4 3.09C6.21 6.87 8.87 4.75 12 4.75z"/></svg>
                        Autofill with Google
                    </a>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex-1 h-px bg-[#DCD2BF]"></div>
                        <span class="text-xs text-[#8A8071] font-semibold uppercase">or fill manually</span>
                        <div class="flex-1 h-px bg-[#DCD2BF]"></div>
                    </div>

                    <!-- Selected Package Display -->
                    <div id="selectedPackageInfo" class="hidden bg-[#EFFAF4] p-4 rounded-xl border border-[#BEE8D2] mb-6">
                        <p class="text-xs text-[#2E9E6E] font-semibold uppercase tracking-wide">Selected plan</p>
                        <p id="selectedPackageName" class="font-bold text-lg text-[#16233B]"></p>
                        <p id="selectedPackagePrice" class="pz-mono text-[#FF6551] font-bold text-xl"></p>
                    </div>

                    <form id="registrationForm" class="space-y-4">
                        @csrf
                        <input type="hidden" id="selectedPackageId" name="package_id">

                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-2">Full Name *</label>
                            <input
                                type="text"
                                name="name"
                                required
                                placeholder="Your full name"
                                value="{{ session('google_prefill.name') }}"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-2">Email *</label>
                            <input
                                type="email"
                                name="email"
                                required
                                placeholder="your@email.com"
                                value="{{ session('google_prefill.email') }}"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] focus:border-transparent"
                            >
                        </div>

                      <div>
    <label class="block text-sm font-medium text-[#4B4438] mb-2">Phone (10-digit) *</label>
    <div class="flex gap-2">
        <input
            type="tel"
            name="phone"
            id="ownerPhoneInput"
            required
            maxlength="10"
            placeholder="9876543210"
            pattern="[0-9]{10}"
            class="flex-1 px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] focus:border-transparent"
        >
        <button type="button" id="ownerSendOtpBtn" onclick="ownerSendPhoneOtp()"
            class="px-4 py-2 bg-[#16233B] hover:bg-black text-white rounded-xl font-semibold text-sm whitespace-nowrap">
            Verify
        </button>
    </div>
    <p id="ownerPhoneStatus" class="text-xs text-[#8A8071] mt-1"></p>

    <div id="ownerOtpBox" class="hidden mt-3 p-4 bg-cream/50 rounded-xl border border-[#DCD2BF]">
        <label class="block text-xs font-bold uppercase text-[#8A8071] mb-2">Enter 6-digit OTP</label>
        <div class="flex gap-2">
            <input type="text" id="ownerOtpInput" maxlength="6" placeholder="••••••"
                class="flex-1 px-4 py-2.5 border border-[#DCD2BF] rounded-xl tracking-widest text-center">
            <button type="button" onclick="ownerVerifyPhoneOtp()"
                class="px-4 py-2 bg-[#FF6551] hover:bg-[#D9432E] text-white rounded-xl font-semibold text-sm">
                Confirm
            </button>
        </div>
        <p id="ownerOtpStatus" class="text-xs mt-2"></p>
    </div>
    <!--<p class="text-xs text-[#8A8071] mt-1">-->
    <!--    Having trouble receiving the OTP? <a href="#" onclick="ownerSkipPhoneOtp(); return false;" class="text-[#D9432E] font-semibold">Skip for now</a>-->
    <!--</p>-->
</div>

                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-2">Password (min 6) *</label>
                            <input
                                type="password"
                                name="password"
                                required
                                minlength="6"
                                placeholder="••••••••"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#4B4438] mb-2">Confirm Password *</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                required
                                placeholder="••••••••"
                                class="w-full px-4 py-3 border border-[#DCD2BF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FF6551] focus:border-transparent"
                            >
                        </div>

                        <button
                            type="button"
                            onclick="buyPackage()"
                            id="buyButton"
                            disabled
                            class="pz-shimmer w-full bg-[#FF6551] hover:bg-[#D9432E] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl transition mt-6"
                        >
                            <i class="fa-solid fa-credit-card fa-fw"></i> Create Account &amp; Buy Credits
                        </button>
                    </form>

                    <p class="text-center text-sm text-[#8A8071] mt-6">
                        Already registered? <a href="/login" class="text-[#D9432E] font-semibold">Login here</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============ TRUST STATS ============ -->
<div class="max-w-6xl mx-auto px-4 py-20">
    <div class="max-w-2xl mb-12">
        <p class="pz-mono text-xs uppercase tracking-widest text-[#D9432E] mb-3">Why owners choose Pizi</p>
        <h2 class="pz-serif text-3xl md:text-4xl font-bold text-[#16233B]">Numbers that keep beds filled.</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @php
            $stats = [
                ['🏠', 500, '+', 'PGs listed across Delhi NCR'],
                ['👥', 10, 'k+', 'Active tenant searches every month'],
                ['⚡', 24, 'hr', 'Typical time to go live once verified'],
                ['💯', 0, '%', 'Brokerage, always'],
            ];
        @endphp
        @foreach($stats as $s)
            <div class="pz-reveal bg-[#FBF3EA] border border-[#EBE2D3] rounded-2xl p-6 text-center">
                <div class="text-2xl mb-2">{{ $s[0] }}</div>
                <div class="pz-mono text-3xl font-bold text-[#16233B]">
                    <span class="pz-counter" data-target="{{ $s[1] }}">0</span>{{ $s[2] }}
                </div>
                <p class="text-xs text-[#5C5344] mt-2">{{ $s[3] }}</p>
            </div>
        @endforeach
    </div>
</div>

<!-- ============ FAQ ============ -->
<div class="bg-[#FBF3EA] border-t border-[#EBE2D3]">
    <div class="max-w-3xl mx-auto px-4 py-20">
        <p class="pz-mono text-xs uppercase tracking-widest text-[#D9432E] mb-3">Frequently asked questions</p>
        <h2 class="pz-serif text-3xl md:text-4xl font-bold text-[#16233B] mb-10">Before you sign up.</h2>

        <div class="space-y-3">
            @php
                $faqs = [
                    ['Is listing my PG really free?', 'Yes. Creating your PG profile and going live is completely free. Paid packs are optional and only unlock extra visibility and leads.'],
                    ['Does Pizi take any commission on rent?', "No. You deal directly with tenants and keep 100% of your rent."],
                    ['How soon will my PG go live?', 'Most listings are verified and live within 24 hours of submission.'],
                    ['How do I receive tenant leads?', 'Leads arrive instantly on your owner dashboard, along with an SMS/WhatsApp alert so you never miss one.'],
                    ['Can I edit or pause my listing anytime?', 'Absolutely. You have full control from your dashboard — update details, change pricing, or pause whenever you like.'],
                ];
            @endphp
            @foreach($faqs as $f)
                <details class="pz-faq bg-white border border-[#EBE2D3] rounded-xl px-5 py-4">
                    <summary class="flex items-center justify-between font-semibold text-[#16233B]">
                        {{ $f[0] }}
                        <span class="pz-faq-icon text-[#FF6551] text-xl leading-none">+</span>
                    </summary>
                    <p class="text-sm text-[#5C5344] mt-3 leading-relaxed">{{ $f[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</div>

<!-- ============ FINAL CTA ============ -->
<div class="bg-[#16233B]">
    <div class="max-w-4xl mx-auto px-4 py-20 text-center">
        <h2 class="pz-serif text-3xl md:text-4xl font-bold text-white mb-4">Ready to fill your vacant beds?</h2>
        <p class="text-[#B9C2D4] mb-8 max-w-xl mx-auto">Register your PG on Pizi today and start receiving genuine tenant leads — free, fast, and broker-free.</p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="#plans" class="pz-shimmer inline-flex items-center gap-2 bg-[#FF6551] hover:bg-[#D9432E] text-white font-semibold px-8 py-4 rounded-xl transition">
                Register your PG now
            </a>
            <a href="https://play.google.com/store/apps/details?id=com.pizi_owner.india" target="_blank" rel="noopener" class="inline-flex items-center gap-2 border border-white/30 hover:bg-white/10 text-white font-semibold px-8 py-4 rounded-xl transition">
                <i class="fa-brands fa-google-play"></i> Get the Owner app
            </a>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    let selectedPackage = null;
    
    
    async function ownerSendPhoneOtp() {
        const phone = document.getElementById('ownerPhoneInput').value.trim();
        const statusEl = document.getElementById('ownerPhoneStatus');

        if (!/^\d{10}$/.test(phone)) {
            statusEl.textContent = 'Enter a valid 10-digit number first.';
            statusEl.className = 'text-xs text-rose-600 mt-1';
            return;
        }

        const btn = document.getElementById('ownerSendOtpBtn');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        try {
            const res = await fetch('{{ route("register.sendotp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ phone: phone })
            });
            const result = await res.json();

            statusEl.textContent = result.message;
            statusEl.className = result.success ? 'text-xs text-emerald-600 mt-1' : 'text-xs text-rose-600 mt-1';

            if (result.success) {
                document.getElementById('ownerOtpBox').classList.remove('hidden');
            }
        } catch (e) {
            statusEl.textContent = 'Could not send OTP. Please try again.';
            statusEl.className = 'text-xs text-rose-600 mt-1';
        }

        btn.disabled = false;
        btn.textContent = 'Verify';
    }

    async function ownerVerifyPhoneOtp() {
        const phone = document.getElementById('ownerPhoneInput').value.trim();
        const otp = document.getElementById('ownerOtpInput').value.trim();
        const statusEl = document.getElementById('ownerOtpStatus');

        if (!/^\d{6}$/.test(otp)) {
            statusEl.textContent = 'Enter the 6-digit OTP.';
            statusEl.className = 'text-xs text-rose-600 mt-2';
            return;
        }

        try {
            const res = await fetch('{{ route("register.verifyotp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ phone: phone, otp: otp })
            });
            const result = await res.json();

            statusEl.textContent = result.message;
            statusEl.className = result.success ? 'text-xs text-emerald-600 mt-2' : 'text-xs text-rose-600 mt-2';

           if (result.success) {
                document.getElementById('ownerPhoneInput').readOnly = true;
                document.getElementById('ownerSendOtpBtn').style.display = 'none';
                document.getElementById('ownerOtpBox').innerHTML = '<p class="text-sm text-emerald-600 font-semibold">✓ Phone verified</p>';
            }
        } catch (e) {
            statusEl.textContent = 'Verification failed. Please try again.';
            statusEl.className = 'text-xs text-rose-600 mt-2';
        }
    }

    function ownerSkipPhoneOtp() {
        document.getElementById('ownerOtpBox').classList.add('hidden');
        document.getElementById('ownerPhoneStatus').textContent = 'Phone verification skipped for now.';
        document.getElementById('ownerPhoneStatus').className = 'text-xs text-[#8A8071] mt-1';
    }

    function selectPackage(element, id, name, price, credits) {
        // Remove previous selection
        document.querySelectorAll('.package-card').forEach(card => {
            card.classList.remove('border-coral-500', 'bg-coral-50');
            card.classList.add('border-gray-200');
        });

        // Add selection to clicked card
        element.classList.add('border-coral-500', 'bg-coral-50');
        element.classList.remove('border-gray-200');

        // Store selected package
        selectedPackage = {
            id: id,
            name: name,
            price: price,
            credits: credits
        };

        // Show package info
        document.getElementById('selectedPackageInfo').classList.remove('hidden');
        document.getElementById('selectedPackageName').textContent = name + ' - ' + credits + ' credits';
        document.getElementById('selectedPackagePrice').textContent = '₹' + price;
        document.getElementById('selectedPackageId').value = id;

        // Enable button
        document.getElementById('buyButton').disabled = false;
    }

    async function buyPackage() {
        if (!selectedPackage) {
            alert('Please select a package');
            return;
        }

        const form = document.getElementById('registrationForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        // Validate
        if (!data.name || !data.email || !data.phone || !data.password) {
            alert('Please fill all fields');
            return;
        }

        const buyBtn = document.getElementById('buyButton');
        buyBtn.disabled = true;
        buyBtn.textContent = 'Processing...';

        try {
            // Register user
            const registerRes = await fetch('/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
    name: data.name,
    email: data.email,
    phone: data.phone,
    password: data.password,
    password_confirmation: data.password_confirmation,
    role: 'owner'
})
            });

            const registerData = await registerRes.json();

            if (!registerData.success) {
    const msg = typeof registerData.message === 'object' 
        ? JSON.stringify(registerData.message) 
        : (registerData.message || 'Unknown error');
    alert('Registration error: ' + msg);
                buyBtn.disabled = false;
                buyBtn.textContent = '💳 Create Account & Buy Credits';
                return;
            }

            // Create payment order
            const orderRes = await fetch('/owner/purchase-package', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    package_id: selectedPackage.id,
                    payment_method: 'razorpay'
                })
            });

            const orderData = await orderRes.json();

            if (!orderData.success) {
                alert('Payment error: ' + (orderData.message || 'Unknown error'));
                buyBtn.disabled = false;
                buyBtn.textContent = '💳 Create Account & Buy Credits';
                return;
            }

            // Open Razorpay checkout
            const options = {
                key: '{{ env("RAZORPAY_KEY_ID") }}',
                amount: orderData.amount,
                currency: orderData.currency,
                name: 'Pizi',
                description: selectedPackage.name,
                order_id: orderData.order_id,
                handler: function(response) {
                    verifyPayment(response);
                },
                prefill: {
                    name: data.name,
                    email: data.email,
                    contact: data.phone
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();

        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            buyBtn.disabled = false;
            buyBtn.textContent = '💳 Create Account & Buy Credits';
        }
    }


    // ── 2-Step Modal Logic ──────────────────────────────────────
    let mSelectedPackage = null;

    function openModal(step) {
        document.getElementById('regModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        if (step === 2) goToStep2();
        else goToStep1();
    }
    function closeModal() {
        document.getElementById('regModal').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('regModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
    window.addEventListener('load', function() {
        setTimeout(function() { openModal(1); }, 400);
    });

    function goToStep1() {
        document.getElementById('mStep1').classList.add('active');
        document.getElementById('mStep2').classList.remove('active');
        document.getElementById('mTab1').className = 'flex-1 text-center py-4 text-sm font-semibold text-[#FF6551] border-b-2 border-[#FF6551] cursor-pointer';
        document.getElementById('mTab2').className = 'flex-1 text-center py-4 text-sm font-semibold text-[#8A8071] cursor-default';
    }
    function goToStep2() {
        if (!mSelectedPackage) return;
        document.getElementById('mStep1').classList.remove('active');
        document.getElementById('mStep2').classList.add('active');
        document.getElementById('mTab1').className = 'flex-1 text-center py-4 text-sm font-semibold text-[#8A8071] cursor-pointer';
        document.getElementById('mTab2').className = 'flex-1 text-center py-4 text-sm font-semibold text-[#FF6551] border-b-2 border-[#FF6551]';
        document.querySelector('#regModal .modal-box').scrollTop = 0;
    }

    function mSelectPackage(el, id, name, price, credits) {
        document.querySelectorAll('.m-package-card').forEach(c => {
            c.classList.remove('border-[#FF6551]', 'bg-[#FFF6F4]');
            c.classList.add('border-[#EBE2D3]');
        });
        el.classList.add('border-[#FF6551]', 'bg-[#FFF6F4]');
        el.classList.remove('border-[#EBE2D3]');
        mSelectedPackage = { id, name, price, credits };
        document.getElementById('mNextBtn').disabled = false;
        document.getElementById('mSelectedPackageId').value = id;
        document.getElementById('mPkgName').textContent = name;
        document.getElementById('mPkgPrice').textContent = '₹' + price;
        document.getElementById('mPkgCredits').textContent = credits + ' credits';
    }

    async function mOwnerSendPhoneOtp() {
        const phone = document.getElementById('mOwnerPhoneInput').value.trim();
        const statusEl = document.getElementById('mOwnerPhoneStatus');
        if (!/^\d{10}$/.test(phone)) {
            statusEl.textContent = 'Enter a valid 10-digit number.';
            statusEl.className = 'text-xs text-rose-600 mt-1'; return;
        }
        const btn = document.getElementById('mOwnerSendOtpBtn');
        btn.disabled = true; btn.textContent = 'Sending...';
        try {
            const csrf = document.querySelector('#mRegistrationForm input[name="_token"]').value;
            const res = await fetch('{{ route("register.sendotp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ phone })
            });
            const result = await res.json();
            statusEl.textContent = result.message;
            statusEl.className = result.success ? 'text-xs text-emerald-600 mt-1' : 'text-xs text-rose-600 mt-1';
            if (result.success) document.getElementById('mOwnerOtpBox').classList.remove('hidden');
        } catch(e) {
            statusEl.textContent = 'Could not send OTP.';
            statusEl.className = 'text-xs text-rose-600 mt-1';
        }
        btn.disabled = false; btn.textContent = 'Verify';
    }

    async function mOwnerVerifyPhoneOtp() {
        const phone = document.getElementById('mOwnerPhoneInput').value.trim();
        const otp = document.getElementById('mOwnerOtpInput').value.trim();
        const statusEl = document.getElementById('mOwnerOtpStatus');
        if (!/^\d{6}$/.test(otp)) {
            statusEl.textContent = 'Enter the 6-digit OTP.';
            statusEl.className = 'text-xs text-rose-600'; return;
        }
        try {
            const csrf = document.querySelector('#mRegistrationForm input[name="_token"]').value;
            const res = await fetch('{{ route("register.verifyotp") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ phone, otp })
            });
            const result = await res.json();
            statusEl.textContent = result.message;
            statusEl.className = result.success ? 'text-xs text-emerald-600' : 'text-xs text-rose-600';
            if (result.success) {
                document.getElementById('mOwnerPhoneInput').readOnly = true;
                document.getElementById('mOwnerSendOtpBtn').style.display = 'none';
                document.getElementById('mOwnerOtpBox').innerHTML = '<p class="text-sm text-emerald-600 font-semibold">✓ Phone verified</p>';
            }
        } catch(e) {
            statusEl.textContent = 'Verification failed.';
            statusEl.className = 'text-xs text-rose-600';
        }
    }

    async function mBuyPackage() {
        if (!mSelectedPackage) { alert('Please select a package first.'); return; }
        const form = document.getElementById('mRegistrationForm');
        const data = Object.fromEntries(new FormData(form));
        if (!data.name || !data.email || !data.phone || !data.password) { alert('Please fill all fields.'); return; }
        const btn = document.getElementById('mBuyButton');
        btn.disabled = true; btn.textContent = 'Processing...';
        try {
            const csrf = data['_token'];
            const regRes = await fetch('/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ name: data.name, email: data.email, phone: data.phone, password: data.password, password_confirmation: data.password_confirmation, role: 'owner' })
            });
            const regData = await regRes.json();
            if (!regData.success) {
                const msg = typeof regData.message === 'object' ? JSON.stringify(regData.message) : (regData.message || 'Unknown error');
                alert('Registration error: ' + msg);
                btn.disabled = false; btn.textContent = '💳 Create Account & Buy Credits'; return;
            }
            const orderRes = await fetch('/owner/purchase-package', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ package_id: mSelectedPackage.id, payment_method: 'razorpay' })
            });
            const orderData = await orderRes.json();
            if (!orderData.success) {
                alert('Payment error: ' + (orderData.message || 'Unknown error'));
                btn.disabled = false; btn.textContent = '💳 Create Account & Buy Credits'; return;
            }
            const rzp = new Razorpay({
                key: '{{ env("RAZORPAY_KEY_ID") }}',
                amount: orderData.amount, currency: orderData.currency,
                name: 'Pizi', description: mSelectedPackage.name, order_id: orderData.order_id,
                handler: function(response) { mVerifyPayment(response, csrf); },
                prefill: { name: data.name, email: data.email, contact: data.phone }
            });
            rzp.open();
        } catch(err) {
            alert('An error occurred. Please try again.');
            btn.disabled = false; btn.textContent = '💳 Create Account & Buy Credits';
        }
    }

    async function mVerifyPayment(response, csrf) {
        try {
            const res = await fetch('/owner/verify-payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ razorpay_order_id: response.razorpay_order_id, razorpay_payment_id: response.razorpay_payment_id, razorpay_signature: response.razorpay_signature, package_id: mSelectedPackage.id })
            });
            const data = await res.json();
            if (data.success) { alert('Payment successful!'); window.location.href = '/owner'; }
            else { alert('Verification failed: ' + (data.message || 'Unknown error')); }
        } catch(e) { alert('Verification error. Please contact support.'); }
    }
    async function verifyPayment(response) {
        try {
            const res = await fetch('/owner/verify-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature,
                     package_id: selectedPackage.id  
                })
            });

            const data = await res.json();

            if (data.success) {
                alert('Payment successful! Your account has been created with credits.');
               window.location.href = '/owner';
            } else {
                alert('Payment verification failed: ' + (data.message || 'Unknown error'));
                document.getElementById('buyButton').disabled = false;
                document.getElementById('buyButton').textContent = '💳 Create Account & Buy Credits';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Verification error. Please contact support.');
            document.getElementById('buyButton').disabled = false;
            document.getElementById('buyButton').textContent = '💳 Create Account & Buy Credits';
        }
    }
</script>

<!-- ============ DESIGN-ONLY JS: scroll reveal + counters + FAQ (does not touch payment flow) ============ -->
<script>
    (function () {
        var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Scroll reveal
        var revealEls = document.querySelectorAll('.pz-reveal');
        if ('IntersectionObserver' in window && !prefersReduced) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry, idx) {
                    if (entry.isIntersecting) {
                        setTimeout(function () {
                            entry.target.classList.add('pz-in');
                        }, (idx % 6) * 70);
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });
            revealEls.forEach(function (el) { io.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('pz-in'); });
        }

        // Glow pulse on the form panel once visible
        var glowEl = document.querySelector('.pz-glow-pulse');
        if (glowEl && 'IntersectionObserver' in window && !prefersReduced) {
            var glowIo = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('pz-in');
                        glowIo.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });
            glowIo.observe(glowEl);
        }

        // Animated counters
        var counters = document.querySelectorAll('.pz-counter');
        function animateCounter(el) {
            var target = parseInt(el.getAttribute('data-target'), 10) || 0;
            if (prefersReduced || target === 0) { el.textContent = target; return; }
            var start = 0;
            var duration = 900;
            var startTime = null;
            function step(ts) {
                if (!startTime) startTime = ts;
                var progress = Math.min((ts - startTime) / duration, 1);
                el.textContent = Math.floor(progress * target);
                if (progress < 1) requestAnimationFrame(step);
                else el.textContent = target;
            }
            requestAnimationFrame(step);
        }
        if ('IntersectionObserver' in window) {
            var cIo = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        cIo.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.6 });
            counters.forEach(function (el) { cIo.observe(el); });
        } else {
            counters.forEach(animateCounter);
        }
    })();
</script>

@endsection