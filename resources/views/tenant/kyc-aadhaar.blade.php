@extends('layouts.tenant')
@section('title', 'Aadhaar Verification')
@section('content')

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6 lg:p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-coral-50 text-3xl flex items-center justify-center mx-auto">🪪</div>
            <h1 class="font-display font-black text-2xl mt-4">Verify with Aadhaar</h1>
            <p class="text-ink-900/60 text-sm mt-2">Secure, paperless KYC — powered by Setu's Aadhaar OKYC.</p>
        </div>

        {{-- STEP 1: Enter Aadhaar + Captcha --}}
        <div id="stepAadhaar">
            <p class="text-xs font-bold uppercase text-ink-900/60 mb-1">Aadhaar Number</p>
            <input type="text" id="aadhaarNumber" maxlength="12" inputmode="numeric" placeholder="12-digit Aadhaar number"
                class="w-full px-4 py-3 rounded-xl border border-ink-900/15 tracking-widest text-center text-lg">
            <p class="text-xs text-ink-900/40 mt-1">We only store a masked version. Your full number is never saved.</p>

            {{-- Captcha area (hidden until loaded) --}}
            <div id="captchaArea" class="hidden mt-4">
                <p class="text-xs font-bold uppercase text-ink-900/60 mb-1">Enter the characters shown below</p>
                <div class="flex gap-3 items-center">
                    <img id="captchaImg" src="" alt="Captcha" class="h-14 rounded-lg border border-ink-900/15 bg-gray-50">
                    <button onclick="loadCaptcha()" class="text-xs text-coral-600 font-semibold underline">Refresh</button>
                </div>
                <input type="text" id="captchaCode" maxlength="10" placeholder="Type characters here"
                    class="w-full mt-2 px-4 py-3 rounded-xl border border-ink-900/15 tracking-widest">
            </div>

            <button onclick="handleStep1()" id="step1Btn"
                class="w-full mt-5 px-6 py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold transition">
                Continue
            </button>
        </div>

        {{-- STEP 2: Enter OTP --}}
        <div id="stepOtp" class="hidden">
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">
                ✅ OTP sent to your Aadhaar-linked mobile number.
            </div>

            <p class="text-xs font-bold uppercase text-ink-900/60 mb-1">Enter OTP</p>
            <input type="text" id="otpInput" maxlength="6" inputmode="numeric" placeholder="6-digit OTP"
                class="w-full px-4 py-3 rounded-xl border border-ink-900/15 tracking-widest text-center text-lg">

            <p class="text-xs font-bold uppercase text-ink-900/60 mt-4 mb-1">Set a 4-digit Share Code</p>
            <input type="text" id="shareCodeInput" maxlength="4" inputmode="numeric" placeholder="Any 4 digits, e.g. 1234"
                class="w-full px-4 py-3 rounded-xl border border-ink-900/15 tracking-widest text-center text-lg">
            <p class="text-xs text-ink-900/40 mt-1">This protects your Aadhaar data — remember it for future reference.</p>

            <button onclick="handleStep2()" id="step2Btn"
                class="w-full mt-5 px-6 py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold transition">
                Verify &amp; Continue
            </button>

            <button onclick="resetFlow()" class="w-full mt-2 px-6 py-2 text-ink-900/40 text-sm font-semibold">
                ← Start over
            </button>
        </div>

        {{-- Skip button (debug only) --}}
        @if(config('app.debug'))
        <div class="mt-6 pt-4 border-t border-dashed border-amber-300">
            <form method="POST" action="{{ route('tenant.kyc.aadhaar.skiptesting') }}">
                @csrf
                <button type="submit"
                    class="w-full px-4 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-xl font-semibold text-sm">
                    ⚠️ Skip Aadhaar for now (testing only)
                </button>
            </form>
            <p class="text-xs text-amber-700/60 mt-1 text-center">Visible only when APP_DEBUG=true. Remove before going live.</p>
        </div>
        @endif

        {{-- Error box --}}
        <div id="errorBox" class="hidden mt-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-xl"></div>
    </div>
</div>

<script>
// State stored in JS (request ID from Setu)
let setuRequestId = null;

function showError(msg) {
    const box = document.getElementById('errorBox');
    box.textContent = msg;
    box.classList.remove('hidden');
}
function clearError() {
    document.getElementById('errorBox').classList.add('hidden');
}
function setLoading(btnId, loading, label) {
    const btn = document.getElementById(btnId);
    btn.disabled = loading;
    btn.textContent = loading ? 'Please wait...' : label;
}

