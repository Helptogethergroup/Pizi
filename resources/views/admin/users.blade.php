@extends('layouts.dashboard')
@section('title', 'Users — Admin')
@section('content')

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}"
            class="bg-white rounded-xl p-4 border-2 {{ !request('role') ? 'border-ink-900' : 'border-ink-900/10' }} hover:border-ink-900 transition">
            <p class="text-xs text-ink-900/50 uppercase font-bold">Total Users</p>
            <p class="text-2xl font-black mt-1">{{ $totalUsers }}</p>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'owner']) }}"
            class="bg-white rounded-xl p-4 border-2 {{ request('role') === 'owner' ? 'border-blue-600' : 'border-ink-900/10' }} hover:border-blue-600 transition">
            <p class="text-xs text-ink-900/50 uppercase font-bold">Owners</p>
            <p class="text-2xl font-black mt-1 text-blue-600">{{ $roleCounts['owner'] ?? 0 }}</p>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'tenant']) }}"
            class="bg-white rounded-xl p-4 border-2 {{ request('role') === 'tenant' ? 'border-emerald-600' : 'border-ink-900/10' }} hover:border-emerald-600 transition">
            <p class="text-xs text-ink-900/50 uppercase font-bold">Tenants</p>
            <p class="text-2xl font-black mt-1 text-emerald-600">{{ $roleCounts['tenant'] ?? 0 }}</p>
        </a>
      <a href="{{ route('admin.users.index', ['role' => 'others']) }}"
            class="bg-white rounded-xl p-4 border-2 {{ request('role') === 'others' ? 'border-amber-600' : 'border-ink-900/10' }} hover:border-amber-600 transition">
            <p class="text-xs text-ink-900/50 uppercase font-bold">Others (Staff)</p>
            <p class="text-2xl font-black mt-1 text-amber-600">{{ $totalUsers - ($roleCounts['owner'] ?? 0) - ($roleCounts['tenant'] ?? 0) }}</p>
        </a>
    </div>
    
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-black text-3xl">Users</h1>
        <button onclick="document.getElementById('newUserModal').classList.remove('hidden')"
            class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold">+ Create user</button>
    </div>

    <form class="flex flex-col sm:flex-row gap-2 mb-6">
        <input name="search" value="{{ request('search') }}" placeholder="Search name / email"
            class="px-3 py-2 rounded-lg border border-ink-900/15 w-full sm:w-auto sm:flex-1 min-w-0">
        <select name="role" class="px-3 py-2 rounded-lg border border-ink-900/15 w-full sm:w-auto min-w-0">
            <option value="">All roles</option>
           @foreach(['admin', 'owner', 'tenant', 'telecaller', 'field_executive', 'seo_manager', 'guest'] as $r)
                <option value="{{ $r }}" @selected(request('role') === $r)>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg w-full sm:w-auto">Filter</button>
    </form>

    <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th>Email</th>
                     <th>Role</th>
                    <th>PGs</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr class="border-t border-ink-900/5">
                        <td class="px-4 py-3 font-semibold">{{ $u->name }}
                            <div class="text-xs text-ink-900/50">{{ $u->phone }}</div>
                        </td>
                        <td>{{ $u->email }}</td>
                                              <td><span
                                class="px-2 py-1 rounded-full text-xs bg-ink-900/5 capitalize">{{ str_replace('_', ' ', $u->role) }}</span>
                            @if($u->role === 'telecaller')
                                <span class="px-2 py-1 rounded-full text-xs font-semibold capitalize
                                    {{ $u->lead_specialization === 'tenant' ? 'bg-blue-100 text-blue-700' : ($u->lead_specialization === 'owner' ? 'bg-amber-100 text-amber-700' : 'bg-ink-900/5 text-ink-900/60') }}">
                                    {{ $u->lead_specialization === 'both' ? 'tenant + owner' : $u->lead_specialization }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($u->role === 'owner' && $u->properties_count > 0)
                                <a href="{{ route('admin.properties.index', ['owner' => $u->id]) }}"
                                   class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700 font-bold hover:bg-blue-200">
                                    <i class="fa-solid fa-house fa-fw"></i> {{ $u->properties_count }}
                                </a>
                            @elseif($u->role === 'owner')
                                <span class="text-xs text-ink-900/40">0 PGs</span>
                            @else
                                <span class="text-xs text-ink-900/20">—</span>
                            @endif
                        </td>
                        <td>{!! $u->is_active ? '<span class="text-emerald-600 font-semibold">Active</span>' : '<span class="text-rose-600 font-semibold">Disabled</span>' !!}
                        </td>
                       <td class="px-4 py-3 flex gap-2">
                            <a href="{{ route('admin.users.show', $u) }}" class="text-xs px-2 py-1 rounded bg-ink-900 text-cream font-semibold">
                                View
                            </a>
                       <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="inline">@csrf
                                @method('PATCH')
                                <button
                                    class="text-xs px-2 py-1 rounded border border-ink-900/15">{{ $u->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            @php
                                $looksLikeTest = $u->is_test_account
                                    || stripos($u->name, 'test') !== false
                                    || stripos($u->email, 'test') !== false;
                            @endphp
                            @if($u->role === 'owner' && $looksLikeTest)
                            <form method="POST" action="{{ route('admin.users.toggle-test', $u) }}" class="inline">@csrf
                                @method('PATCH')
                                <button
                                    title="Test accounts' lead unlocks never block real owners"
                                    class="text-xs px-2 py-1 rounded font-semibold {{ $u->is_test_account ? 'bg-amber-500 text-white' : 'border border-ink-900/15' }}"><i class="fa-solid fa-flask fa-fw"></i> {{ $u->is_test_account ? 'Test' : 'Mark Test' }}</button>
                            </form>
                            @endif
                            <button type="button" onclick="openDeleteModal({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                class="text-xs px-2 py-1 rounded bg-rose-600 text-white font-semibold">Delete</button>
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
                <button onclick="document.getElementById('newUserModal').classList.add('hidden')"
                    class="text-2xl">×</button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">
                @csrf
                <input name="name" required placeholder="Name" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                <input name="email" type="email" required placeholder="Email"
                    class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                <input name="phone" required placeholder="Phone"
                    class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                <select name="role" id="newUserRole" required class="w-full px-4 py-3 rounded-xl border border-ink-900/15" onchange="document.getElementById('newUserSpecWrap').classList.toggle('hidden', this.value !== 'telecaller')">
                    <option value="telecaller">Tele-caller</option>
                    <option value="field_executive">Field Executive</option>
                    <option value="owner">Owner</option>
                    <option value="tenant">Tenant</option>
                    <option value="seo_manager">SEO Manager</option>
                    <option value="admin">Admin</option>
                </select>
                <div id="newUserSpecWrap">
                    <label class="block text-xs font-bold uppercase text-ink-900/50 mb-1">Leads this telecaller handles</label>
                    <select name="lead_specialization" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                        <option value="both">Both tenant & owner leads</option>
                        <option value="tenant">Tenant leads only</option>
                        <option value="owner">Owner leads only</option>
                    </select>
                </div>
                <input name="password" type="password" required placeholder="Password" minlength="6"
                    class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                <button class="w-full py-3 bg-coral-500 text-white rounded-xl font-bold">Create</button>
            </form>
        </div>
    </div>
    
    {{-- Delete user modal --}}
    <div id="deleteUserModal" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full">
            <div class="flex justify-between items-start mb-4">
                <h2 class="font-display font-bold text-2xl text-rose-600">Delete User</h2>
                <button onclick="document.getElementById('deleteUserModal').classList.add('hidden')" class="text-2xl">×</button>
            </div>
            <p class="text-sm text-ink-900/70 mb-4">
                You're about to permanently delete <strong id="deleteUserName"></strong> from the database. This cannot be undone.
            </p>
            <form id="deleteUserForm" method="POST" class="space-y-3">
                @csrf
                @method('DELETE')
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="force" value="1" class="rounded">
                    Force delete (unlink any properties/tenants owned by this user)
                </label>
                <button class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold">Yes, Delete Permanently</button>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(userId, userName) {
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteUserForm').action = '/admin/users/' + userId;
            document.getElementById('deleteUserModal').classList.remove('hidden');
        }
    </script>

@endsection