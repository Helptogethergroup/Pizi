@extends('layouts.dashboard')
@section('title', 'Notifications')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">🔔 Notifications</h1>
        <p class="text-ink-900/60 mt-1">Your alerts and updates</p>
    </div>
    @if($notifications->count())
        <form method="POST" action="{{ route('notifications.readAll') }}" onsubmit="event.preventDefault(); fetch(this.action, {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(() => location.reload());">
            <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg text-sm">✓ Mark all as read</button>
        </form>
    @endif
</div>

@if($notifications->count())
    <div class="bg-white rounded-2xl border border-ink-100 divide-y divide-ink-900/5 overflow-hidden">
        @foreach($notifications as $n)
            <a href="{{ $n->data['url'] ?? '#' }}"
               class="flex items-start gap-4 p-5 hover:bg-cream transition {{ !$n->read_at ? 'bg-coral-50/50' : '' }}">
                <div class="text-2xl flex-shrink-0">{{ $n->data['icon'] ?? '🔔' }}</div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm">{{ $n->data['title'] ?? ucfirst(str_replace('_', ' ', $n->data['type'] ?? 'Notification')) }}</div>
                    <div class="text-sm text-ink-900/60 mt-0.5">{{ $n->data['message'] ?? '' }}</div>
                    <div class="text-xs text-ink-900/40 mt-1">{{ $n->created_at->diffForHumans() }}</div>
                </div>
                @if(!$n->read_at)
                    <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-coral-500 mt-2"></span>
                @endif
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
@else
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3">🔔</div>
        <h2 class="font-bold text-xl">No notifications yet</h2>
        <p class="text-ink-700 mt-2">You'll see new lead alerts, visit updates and more here.</p>
    </div>
@endif

@endsection
