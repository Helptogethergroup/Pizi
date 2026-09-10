@extends('layouts.dashboard')
@section('title', 'Complaints')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">Complaints & Maintenance</h1>
        <p class="text-ink-900/60 mt-1">Track and resolve tenant complaints</p>
    </div>
    <a href="{{ route('owner.complaints.create') }}" class="px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-coral-500/30">+ New Complaint</a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
</div>

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Title, ticket, tenant name..." class="flex-1 min-w-[200px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
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
    <select name="category" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Categories</option>
        <option value="plumbing">🚿 Plumbing</option>
        <option value="electrical">⚡ Electrical</option>
        <option value="wifi">📶 WiFi</option>
        <option value="housekeeping">🧹 Housekeeping</option>
        <option value="food">🍱 Food</option>
        <option value="furniture">🪑 Furniture</option>
        <option value="security">🔒 Security</option>
        <option value="ac">❄️ AC</option>
        <option value="water">💧 Water</option>
        <option value="other">Other</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>
</form>

@if($complaints->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3">🛠️</div>
        <p class="text-ink-700 mb-4">No complaints registered yet.</p>
        <a href="{{ route('owner.complaints.create') }}" class="inline-block px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ Register First Complaint</a>
    </div>
@else
    <div class="space-y-3">
        @foreach($complaints as $complaint)
            <a href="{{ route('owner.complaints.show', $complaint) }}" class="block bg-white p-4 rounded-2xl border border-ink-100 hover:border-coral-300 transition">
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

                            @if($complaint->status === 'resolved' || $complaint->status === 'closed')
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">{{ $complaint->status_label }}</span>
                            @elseif($complaint->status === 'in_progress')
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">{{ $complaint->status_label }}</span>
                            @elseif($complaint->status === 'assigned')
                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-bold">{{ $complaint->status_label }}</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">{{ $complaint->status_label }}</span>
                            @endif
                        </div>

                        <h3 class="font-bold text-ink-950">{{ $complaint->title }}</h3>

                        <div class="flex items-center gap-3 text-xs text-ink-700 mt-1 flex-wrap">
                            <span>👤 {{ $complaint->tenant?->name }}</span>
                            @if($complaint->tenant?->room_number)<span>🚪 Room {{ $complaint->tenant->room_number }}</span>@endif
                            <span>📅 {{ $complaint->created_at->diffForHumans() }}</span>
                            @if($complaint->assigned_to_name)
                                <span class="text-purple-700 font-bold">→ {{ $complaint->assigned_to_name }}</span>
                            @endif
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