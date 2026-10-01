@extends('layouts.dashboard')
@section('title', 'Telecaller Monitoring — Admin')
@section('content')

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

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl"><i class="fa-solid fa-phone fa-fw"></i> Telecaller Monitoring</h1>
        <p class="text-ink-900/60 mt-1">Every telecaller's daily activity — live, straight from their own dashboard use.</p>
    </div>
    <button type="button" onclick="document.getElementById('newTelecallerModal').classList.remove('hidden')"
        class="px-4 py-2 bg-coral-500 text-white rounded-xl font-semibold"><i class="fa-solid fa-plus fa-fw"></i> Create Telecaller</button>
</div>

<div class="flex items-center justify-end mb-6">
    <form method="GET" class="flex items-center gap-2 flex-wrap">
        <label class="text-xs font-bold uppercase text-ink-900/50">From</label>
        <input type="date" name="date_from" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}"
               class="px-3 py-2 rounded-xl border border-ink-100 text-sm">
        <label class="text-xs font-bold uppercase text-ink-900/50">To</label>
        <input type="date" name="date_to" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}"
               class="px-3 py-2 rounded-xl border border-ink-100 text-sm">
        <button class="px-4 py-2 bg-ink-900 text-cream rounded-xl text-sm font-semibold">Apply</button>
        <a href="{{ route('admin.telecallers.export', ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]) }}"
           class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold">
            <i class="fa-solid fa-download fa-fw"></i> Download CSV
        </a>
    </form>
</div>

