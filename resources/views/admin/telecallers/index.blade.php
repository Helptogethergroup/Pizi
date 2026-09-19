@extends('layouts.dashboard')
@section('title', 'Telecaller Monitoring — Admin')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl"><i class="fa-solid fa-phone fa-fw"></i> Telecaller Monitoring</h1>
        <p class="text-ink-900/60 mt-1">Every telecaller's daily activity — live, straight from their own dashboard use.</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}"
               class="px-4 py-2 rounded-xl border border-ink-100" onchange="this.form.submit()">
    </form>
</div>

<div class="space-y-4">
    @forelse($rows as $row)
        @php $tc = $row['telecaller']; @endphp
        <div class="bg-white p-5 rounded-2xl border border-ink-100">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <div>
                    <h3 class="font-display font-bold text-lg">{{ $tc->name }}</h3>
                    <p class="text-xs text-ink-900/50">{{ $tc->phone }} · {{ $tc->email }}</p>
                </div>

                <form method="POST" action="{{ route('admin.telecallers.target', $tc) }}" class="flex items-center gap-2 flex-wrap">
                    @csrf @method('PATCH')
                    <label class="text-xs font-bold uppercase text-ink-900/50">Daily target</label>
                    <input type="number" name="daily_call_target" value="{{ $tc->daily_call_target }}"
                           placeholder="{{ config('telecaller.default_daily_call_target', 20) }}"
                           class="w-20 px-2 py-1.5 rounded-lg border border-ink-100 text-sm">
                    <button class="px-3 py-1.5 bg-ink-950 text-cream rounded-lg text-xs font-bold">Save</button>
                </form>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-3">
                <div class="p-3 rounded-xl bg-ink-900 text-cream">
                    <div class="text-xs uppercase opacity-70">Total assigned</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['assigned_total'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-amber-100 text-amber-900">
                    <div class="text-xs uppercase opacity-70">Pending (never called)</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['pending'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-100 text-emerald-900">
                    <div class="text-xs uppercase opacity-70">Attended today</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['attended_today'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-rose-100 text-rose-900">
                    <div class="text-xs uppercase opacity-70">Rejected today</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['rejected_today'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-coral-500 text-white">
                    <div class="text-xs uppercase opacity-70">Converted today</div>
                    <div class="font-display font-black text-2xl mt-1">{{ $row['converted_today'] }}</div>
                </div>
            </div>

            <div class="mb-4">
                <div class="flex justify-between text-xs text-ink-900/50 mb-1">
                    <span>Daily call target progress (tenant leads)</span>
                    <span>{{ $row['attended_today'] }} / {{ $row['target'] }}</span>
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
                        <div class="text-xs text-ink-900/50">Called today</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_called_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-rose-50">
                        <div class="text-xs text-ink-900/50">Rejected today</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_rejected_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-sky-50">
                        <div class="text-xs text-ink-900/50">Registered today</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_registered_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50">
                        <div class="text-xs text-ink-900/50">Listed today</div>
                        <div class="font-display font-black text-xl mt-1">{{ $row['owner_listed_today'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-green-100">
                        <div class="text-xs text-ink-900/50">Paid plan today</div>
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
    <h2 class="font-display font-bold text-xl mb-4">✗ Rejection reasons — {{ $date->format('d M Y') }}</h2>
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
    <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-pen-to-square fa-fw"></i> Recent status updates (live)</h2>
    <p class="text-xs text-ink-900/50 mb-3">Every time a telecaller changes a lead's status or call outcome, it shows up here immediately.</p>
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

@endsection
