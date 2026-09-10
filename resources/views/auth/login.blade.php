@extends('layouts.app')
@section('title', 'Login — PGFind')
@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-ink-900/10 shadow-xl shadow-ink-900/5">
        <h1 class="font-display font-black text-3xl">Welcome back.</h1>
        <p class="text-ink-900/60 mt-2 text-sm">Login to manage your listings or leads.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
            @csrf
            <input name="email" type="email" required value="{{ old('email', session('google_prefill.email')) }}" placeholder="Email"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <input name="password" type="password" required placeholder="Password"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="rounded border-ink-900/20 text-coral-500">
                Remember me
            </label>
            <button class="w-full py-3 bg-ink-900 text-cream rounded-xl font-bold hover:bg-ink-800">Login</button>
            <div class="mt-4 text-center">
                
    <a href="{{ route('otp.login') }}" class="inline-flex items-center gap-2 px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm w-full justify-center">
        📱 Login with OTP instead
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
    <a href="{{ route('password.request') }}" class="text-sm text-coral-500 hover:text-coral-600 font-semibold">
        🔓 Forgot Password?
    </a>
</div>

        <p class="text-center text-sm text-ink-900/60 mt-6">
            New here? <a href="{{ route('register') }}" class="text-coral-600 font-semibold hover:underline">Register</a>
        </p>
    </div>
</section>
@endsection
