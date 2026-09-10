@extends('layouts.dashboard')
@section('title', 'Owner Prospects')
@section('content')

<div class="flex items-center justify-between flex-wrap gap-3 mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">🏠 Owner Prospects</h1>
        <p class="text-ink-900/60 mt-1">PG owners jinhe call karna hai — onboard karo Pizi par.</p>
    </div>
    <a href="{{ route('telecaller.owner-prospects.create') }}" class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">+ Add Owner Prospect</a>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or phone..." class="flex-1 min-w-[180px] px-4 py-2.5 rounded-xl border border-ink-100">
    <select name="stage" class="px-4 py-2.5 rounded-xl border border-ink-100">
        <option value="">All stages</option>
        <option value="pending" @selected(request('stage')=='pending')>⏳ Pending call</option>
        <option value="called" @selected(request('stage')=='called')>📞 Called, not registered yet</option>
        <option value="registered" @selected(request('stage')=='registered')>✅ Registered (free)</option>
        <option value="listed" @selected(request('stage')=='listed')>🏠 Property listed</option>
        <option value="paid" @selected(request('stage')=='paid')>💳 Paid plan</option>
        <option value="not_interested" @selected(request('stage')=='not_interested')>✗ Not interested</option>
    </select>
    <button class="px-4 py-2.5 bg-ink-950 text-cream rounded-xl font-bold">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th>Phone</th>
                <th>Stage</th>
                <th>Last call</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($prospects as $p)
                <tr class="border-t border-ink-900/5">
                    <td class="px-4 py-3 font-semibold">{{ $p->name }}</td>
                    <td>{{ $p->phone }}</td>
                    <td><span class="px-2 py-1 rounded-full text-xs {{ $p->stageBadge() }}">{{ $p->stageLabel() }}</span></td>
                    <td class="text-ink-900/50 text-xs">{{ $p->called_at?->diffForHumans() ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('telecaller.owner-prospects.show', $p) }}" class="text-coral-600 font-bold text-xs">Open →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-8 text-center text-ink-900/50">Abhi koi owner prospect nahi hai. "+ Add Owner Prospect" se shuru karo.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $prospects->links() }}</div>

@endsection
