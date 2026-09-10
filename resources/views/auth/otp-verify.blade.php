@extends('layouts.app')
@section('title', 'Verify OTP — Pizi')
@section('content')

<div class="min-h-[80vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="font-display font-black text-4xl text-ink-950">Enter OTP</h1>
            <p class="text-ink-700 mt-2">We sent a 6-digit code to <strong>{{ $identifier }}</strong></p>
        </div>

        <div class="bg-white rounded-2xl p-8 border border-ink-100 shadow-xl shadow-ink-950/5">
            
            @if($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('otp.verify') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">6-digit OTP *</label>
                    <input name="otp" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autofocus placeholder="••••••" class="w-full mt-1 px-4 py-4 rounded-xl border border-ink-200 focus:border-coral-500 outline-none text-center text-3xl font-bold tracking-[0.5em]">
                </div>

                <button type="submit" class="w-full py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-base transition shadow-lg shadow-coral-500/30">
                    Verify & Login →
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-ink-100 flex items-center justify-between text-sm">
                <a href="{{ route('otp.login') }}" class="text-ink-700 hover:text-coral-500">← Change number</a>
                
                <form method="POST" action="{{ route('otp.resend') }}" class="inline" id="resendForm">
                    @csrf
                    <button type="submit" id="resendBtn" class="text-coral-500 font-bold disabled:opacity-50">
                        Resend OTP
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Resend timer
let timer = 60;
const btn = document.getElementById('resendBtn');
const originalText = btn.textContent;

function updateTimer() {
    if (timer > 0) {
        btn.disabled = true;
        btn.textContent = `Resend in ${timer}s`;
        timer--;
        setTimeout(updateTimer, 1000);
    } else {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}
updateTimer();

// // Auto-submit when 6 digits entered
// document.querySelector('input[name="otp"]').addEventListener('input', function(e) {
//     if (e.target.value.length === 6) {
//         e.target.form.submit();
//     }
// });
</script>

@endsection