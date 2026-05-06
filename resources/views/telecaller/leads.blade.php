@extends('layouts.dashboard')
@section('title', 'My Leads')
@section('content')
<div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <h1 class="font-display font-black text-3xl">My leads</h1>
        <a href="{{ route('leads.manual.create') }}" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold text-sm">+ Add Lead</a>
    </div>
    <form class="flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Name / phone…" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <select name="status" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All</option>
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
            <tr><th class="px-4 py-3">Lead</th><th>Property</th><th>Status</th><th>Last contact</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($leads as $lead)
            <tr class="border-t border-ink-900/5 hover:bg-ink-900/5">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $lead->name }}</div>
                    <div class="text-xs text-ink-900/50">📞 {{ $lead->phone }}</div>
                </td>
                <td class="text-xs">{{ $lead->property?->name ?? 'General' }}</td>
                <td><span class="px-2 py-1 rounded-full text-xs {{ $lead->statusBadge() }}">{{ str_replace('_',' ',$lead->status) }}</span></td>
                <td class="text-xs text-ink-900/60">{{ $lead->last_contacted_at?->diffForHumans() ?? '—' }}</td>
                <td class="px-4 py-3 space-x-1">
                    <a href="{{ route('telecaller.leads.show', $lead) }}" class="text-xs px-3 py-1.5 rounded bg-ink-900 text-cream font-semibold">Open</a>
                    <a href="tel:{{ $lead->phone }}" class="text-xs px-3 py-1.5 rounded bg-emerald-500 text-white font-semibold">📞 Call</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $leads->links() }}</div>
@endsection
