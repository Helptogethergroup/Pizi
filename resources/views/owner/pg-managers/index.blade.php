@extends('layouts.dashboard')
@section('title', 'PG Managers — Owner')
@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-display font-black text-3xl">PG Managers</h1>
            <p class="text-ink-900/60 mt-1">Create manager accounts and assign them to specific PGs.</p>
        </div>
        <button onclick="document.getElementById('newManagerModal').classList.remove('hidden')"
            class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold">+ Create Manager</button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3 rounded-xl">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th>Email / Phone</th>
                    <th>Assigned PGs</th>
                    <th>Access</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($managers as $m)
                    <tr class="border-t border-ink-900/5">
                        <td class="px-4 py-3 font-semibold">{{ $m->name }}</td>
                        <td>{{ $m->email }}<div class="text-xs text-ink-900/50">{{ $m->phone }}</div></td>
                        <td>
                            @forelse($m->property_names as $pname)
                                <span class="text-xs bg-ink-900/5 px-2 py-1 rounded-full inline-block mb-1">{{ $pname }}</span>
                            @empty
                                <span class="text-xs text-ink-900/40">No PG assigned</span>
                            @endforelse
                        </td>
                        <td>
                            @forelse($m->feature_list as $fkey)
                                <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-full inline-block mb-1">{{ $availableFeatures[$fkey] ?? $fkey }}</span>
                            @empty
                                <span class="text-xs text-ink-900/40">No access granted</span>
                            @endforelse
                        </td>
                        <td>
                            <span class="text-xs px-2 py-1 rounded-full {{ $m->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $m->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 flex gap-1.5 flex-wrap py-3">
                            <button type="button" onclick="openEditModal({{ $m->id }}, '{{ addslashes($m->name) }}', {{ json_encode($m->property_ids) }}, {{ json_encode($m->feature_list) }})"
                                class="text-xs px-3 py-1.5 rounded bg-ink-100 text-ink-950 font-semibold">Edit PGs</button>
                            <form method="POST" action="{{ route('owner.pg-managers.toggle', $m) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="text-xs px-3 py-1.5 rounded border border-ink-900/15">{{ $m->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="{{ route('owner.pg-managers.destroy', $m) }}" class="inline" onsubmit="return confirm('Remove this manager?')">
                                @csrf @method('DELETE')
                                <button class="text-xs px-3 py-1.5 rounded bg-rose-600 text-white font-semibold">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-ink-900/50">No PG Managers yet. Create one to delegate day-to-day PG handling.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create Manager Modal --}}
    <div id="newManagerModal" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-8 max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-4">
                <h2 class="font-display font-bold text-2xl">Create PG Manager</h2>
                <button onclick="document.getElementById('newManagerModal').classList.add('hidden')" class="text-2xl">×</button>
            </div>
            <form method="POST" action="{{ route('owner.pg-managers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Manager's Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2.5 border border-ink-900/15 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email *</label>
                    <input type="email" name="email" required class="w-full px-4 py-2.5 border border-ink-900/15 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone *</label>
                    <input type="tel" name="phone" required maxlength="10" class="w-full px-4 py-2.5 border border-ink-900/15 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password *</label>
                    <input type="password" name="password" required minlength="6" class="w-full px-4 py-2.5 border border-ink-900/15 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Assign PGs (select at least one) *</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-ink-900/10 rounded-xl p-3">
                        @forelse($myProperties as $p)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="property_ids[]" value="{{ $p->id }}">
                                {{ $p->name }}
                            </label>
                        @empty
                            <p class="text-sm text-ink-900/50">You have no properties yet. Add a property first.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">What can this manager access?</label>
                    <div class="space-y-2 border border-ink-900/10 rounded-xl p-3">
                        @foreach($availableFeatures as $key => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="features[]" value="{{ $key }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-ink-900/50 mt-1">Wallet, Buy Credits and creating other PG Managers are never available to managers.</p>
                </div>
                <button class="w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Create Manager Account</button>
            </form>
        </div>
    </div>

    {{-- Edit Assigned PGs Modal --}}
    <div id="editManagerModal" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-8 max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-4">
                <h2 class="font-display font-bold text-2xl">Edit Assigned PGs — <span id="editManagerName"></span></h2>
                <button onclick="document.getElementById('editManagerModal').classList.add('hidden')" class="text-2xl">×</button>
            </div>
            <form id="editManagerForm" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm font-medium mb-2">Assigned PGs</label>
                    <div class="space-y-2 max-h-64 overflow-y-auto border border-ink-900/10 rounded-xl p-3">
                        @forelse($myProperties as $p)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="property_ids[]" value="{{ $p->id }}" class="edit-property-checkbox" data-property-id="{{ $p->id }}">
                                {{ $p->name }}
                            </label>
                        @empty
                            <p class="text-sm text-ink-900/50">No properties available.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Access</label>
                    <div class="space-y-2 border border-ink-900/10 rounded-xl p-3">
                        @foreach($availableFeatures as $key => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="features[]" value="{{ $key }}" class="edit-feature-checkbox" data-feature-key="{{ $key }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button class="w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(managerId, managerName, assignedPropertyIds, assignedFeatures) {
            document.getElementById('editManagerName').textContent = managerName;
            document.getElementById('editManagerForm').action = '/owner/pg-managers/' + managerId;

            document.querySelectorAll('.edit-property-checkbox').forEach(function (cb) {
                cb.checked = assignedPropertyIds.includes(parseInt(cb.dataset.propertyId));
            });

            document.querySelectorAll('.edit-feature-checkbox').forEach(function (cb) {
                cb.checked = assignedFeatures.includes(cb.dataset.featureKey);
            });

            document.getElementById('editManagerModal').classList.remove('hidden');
        }
    </script>
@endsection