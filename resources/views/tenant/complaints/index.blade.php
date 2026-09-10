@extends('layouts.tenant')
@section('title', 'My Complaints')
@section('content')

<div class="flex justify-between items-center flex-wrap gap-3">
    <div>
        <h1 class="font-display font-black text-3xl text-ink-950">My Complaints</h1>
        <p class="text-ink-900/60 mt-1">Track all your complaints aur status</p>
    </div>
    <a href="{{ route('tenant.complaints.create') }}" class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
        + New Complaint
    </a>
</div>

<div class="mt-6 space-y-3">
    @forelse($complaints as $c)
        <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
            <div class="flex justify-between items-start gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs px-2 py-0.5 bg-ink-100 text-ink-700 rounded-full uppercase font-bold">{{ $c->category ?? 'general' }}</span>
                        @if($c->priority === 'urgent')
                            <span class="text-xs px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full uppercase font-bold">🚨 Urgent</span>
                        @elseif($c->priority === 'high')
                            <span class="text-xs px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full uppercase font-bold">High</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-lg">{{ $c->title }}</h3>
                    <p class="text-sm text-ink-900/70 mt-1">{{ $c->description }}</p>
                    <div class="text-xs text-ink-900/40 mt-2">{{ \Carbon\Carbon::parse($c->created_at)->diffForHumans() }}</div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase flex-shrink-0
                    {{ $c->status === 'new' ? 'bg-amber-100 text-amber-700' : '' }}
                    {{ $c->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                    {{ $c->status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : '' }}
                    {{ $c->status === 'closed' ? 'bg-gray-100 text-gray-700' : '' }}
                ">{{ str_replace('_', ' ', $c->status) }}</span>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-ink-900/10 p-12 text-center">
            <div class="text-6xl mb-3">🎉</div>
            <h3 class="font-bold text-lg">No complaints!</h3>
            <p class="text-ink-900/60 text-sm mt-1">Aap settled ho — koi issue nahi.</p>
        </div>
    @endforelse
</div>

@if($complaints->hasPages())
    <div class="mt-6">{{ $complaints->links() }}</div>
@endif

@endsection