@extends('layouts.dashboard')
@section('title', 'My Profile')
@section('content')

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="font-display font-black text-3xl">My Profile</h1>
        <p class="text-ink-900/60 mt-1">Manage your account details, photo, and password.</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Photo -->
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-bold text-lg mb-4">Profile Photo</h2>
        <form method="POST" action="{{ route('account.photo') }}" enctype="multipart/form-data" class="flex items-center gap-5">
            @csrf
            <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                 class="w-20 h-20 rounded-full object-cover border-2 border-ink-900/10" alt="Avatar">
            <div class="flex-1">
                <input type="file" name="avatar" accept="image/*" required class="text-sm">
                <p class="text-xs text-ink-900/50 mt-1">JPG or PNG, max 2MB.</p>
            </div>
            <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg font-bold text-sm">Upload</button>
        </form>
    </div>

    <!-- Basic details -->
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-bold text-lg mb-4">Basic Details</h2>
        <form method="POST" action="{{ route('account.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Phone</label>
                    <input type="tel" name="phone" maxlength="10" value="{{ old('phone', $user->phone) }}" required
                           class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Address</label>
                <textarea name="address" rows="2"
                          class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">{{ old('address', $user->address) }}</textarea>
            </div>

            <button class="px-6 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg font-bold text-sm">Save changes</button>
        </form>
    </div>

    <!-- Password -->
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-bold text-lg mb-4">Change Password</h2>
        <form method="POST" action="{{ route('account.password') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-ink-900/70 mb-1.5">New Password</label>
                    <input type="password" name="new_password" minlength="6" required
                           class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" minlength="6" required
                           class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
                </div>
            </div>

            <button class="px-6 py-2.5 bg-ink-950 text-cream rounded-lg font-bold text-sm">Change password</button>
        </form>
    </div>

</div>

@endsection