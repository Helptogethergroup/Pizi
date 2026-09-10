
@extends('layouts.app')
@section('title', 'Login with OTP — Pizi')
@section('content')

<div class="min-h-[80vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="font-display font-black text-4xl text-ink-950">Welcome back</h1>
            <p class="text-ink-700 mt-2">Login with mobile number or email</p>
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

            <form method="POST" action="{{ route('otp.send') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="purpose" value="login">

                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Mobile or Email *</label>
                    <input name="identifier" required value="{{ old('identifier') }}" placeholder="9876543210 or you@email.com" autofocus class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none">
                </div>

                <button type="submit" class="w-full py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-base transition shadow-lg shadow-coral-500/30">
                    Send OTP →
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-ink-100 text-center text-sm">
                <a href="{{ route('login') }}" class="text-ink-700 hover:text-coral-500">Login with password instead</a>
            </div>

            <div class="mt-3 text-center text-sm text-ink-700">
                New to Pizi? <a href="{{ route('register') }}" class="text-coral-500 font-bold">Create account</a>
            </div>
        </div>
    </div>
</div>

@endsection