<div class="space-y-4">
    @forelse($rows as $row)
        @php $tc = $row['telecaller']; @endphp
        <div class="bg-white p-5 rounded-2xl border border-ink-100">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <div>
                    <h3 class="font-display font-bold text-lg">{{ $tc->name }}
                        <span class="ml-2 align-middle px-2 py-0.5 rounded-full text-xs font-semibold capitalize
                            {{ $tc->lead_specialization === 'tenant' ? 'bg-blue-100 text-blue-700' : ($tc->lead_specialization === 'owner' ? 'bg-amber-100 text-amber-700' : 'bg-ink-900/5 text-ink-900/60') }}">
                            {{ $tc->lead_specialization === 'both' ? 'tenant + owner' : $tc->lead_specialization }}
                        </span>
                    </h3>
                    <p class="text-xs text-ink-900/50">{{ $tc->phone }} · {{ $tc->email }}</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <form method="POST" action="{{ route('admin.telecallers.target', $tc) }}" class="flex items-center gap-2 flex-wrap">
                        @csrf @method('PATCH')
                        <label class="text-xs font-bold uppercase text-ink-900/50">Daily target</label>
                        <input type="number" name="daily_call_target" value="{{ $tc->daily_call_target }}"
                               placeholder="{{ config('telecaller.default_daily_call_target', 20) }}"
                               class="w-20 px-2 py-1.5 rounded-lg border border-ink-100 text-sm">
                        <button class="px-3 py-1.5 bg-ink-950 text-cream rounded-lg text-xs font-bold">Save</button>
                    </form>
                    <button type="button" onclick="document.getElementById('editTcModal{{ $tc->id }}').classList.remove('hidden')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border border-ink-900/15">Edit</button>
                    <button type="button" onclick="openTcDeleteModal({{ $tc->id }}, '{{ addslashes($tc->name) }}')"
                        class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-bold">Delete</button>
                </div>
            </div>

            {{-- Edit telecaller modal --}}
            <div id="editTcModal{{ $tc->id }}" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl p-8 max-w-md w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="font-display font-bold text-2xl">Edit {{ $tc->name }}</h2>
                        <button type="button" onclick="document.getElementById('editTcModal{{ $tc->id }}').classList.add('hidden')" class="text-2xl">×</button>
                    </div>
                    <form method="POST" action="{{ route('admin.users.update', $tc) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <input type="hidden" name="role" value="telecaller">
                        <input name="name" required value="{{ $tc->name }}" placeholder="Full name" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                        <input name="email" type="email" required value="{{ $tc->email }}" placeholder="Email" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                        <input name="phone" required value="{{ $tc->phone }}" placeholder="Phone" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                        <div>
                            <label class="block text-xs font-bold uppercase text-ink-900/50 mb-1">Leads this telecaller handles</label>
                            <select name="lead_specialization" required class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                                <option value="both" @selected($tc->lead_specialization === 'both')>Both tenant & owner leads</option>
                                <option value="tenant" @selected($tc->lead_specialization === 'tenant')>Tenant leads only</option>
                                <option value="owner" @selected($tc->lead_specialization === 'owner')>Owner leads only</option>
                            </select>
                        </div>
                        <button class="w-full py-3 bg-coral-500 text-white rounded-xl font-bold">Save changes</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-3">
                <div class="p-3 rounded-xl bg-ink-900 text-cream">
                    <div class="text-xs uppercase opacity-70">Total assigned (lifetime)</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['assigned_total'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-amber-100 text-amber-900">
                    <div class="text-xs uppercase opacity-70">Pending (never called)</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['pending'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-100 text-emerald-900">
                    <div class="text-xs uppercase opacity-70">Calls made in range</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['attended_today'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-rose-100 text-rose-900">
                    <div class="text-xs uppercase opacity-70">Rejected in range</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['rejected_today'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-blue-100 text-blue-900">
                    <div class="text-xs uppercase opacity-70">Verified in range</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['verified_in_range'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-coral-500 text-white">
                    <div class="text-xs uppercase opacity-70">Converted in range</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['converted_today'] }}</div>
                </div>
            </div>

            <div class="mb-4">
                <div class="flex justify-between text-xs text-ink-900/50 mb-1">
                    <span>Call target progress for this range (tenant leads)</span>
                    <span>{{ $row['attended_today'] }} / {{ $row['target_for_range'] }}</span>
                </div>
                <div class="h-2 rounded-full bg-ink-100 overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ $row['progress_pct'] }}%"></div>
                </div>
            </div>

            <div class="pt-4 border-t border-ink-100">
                <p class="text-xs font-bold uppercase text-ink-900/50 mb-2"><i class="fa-solid fa-house fa-fw"></i> Owner outreach (B2B onboarding calls)</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-3 rounded-xl bg-ink-50">
                        <div class="text-xs text-ink-900/50">Pending</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_pending'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-blue-50">
                        <div class="text-xs text-ink-900/50">Called in range</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_called_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-rose-50">
                        <div class="text-xs text-ink-900/50">Rejected in range</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_rejected_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-sky-50">
                        <div class="text-xs text-ink-900/50">Registered in range</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_registered_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50">
                        <div class="text-xs text-ink-900/50">Listed in range</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_listed_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-green-100">
                        <div class="text-xs text-ink-900/50">Paid plan in range</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_paid_today'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white p-10 rounded-2xl border border-ink-100 text-center text-ink-900/50">
            No telecaller accounts found.
        </div>
    @endforelse
</div>

@if($rejectionBreakdown->isNotEmpty())
<div class="mt-10">
    <h2 class="font-display font-bold text-xl mb-4">✗ Rejection reasons — {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</h2>
    <div class="flex flex-wrap gap-2">
        @php
            $labels = [
                'too_expensive' => '💰 Too expensive',
                'already_found_pg' => '🏠 Already found a PG',
                'wrong_location' => '📍 Wrong location',
                'not_ready_yet' => '⏳ Not ready yet',
                'no_response_after_interest' => '🔇 Stopped responding',
                'other' => '❓ Other',
            ];
        @endphp
        @foreach($rejectionBreakdown as $reason => $count)
            <div class="px-4 py-2 bg-rose-50 border border-rose-200 rounded-xl text-sm font-semibold text-rose-800">
                {{ $labels[$reason] ?? $reason }}: {{ $count }}
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="mt-10">
    <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-pen-to-square fa-fw"></i> Status updates — {{ $from->format('d M') }} to {{ $to->format('d M Y') }}</h2>
    <p class="text-xs text-ink-900/50 mb-3">Every status/call-outcome change a telecaller made in this date range (most recent 50).</p>
    <div class="bg-white rounded-2xl border border-ink-100 overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-cream text-left">
                <tr>
                    <th class="p-3">Telecaller</th>
                    <th class="p-3">Lead</th>
                    <th class="p-3">Field</th>
                    <th class="p-3">Change</th>
                    <th class="p-3">When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentStatusUpdates as $log)
                    <tr class="border-t border-ink-100/60">
                        <td class="p-3 font-semibold">{{ $log->changedBy?->name ?? 'System' }}</td>
                        <td class="p-3">{{ $log->lead?->name ?? '—' }}</td>
                        <td class="p-3 text-ink-900/60">
                            @if($log->field === 'status') Status
                            @elseif($log->field === 'lead_type') <i class="fa-solid fa-trophy fa-fw"></i> Verification
                            @else Call outcome
                            @endif
                        </td>
                        <td class="p-3">
                            <span class="text-ink-900/50">{{ $log->old_value ? str_replace('_',' ', $log->old_value) : '—' }}</span>
                            →
                            <span class="font-semibold">{{ str_replace('_',' ', $log->new_value) }}</span>
                        </td>
                        <td class="p-3 text-ink-900/50 whitespace-nowrap">{{ $log->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-ink-900/50">No status updates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-10">
    <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-rotate fa-fw"></i> Recent lead reassignments</h2>
    <div class="bg-white rounded-2xl border border-ink-100 overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-cream text-left">
                <tr>
                    <th class="p-3">Lead</th>
                    <th class="p-3">From</th>
                    <th class="p-3">To</th>
                    <th class="p-3">Changed by</th>
                    <th class="p-3">When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentReassignments as $log)
                    <tr class="border-t border-ink-100/60">
                        <td class="p-3">{{ $log->lead?->name ?? '—' }}</td>
                        <td class="p-3">{{ $log->fromUser?->name ?? '— (new lead)' }}</td>
                        <td class="p-3 font-semibold">{{ $log->toUser?->name ?? '— (unassigned)' }}</td>
                        <td class="p-3 text-ink-900/60">{{ $log->changedBy?->name ?? 'System (auto)' }}</td>
                        <td class="p-3 text-ink-900/50 whitespace-nowrap">{{ $log->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-ink-900/50">No reassignment history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Delete telecaller modal --}}
<div id="deleteTcModal" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full">
        <div class="flex justify-between items-start mb-4">
            <h2 class="font-display font-bold text-2xl text-rose-600">Delete Telecaller</h2>
            <button type="button" onclick="document.getElementById('deleteTcModal').classList.add('hidden')" class="text-2xl">×</button>
        </div>
        <p class="text-sm text-ink-900/70 mb-4">
            You're about to permanently delete <strong id="deleteTcName"></strong>. Their assigned leads will need to be reassigned to someone else — this cannot be undone.
        </p>
        <form id="deleteTcForm" method="POST" class="space-y-3">
            @csrf
            @method('DELETE')
            <button class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold">Yes, Delete Permanently</button>
        </form>
    </div>
</div>

<script>
    function openTcDeleteModal(userId, userName) {
        document.getElementById('deleteTcName').textContent = userName;
        document.getElementById('deleteTcForm').action = '/admin/users/' + userId;
        document.getElementById('deleteTcModal').classList.remove('hidden');
    }
</script>

{{-- Create telecaller modal --}}
<div id="newTelecallerModal" class="hidden fixed inset-0 bg-ink-950/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-6">
            <h2 class="font-display font-bold text-2xl">Create Telecaller</h2>
            <button type="button" onclick="document.getElementById('newTelecallerModal').classList.add('hidden')" class="text-2xl">×</button>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="role" value="telecaller">

            <input name="name" required placeholder="Full name" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            <input name="email" type="email" required placeholder="Email" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            <input name="phone" required placeholder="Phone" class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            <input name="password" type="password" required placeholder="Password" minlength="6"
                class="w-full px-4 py-3 rounded-xl border border-ink-900/15">

            <div>
                <label class="block text-xs font-bold uppercase text-ink-900/50 mb-1">Leads this telecaller handles</label>
                <select name="lead_specialization" required class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
                    <option value="both">Both tenant & owner leads</option>
                    <option value="tenant">Tenant leads only</option>
                    <option value="owner">Owner leads only</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-ink-900/50 mb-1">Daily call target</label>
                <input name="daily_call_target" type="number" min="1" max="500"
                    placeholder="{{ config('telecaller.default_daily_call_target', 20) }} (default)"
                    class="w-full px-4 py-3 rounded-xl border border-ink-900/15">
            </div>

            <button class="w-full py-3 bg-coral-500 text-white rounded-xl font-bold">Create Telecaller</button>
        </form>
    </div>
</div>

@endsection
