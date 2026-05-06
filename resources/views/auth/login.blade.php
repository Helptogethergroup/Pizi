@extends('layouts.app')
@section('title', 'Login — PGFind')
@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-ink-900/10 shadow-xl shadow-ink-900/5">
        <h1 class="font-display font-black text-3xl">Welcome back.</h1>
        <p class="text-ink-900/60 mt-2 text-sm">Login to manage your listings or leads.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
            @csrf
            <input name="email" type="email" required value="{{ old('email') }}" placeholder="Email"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <input name="password" type="password" required placeholder="Password"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="rounded border-ink-900/20 text-coral-500">
                Remember me
            </label>
            <button class="w-full py-3 bg-ink-900 text-cream rounded-xl font-bold hover:bg-ink-800">Login</button>
        </form>

        <p class="text-center text-sm text-ink-900/60 mt-6">
            New here? <a href="{{ route('register') }}" class="text-coral-600 font-semibold hover:underline">Register</a>
        </p>
    </div>
</section>
@endsection
