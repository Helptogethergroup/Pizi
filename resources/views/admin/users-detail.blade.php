@extends('layouts.dashboard')
@section('title', 'User — ' . $user->name)
@section('content')

<a href="{{ route('admin.users.index') }}" class="text-coral-600 font-semibold text-sm mb-4 inline-block">← Back to Users</a>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm mb-4">
        {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm mb-4">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <div class="md:col-span-2 space-y-6">

        <!-- Edit details -->
        <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
            <h2 class="font-bold text-lg mb-4">Edit User Details</h2>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required
                               class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Role</label>
                    <select name="role" id="editUserRole" required class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg" onchange="document.getElementById('editUserSpecWrap').classList.toggle('hidden', this.value !== 'telecaller')">
                        @foreach(['telecaller', 'field_executive', 'owner', 'seo_manager', 'admin', 'tenant'] as $r)
                            <option value="{{ $r }}" @selected($user->role === $r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="editUserSpecWrap" class="{{ $user->role === 'telecaller' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-ink-900/70 mb-1.5">Leads this telecaller handles</label>
                    <select name="lead_specialization" class="w-full px-4 py-2.5 border border-ink-900/15 rounded-lg">
                        <option value="both" @selected($user->lead_specialization === 'both')>Both tenant & owner leads</option>
                        <option value="tenant" @selected($user->lead_specialization === 'tenant')>Tenant leads only</option>
                        <option value="owner" @selected($user->lead_specialization === 'owner')>Owner leads only</option>
                    </select>
                </div>

                <button class="px-6 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg font-bold text-sm">Save changes</button>
            </form>
        </div>

        <!-- Reset password -->
        <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
            <h2 class="font-bold text-lg mb-2">Reset Password</h2>
            <p class="text-xs text-ink-900/50 mb-4">
                <i class="fa-solid fa-lock fa-fw"></i> For security, existing passwords are encrypted and cannot be viewed by anyone — including admins.
                You can only set a new one below.
            </p>
            <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                @method('PATCH')
                <input type="password" name="new_password" required minlength="6" placeholder="New password (min 6 chars)"
                       class="flex-1 min-w-0 px-4 py-2.5 border border-ink-900/15 rounded-lg">
                <button class="px-6 py-2.5 bg-ink-950 text-cream rounded-lg font-bold text-sm whitespace-nowrap">Reset password</button>
            </form>
        </div>

    </div>

    <!-- Activity log -->
    <div>
        <div class="bg-white rounded-2xl border border-ink-900/10 p-6 sticky top-6">
            <h2 class="font-bold text-lg mb-1">Self-made Changes</h2>
            <p class="text-3xl font-black text-coral-600 mb-4">{{ $changeCount }}</p>
            <p class="text-xs text-ink-900/50 mb-4">Times this user has updated their own profile or password.</p>

            <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                @forelse($activity as $a)
                    <div class="border-l-2 border-ink-900/10 pl-3">
                        <p class="text-xs text-ink-900/40">{{ \Carbon\Carbon::parse($a->created_at)->format('d M Y, h:i A') }}</p>
                        <p class="text-sm font-semibold text-ink-900">{{ $a->description }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-900/40">No self-made changes yet.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>

@endsection