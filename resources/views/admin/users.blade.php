@extends('layouts.dashboard')
@section('title', 'Users — Admin')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="font-display font-black text-3xl">Users</h1>
    <button onclick="document.getElementById('newUserModal').classList.remove('hidden')" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold">+ Create user</button>
</div>

<form class="flex gap-2 mb-6">
    <input name="search" value="{{ request('search') }}" placeholder="Search name / email" class="px-3 py-2 rounded-lg border border-ink-900/15">
    <select name="role" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <option value="">All roles</option>
        @foreach(['admin','owner','telecaller','field_executive','guest'] as $r)
            <option value="{{ $r }}" @selected(request('role') === $r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
        @endforeach
    </select>
    <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr><th class="px-4 py-3">Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($users as $u)
            <tr class="border-t border-ink-900/5">
                <td class="px-4 py-3 font-semibold">{{ $u->name }}<div class="text-xs text-ink-900/50">{{ $u->phone }}</div></td>
                <td>{{ $u->email }}</td>
                <td><span class="px-2 py-1 rounded-full text-xs bg-ink-900/5 capitalize">{{ str_replace('_',' ',$u->role) }}</span></td>
                <td>{!! $u->is_active ? '<span class="text-emerald-600 font-semibold">Active</span>' : '<span class="text-rose-600 font-semibold">Disabled</span>' !!}</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="inline">@csrf @method('PATCH')
                        <button class="text-xs px-2 py-1 rounded border border-ink-900/15">{{ $u->is_active ? 'Disable' : 'Enable' }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $users->links() }}</div>

{{-- Create user modal --}}
<div id="newUserModal" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full">
        <div class="flex justify-between items-start mb-6">
            <h2 class="font-display font-bold text-2xl">Create user</h2>
            <button onclick="document.getElementById('newUserModal').classList.add('hidden')" class="text-2xl">×</button>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">
            @csrf
            <input name="name" required placeholder="Name" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            <input name="email" type="email" required placeholder="Email" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            <input name="phone" required placeholder="Phone" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            <select name="role" required class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                <option value="telecaller">Tele-caller</option>
                <option value="field_executive">Field Executive</option>
                <option value="owner">Owner</option>
                <option value="admin">Admin</option>
            </select>
            <input name="password" type="password" required placeholder="Password" minlength="6" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            <button class="w-full py-3 bg-coral-500 text-white rounded-xl font-bold">Create</button>
        </form>
    </div>
</div>

@endsection
