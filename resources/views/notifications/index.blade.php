@extends('layouts.dashboard')
@section('title', 'Notifications')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">🔔 Notifications</h1>
        <p class="text-ink-900/60 mt-1">{{ auth()->user()->unreadNotifications->count() }} unread</p>
    </div>
    @if(auth()->user()->unreadNotifications->count() > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
            <button class="px-4 py-2 bg-coral-500 text-white rounded-lg text-sm font-semibold">Mark all read</button>
        </form>
    @endif
</div>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    @forelse($notifications as $n)
        @php
            $isRead = !is_null($n->read_at);
            $data = $n->data;
        @endphp
        <a href="{{ $data['url'] ?? '#' }}" class="flex items-start gap-4 p-5 border-b border-ink-900/5 last:border-0 hover:bg-cream transition {{ !$isRead ? 'bg-coral-50' : '' }}">
            <div class="text-3xl">{{ $data['icon'] ?? '🔔' }}</div>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start gap-3">
                    <div class="font-display font-bold text-lg">{{ $data['title'] ?? 'Notification' }}</div>
                    @if(!$isRead)
                        <span class="flex-shrink-0 w-2 h-2 rounded-full bg-coral-500 mt-2"></span>
                    @endif
                </div>
                <p class="text-sm text-ink-900/70 mt-1">{{ $data['message'] ?? '' }}</p>
                <p class="text-xs text-ink-900/40 mt-2">{{ $n->created_at->diffForHumans() }}</p>
            </div>
        </a>
    @empty
        <div class="p-16 text-center">
            <div class="text-6xl mb-4">📬</div>
            <p class="font-display font-bold text-xl">No notifications yet</p>
            <p class="text-sm text-ink-900/60 mt-2">When you get matched leads, payments, or alerts, they'll show up here.</p>
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $notifications->links() }}</div>

@endsection