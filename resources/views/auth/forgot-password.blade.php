@extends('layouts.app')
@section('title', 'Forgot Password — Pizi')
@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-ink-900/10 shadow-xl shadow-ink-900/5">
        <h1 class="font-display font-black text-3xl">Reset Password</h1>
        <p class="text-ink-900/60 mt-2 text-sm">Enter your email to receive password reset link</p>

        @if(session('status'))
            <div class="mt-6 mb-4 p-4 bg-emerald-100 text-emerald-700 rounded-lg text-sm">
                <i class="fa-solid fa-circle-check fa-fw"></i> {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-6 mb-4 p-4 bg-rose-100 text-rose-700 rounded-lg text-sm">
                @foreach($errors->all() as $error)
                    <p><i class="fa-solid fa-circle-xmark fa-fw"></i> {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="mt-8 space-y-4">
            @csrf
            
            <input type="email" name="email" required value="{{ old('email') }}" placeholder="Email address"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">

            <button type="submit" class="w-full py-3 bg-ink-900 text-cream rounded-xl font-bold hover:bg-ink-800">
                Send Reset Link →
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