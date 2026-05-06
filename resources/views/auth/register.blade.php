@extends('layouts.app')
@section('title', 'Register — PGFind')
@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-ink-900/10 shadow-xl shadow-ink-900/5">
        <h1 class="font-display font-black text-3xl">Get started.</h1>
        <p class="text-ink-900/60 mt-2 text-sm">List your PG or save your favourites.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
            @csrf
            <input name="name" required placeholder="Full name" value="{{ old('name') }}"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <input name="email" type="email" required placeholder="Email" value="{{ old('email') }}"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <input name="phone" required placeholder="Phone (10-digit)" value="{{ old('phone') }}" pattern="[0-9]{10}"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">

            <div>
                <label class="text-xs font-semibold text-ink-900/60 uppercase">I am a</label>
                <div class="grid grid-cols-2 gap-2 mt-1">
                    <label>
                        <input type="radio" name="role" value="guest" class="peer hidden" checked>
                        <div class="text-center text-sm py-3 rounded-lg border border-ink-900/15 cursor-pointer peer-checked:bg-ink-900 peer-checked:text-cream">Tenant / Guest</div>
                    </label>
                    <label>
                        <input type="radio" name="role" value="owner" class="peer hidden">
                        <div class="text-center text-sm py-3 rounded-lg border border-ink-900/15 cursor-pointer peer-checked:bg-ink-900 peer-checked:text-cream">PG Owner</div>
                    </label>
                </div>
            </div>

            <input name="password" type="password" required placeholder="Password (min 6)"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <input name="password_confirmation" type="password" required placeholder="Confirm password"
                   class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">

            <button class="w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Create account</button>
        </form>

        <p class="text-center text-sm text-ink-900/60 mt-6">
            Already a user? <a href="{{ route('login') }}" class="text-coral-600 font-semibold hover:underline">Login</a>
        </p>
    </div>
</section>
@endsection
