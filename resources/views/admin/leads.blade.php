@extends('layouts.dashboard')
@section('title', 'Leads — Admin')
@section('content')
<div class="flex items-center justify-between mb-6">
     <div class="flex items-center gap-3">
        <h1 class="font-display font-black text-3xl">All leads</h1>
        <a href="{{ route('leads.manual.create') }}" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold text-sm">+ Add Manual</a>
    </div>
    <form class="flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Name / phone…" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <select name="status" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All status</option>
            @foreach(['new','contacted','interested','follow_up','visit_scheduled','visit_done','closed_won','closed_lost','junk'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_',' ',$s) }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg">Filter</button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr><th class="px-4 py-3">Lead</th><th>Property</th><th>Status</th><th>Assigned to</th><th>When</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($leads as $lead)
            <tr class="border-t border-ink-900/5">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $lead->name }}</div>
                    <div class="text-xs text-ink-900/50">{{ $lead->phone }} · {{ $lead->source }}</div>
                </td>
                <td class="text-xs">{{ $lead->property?->name ?? 'General inquiry' }}</td>
                <td><span class="px-2 py-1 rounded-full text-xs {{ $lead->statusBadge() }}">{{ str_replace('_',' ',$lead->status) }}</span></td>
                <td class="text-xs">{{ $lead->telecaller?->name ?? '—' }}</td>
                <td class="text-xs text-ink-900/60">{{ $lead->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.leads.assign', $lead) }}" class="flex gap-1">@csrf @method('PATCH')
                        <select name="telecaller_id" class="text-xs px-2 py-1 rounded border border-ink-900/15">
                            <option value="">Assign…</option>
                            @foreach($telecallers as $tc)<option value="{{ $tc->id }}" @selected($lead->assigned_telecaller_id === $tc->id)>{{ $tc->name }}</option>@endforeach
                        </select>
                        <button class="text-xs px-2 py-1 rounded bg-ink-900 text-cream">Set</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $leads->links() }}</div>
@endsection
