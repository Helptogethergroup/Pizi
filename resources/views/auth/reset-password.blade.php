@extends('layouts.app')
@section('title', 'Reset Password — Pizi')
@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-ink-900/10 shadow-xl shadow-ink-900/5">
        <h1 class="font-display font-black text-3xl">Create New Password</h1>
        <p class="text-ink-900/60 mt-2 text-sm">Enter your new password below</p>

        @if($errors->any())
            <div class="mt-6 mb-4 p-4 bg-rose-100 text-rose-700 rounded-lg text-sm">
                @foreach($errors->all() as $error)
                    <p>❌ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="mt-8 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            
            <input type="email" name="email" required value="{{ old('email', $email) }}" readonly
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none bg-ink-900/5 text-ink-900/60">

            <input type="password" name="password" required placeholder="New password"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">

            <input type="password" name="password_confirmation" required placeholder="Confirm password"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">

            <button type="submit" class="w-full py-3 bg-ink-900 text-cream rounded-xl font-bold hover:bg-ink-800">
                Reset Password →
            </button>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-ink-900/60 hover:text-ink-900 font-semibold">
                    Back to Login
                </a>
            </div>
        </form>
    </div>
</section>
@endsection