function resetFlow() {
    setuRequestId = null;
    document.getElementById('stepAadhaar').classList.remove('hidden');
    document.getElementById('stepOtp').classList.add('hidden');
    document.getElementById('captchaArea').classList.add('hidden');
    document.getElementById('aadhaarNumber').value = '';
    document.getElementById('captchaCode').value = '';
    clearError();
}

async function loadCaptcha() {
    clearError();
    const aadhaar = document.getElementById('aadhaarNumber').value.trim();
    if (!/^\d{12}$/.test(aadhaar)) {
        showError('Please enter a valid 12-digit Aadhaar number first.');
        return;
    }

    setLoading('step1Btn', true, 'Continue');

    try {
        const res = await fetch('{{ route("tenant.kyc.aadhaar.sendotp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ aadhaar_number: aadhaar })
        });
        const result = await res.json();

        if (!result.success) {
            showError(result.message || 'Could not load captcha. Please try again.');
            setLoading('step1Btn', false, 'Continue');
            return;
        }

        // Store request ID
        setuRequestId = result.request_id;

        // Show captcha image
        document.getElementById('captchaImg').src = 'data:image/png;base64,' + result.captcha_image;
        document.getElementById('captchaArea').classList.remove('hidden');
        setLoading('step1Btn', false, 'Send OTP');

        // Change button to send OTP now
        document.getElementById('step1Btn').onclick = sendOtp;

    } catch (e) {
        showError('Something went wrong. Please try again.');
        setLoading('step1Btn', false, 'Continue');
    }
}

function handleStep1() {
    // First click: load captcha. After captcha loads, button changes to sendOtp.
    loadCaptcha();
}

async function sendOtp() {
    clearError();
    const aadhaar    = document.getElementById('aadhaarNumber').value.trim();
    const captcha    = document.getElementById('captchaCode').value.trim();

    if (!setuRequestId) {
        showError('Please wait — loading captcha.');
        return;
    }
    if (!captcha) {
        showError('Please enter the captcha characters.');
        return;
    }

    setLoading('step1Btn', true, 'Send OTP');

    try {
        const res = await fetch('{{ route("tenant.kyc.aadhaar.verifyotp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                step: 'verify_aadhaar',
                request_id: setuRequestId,
                aadhaar_number: aadhaar,
                captcha_code: captcha
            })
        });
        const result = await res.json();

        if (!result.success) {
            showError(result.message || 'Invalid captcha. Please try again.');
            setLoading('step1Btn', false, 'Send OTP');
            // Refresh captcha
            document.getElementById('step1Btn').onclick = handleStep1;
            document.getElementById('captchaArea').classList.add('hidden');
            return;
        }

        // Move to OTP step
        document.getElementById('stepAadhaar').classList.add('hidden');
        document.getElementById('stepOtp').classList.remove('hidden');
        setLoading('step1Btn', false, 'Send OTP');

    } catch (e) {
        showError('Something went wrong. Please try again.');
        setLoading('step1Btn', false, 'Send OTP');
    }
}

async function handleStep2() {
    clearError();
    const otp       = document.getElementById('otpInput').value.trim();
    const shareCode = document.getElementById('shareCodeInput').value.trim();

    if (!/^\d{4,6}$/.test(otp)) {
        showError('Please enter a valid OTP.');
        return;
    }
    if (!/^\d{4}$/.test(shareCode)) {
        showError('Share code must be exactly 4 digits.');
        return;
    }

    setLoading('step2Btn', true, 'Verify & Continue');

    try {
        const res = await fetch('{{ route("tenant.kyc.aadhaar.verifyotp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                step: 'verify_otp',
                request_id: setuRequestId,
                otp: otp,
                share_code: shareCode
            })
        });
        const result = await res.json();

        if (!result.success) {
            showError(result.message || 'Verification failed. Please try again.');
            setLoading('step2Btn', false, 'Verify & Continue');
            return;
        }

        // Success → redirect to onboarding
        window.location.href = '{{ route("tenant.onboarding") }}';

    } catch (e) {
        showError('Something went wrong. Please try again.');
        setLoading('step2Btn', false, 'Verify & Continue');
    }
}
</script>

@endsection