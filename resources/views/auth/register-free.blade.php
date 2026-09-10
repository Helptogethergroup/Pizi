@extends('layouts.app')
@section('title', 'Free Registration - Pizi')
@section('content')

<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8">

        <div class="mb-8">
            <a href="/register" class="text-coral-600 font-semibold mb-4 inline-block">← Back to paid plans</a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Free Onboarding</h1>
            <p class="text-gray-600">List your PG for free. No credits included — upgrade anytime from your dashboard.</p>
        </div>

        <div id="error-msg" class="hidden bg-red-100 text-red-800 p-4 rounded-lg mb-6 text-sm"></div>

        <a href="{{ route('google.redirect') }}" class="w-full flex items-center justify-center gap-3 px-5 py-3 border-2 border-gray-200 rounded-lg font-bold text-sm text-gray-800 hover:bg-gray-50 transition mb-4">
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.48a5.55 5.55 0 0 1-2.4 3.64v3h3.88c2.27-2.09 3.56-5.17 3.56-8.83z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.96-2.9l-3.88-3c-1.08.72-2.45 1.15-4.08 1.15-3.13 0-5.79-2.12-6.74-4.96H1.26v3.09A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.26 14.29A7.2 7.2 0 0 1 4.88 12c0-.79.14-1.56.38-2.29V6.62H1.26A12 12 0 0 0 0 12c0 1.94.46 3.77 1.26 5.38z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.26 6.62l4 3.09C6.21 6.87 8.87 4.75 12 4.75z"/></svg>
            Autofill with Google
        </a>
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-xs text-gray-400 font-semibold uppercase">or fill manually</span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <form id="free-reg-form" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-semibold text-gray-700">Full Name *</label>
                <input type="text" id="name" required value="{{ session('google_prefill.name') }}" class="w-full mt-1 px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Email *</label>
                <input type="email" id="email" required value="{{ session('google_prefill.email') }}" class="w-full mt-1 px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Phone (10-digit) *</label>
                <input type="tel" id="phone" required maxlength="10" class="w-full mt-1 px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Password (min 6) *</label>
                <input type="password" id="password" required minlength="6" class="w-full mt-1 px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Confirm Password *</label>
                <input type="password" id="password_confirmation" required minlength="6" class="w-full mt-1 px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
            </div>

            <button type="button" onclick="submitFreeReg()" id="free-btn"
                    class="w-full bg-coral-500 hover:bg-coral-600 text-white font-bold py-3 rounded-lg transition mt-6">
                Create Free Account
            </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            Already registered? <a href="/login" class="text-coral-600 font-bold hover:underline">Login here</a>
        </p>
    </div>
</div>

<script>
    const csrf = document.querySelector('input[name="_token"]').value;

    function showError(msg) {
        const box = document.getElementById('error-msg');
        box.innerText = typeof msg === 'string' ? msg : JSON.stringify(msg);
        box.classList.remove('hidden');
    }

    async function submitFreeReg() {
        document.getElementById('error-msg').classList.add('hidden');

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;
        const password_confirmation = document.getElementById('password_confirmation').value;

        if (!name || !email || !phone || !password) {
            showError('Fill all fields');
            return;
        }
        if (phone.length !== 10) {
            showError('Phone must be 10 digits');
            return;
        }
        if (password !== password_confirmation) {
            showError('Passwords do not match');
            return;
        }

        const btn = document.getElementById('free-btn');
        btn.disabled = true;
        btn.innerText = 'Creating account...';

        try {
            const res = await fetch('/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    name, email, phone, password, password_confirmation,
                    role: 'owner',
                    is_free: true
                })
            });

            const data = await res.json();

            if (!data.success) {
                showError(data.message || 'Registration failed');
                btn.disabled = false;
                btn.innerText = 'Create Free Account';
                return;
            }

            window.location.href = '/owner';

        } catch (error) {
            showError('Error: ' + error.message);
            btn.disabled = false;
            btn.innerText = 'Create Free Account';
        }
    }
</script>

@endsection