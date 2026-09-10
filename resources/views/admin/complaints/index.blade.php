@extends('layouts.dashboard')
@section('title', 'All Complaints — Admin')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl">🛠️ All Complaints</h1>
    <p class="text-ink-900/60 mt-1">Platform-wide complaint tracking</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-amber-200">
        <div class="text-xs text-amber-700 uppercase font-bold">Open</div>
        <div class="font-display font-black text-3xl text-amber-700 mt-1">{{ $stats['open'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-blue-200">
        <div class="text-xs text-blue-700 uppercase font-bold">In Progress</div>
        <div class="font-display font-black text-3xl text-blue-700 mt-1">{{ $stats['in_progress'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-rose-200">
        <div class="text-xs text-rose-700 uppercase font-bold">Urgent</div>
        <div class="font-display font-black text-3xl text-rose-700 mt-1">{{ $stats['urgent'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-emerald-200">
        <div class="text-xs text-emerald-700 uppercase font-bold">Resolved (Month)</div>
        <div class="font-display font-black text-3xl text-emerald-700 mt-1">{{ $stats['resolved_month'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-ink-100">
        <div class="text-xs text-ink-500 uppercase font-bold">Total</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total'] }}</div>
    </div>
</div>

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Title, ticket, tenant..." class="flex-1 min-w-[180px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <select name="owner_id" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Owners</option>
        @foreach($owners as $o)
            <option value="{{ $o->id }}" @selected(request('owner_id') == $o->id)>{{ $o->name }}</option>
        @endforeach
    </select>
    <select name="status" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Status</option>
        <option value="open" @selected(request('status')==='open')>Open</option>
        <option value="assigned" @selected(request('status')==='assigned')>Assigned</option>
        <option value="in_progress" @selected(request('status')==='in_progress')>In Progress</option>
        <option value="resolved" @selected(request('status')==='resolved')>Resolved</option>
        <option value="closed" @selected(request('status')==='closed')>Closed</option>
    </select>
    <select name="priority" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Priority</option>
        <option value="urgent" @selected(request('priority')==='urgent')>🔴 Urgent</option>
        <option value="high" @selected(request('priority')==='high')>🟠 High</option>
        <option value="medium" @selected(request('priority')==='medium')>🟡 Medium</option>
        <option value="low" @selected(request('priority')==='low')>🟢 Low</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>
</form>

@if($complaints->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3">🛠️</div>
        <p class="text-ink-700">No complaints found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($complaints as $complaint)
            <a href="{{ route('admin.complaints.show', $complaint) }}" class="block bg-white p-4 rounded-2xl border border-ink-100 hover:border-coral-300 transition">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="text-xs font-mono font-bold text-ink-500">{{ $complaint->ticket_number }}</span>
                            <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold">{{ $complaint->category_label }}</span>

                            @if($complaint->priority === 'urgent')
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">🔴 Urgent</span>
                            @elseif($complaint->priority === 'high')
                                <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-bold">🟠 High</span>
                            @elseif($complaint->priority === 'medium')
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">🟡 Medium</span>
                            @else
                                <span class="text-xs bg-ink-100 text-ink-700 px-2 py-0.5 rounded-full font-bold">🟢 Low</span>
                            @endif

                            @if(in_array($complaint->status, ['resolved', 'closed']))
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">{{ $complaint->status_label }}</span>
                            @elseif($complaint->status === 'in_progress')
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">{{ $complaint->status_label }}</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">{{ $complaint->status_label }}</span>
                            @endif
                        </div>
                        <h3 class="font-bold">{{ $complaint->title }}</h3>
                        <div class="flex items-center gap-3 text-xs text-ink-700 mt-1 flex-wrap">
                            <span>👤 {{ $complaint->tenant?->name }}</span>
                            <span>🏠 Owner: <strong>{{ $complaint->owner?->name }}</strong></span>
                            <span>📅 {{ $complaint->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="text-2xl text-ink-300">→</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $complaints->links() }}</div>
@endif

@endsection