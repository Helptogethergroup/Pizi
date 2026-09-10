@extends('layouts.app')
@section('title', 'Complete Registration — Pizi')
@section('content')

<div class="min-h-[80vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="font-display font-black text-4xl text-ink-950">Complete signup</h1>
            <p class="text-ink-700 mt-2">Just a few more details</p>
        </div>

        <div class="bg-white rounded-2xl p-8 border border-ink-100 shadow-xl shadow-ink-950/5">

            @if($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-emerald-50 border border-emerald-200 px-4 py-3 rounded-xl text-sm mb-4">
                ✓ Verified: <strong>{{ $identifier }}</strong>
            </div>

            <form method="POST" action="{{ route('register.complete.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Full name *</label>
                    <input name="name" required value="{{ old('name') }}" placeholder="Your full name" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                </div>

                @if($type === 'phone')
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Email (optional)</label>
                    <input name="email" type="email" placeholder="you@email.com" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                </div>
                @else
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Mobile number *</label>
                    <input name="phone" required placeholder="9876543210" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                </div>
                @endif

                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Password *</label>
                    <input name="password" type="password" required minlength="6" placeholder="At least 6 characters" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                </div>

                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">I am a *</label>
                    <select name="role" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                        <option value="guest">🏠 Looking for PG (Tenant)</option>
                        <option value="owner">👤 PG Owner</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-base shadow-lg shadow-coral-500/30">
                    Create Account →
                </button>
            </form>
        </div>
    </div>
</div>

@endsection