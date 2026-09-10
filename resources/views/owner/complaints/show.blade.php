@extends('layouts.dashboard')
@section('title', $complaint->title)
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.complaints.index') }}" class="text-coral-500 font-bold">← Back to complaints</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        {{-- Main Card --}}
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-mono font-bold bg-cream px-2 py-1 rounded">{{ $complaint->ticket_number }}</span>
                    <span class="text-xs bg-cream px-2 py-1 rounded-full font-bold">{{ $complaint->category_label }}</span>
                    
                    @if($complaint->priority === 'urgent')
                        <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded-full font-bold">🔴 Urgent</span>
                    @elseif($complaint->priority === 'high')
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full font-bold">🟠 High</span>
                    @elseif($complaint->priority === 'medium')
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-bold">🟡 Medium</span>
                    @else
                        <span class="text-xs bg-ink-100 text-ink-700 px-2 py-1 rounded-full font-bold">🟢 Low</span>
                    @endif

                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold">{{ $complaint->status_label }}</span>
                </div>
            </div>

            <h1 class="font-display font-black text-2xl mt-2">{{ $complaint->title }}</h1>

            @if($complaint->description)
                <div class="mt-4 p-4 bg-cream rounded-xl">
                    <p class="text-sm text-ink-900 whitespace-pre-line">{{ $complaint->description }}</p>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-ink-100 text-sm">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Reported</div>
                    <div class="font-bold">{{ $complaint->created_at->format('d M, h:i A') }}</div>
                </div>
                @if($complaint->assigned_at)
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Assigned</div>
                    <div class="font-bold">{{ $complaint->assigned_at->format('d M, h:i A') }}</div>
                </div>
                @endif
                @if($complaint->resolved_at)
                <div>
                    <div class="text-xs text-emerald-700 uppercase font-bold">Resolved</div>
                    <div class="font-bold text-emerald-700">{{ $complaint->resolved_at->format('d M, h:i A') }}</div>
                </div>
                <div>
                    <div class="text-xs text-emerald-700 uppercase font-bold">Resolution Time</div>
                    <div class="font-bold text-emerald-700">{{ $complaint->resolution_time }}</div>
                </div>
                @endif
            </div>

            @if($complaint->resolution_notes)
                <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <div class="text-xs font-bold uppercase text-emerald-700 mb-1">Resolution Notes</div>
                    <p class="text-sm text-emerald-900 whitespace-pre-line">{{ $complaint->resolution_notes }}</p>
                </div>
            @endif
        </div>

        {{-- Media --}}
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-lg mb-4">📸 Photos & Videos</h2>

            <form method="POST" action="{{ route('owner.complaints.media', $complaint) }}" enctype="multipart/form-data" class="mb-4 flex gap-2 flex-wrap items-center">
                @csrf
                <input type="file" name="media[]" multiple accept="image/*,video/mp4" class="flex-1 min-w-[200px] text-sm">
                <button class="px-4 py-2 bg-coral-500 text-white rounded-lg text-sm font-bold">📤 Upload</button>
            </form>

            @if($complaint->media->count())
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($complaint->media as $m)
                        <div class="rounded-xl border border-ink-100 overflow-hidden relative group">
                            @if($m->media_type === 'photo')
                                <a href="{{ $m->url }}" target="_blank">
                                    <img src="{{ $m->url }}" class="w-full aspect-square object-cover">
                                </a>
                            @else
                                <video src="{{ $m->url }}" controls class="w-full aspect-square object-cover"></video>
                            @endif
                            <form method="POST" action="{{ route('owner.complaints.media.delete', $m) }}" onsubmit="return confirm('Delete?')" class="absolute top-2 right-2">
                                @csrf @method('DELETE')
                                <button class="w-7 h-7 bg-rose-500 text-white rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition">×</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-ink-500">No media uploaded.</p>
            @endif
        </div>

        {{-- Comments --}}
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-lg mb-4">💬 Comments & Updates ({{ $complaint->comments->count() }})</h2>

            @if($complaint->comments->count())
                <div class="space-y-3 mb-5">
                    @foreach($complaint->comments as $c)
                        <div class="p-3 {{ $c->is_internal ? 'bg-amber-50 border border-amber-200' : 'bg-cream' }} rounded-xl">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-bold text-sm">{{ $c->author_name }}</span>
                                <span class="text-xs text-ink-500 capitalize">({{ $c->author_role }})</span>
                                @if($c->is_internal)
                                    <span class="text-xs bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold">🔒 Internal</span>
                                @endif
                                <span class="text-xs text-ink-500">·</span>
                                <span class="text-xs text-ink-500">{{ $c->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm whitespace-pre-line">{{ $c->comment }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('owner.complaints.comments', $complaint) }}" class="space-y-2">
                @csrf
                <textarea name="comment" required rows="3" placeholder="Add a comment or update..." class="w-full px-4 py-3 rounded-xl border border-ink-200"></textarea>
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="is_internal" value="1" class="rounded">
                        <span>🔒 Internal note (hidden from tenant)</span>
                    </label>
                    <button class="px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg text-sm font-bold">Post Comment</button>
                </div>
            </form>
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 space-y-4">

            {{-- Tenant --}}
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">👤 Tenant</h3>
                <div class="space-y-1 text-sm">
                    <div class="font-bold">{{ $complaint->tenant?->name }}</div>
                    <div class="text-ink-700">📱 {{ $complaint->tenant?->phone }}</div>
                    <div class="text-ink-700">🏠 {{ $complaint->property?->name }}</div>
                    @if($complaint->tenant?->room_number)
                        <div class="text-ink-700">🚪 Room {{ $complaint->tenant->room_number }}</div>
                    @endif
                </div>
                @if($complaint->tenant?->phone)
                    <a href="https://wa.me/91{{ $complaint->tenant->phone }}" target="_blank" class="block mt-3 w-full text-center px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold">💬 WhatsApp Tenant</a>
                @endif
            </div>

            {{-- Status --}}
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Change Status</h3>
                <form method="POST" action="{{ route('owner.complaints.status', $complaint) }}" class="space-y-2">
                    @csrf @method('PATCH')
                    <select name="status" class="w-full px-3 py-2.5 rounded-lg border border-ink-200 text-sm">
                        <option value="open" @selected($complaint->status==='open')>📂 Open</option>
                        <option value="assigned" @selected($complaint->status==='assigned')>👤 Assigned</option>
                        <option value="in_progress" @selected($complaint->status==='in_progress')>⏳ In Progress</option>
                        <option value="resolved" @selected($complaint->status==='resolved')>✅ Resolved</option>
                        <option value="closed" @selected($complaint->status==='closed')>🏁 Closed</option>
                        <option value="cancelled" @selected($complaint->status==='cancelled')>❌ Cancelled</option>
                    </select>
                    <textarea name="resolution_notes" rows="2" placeholder="Resolution notes (optional)" class="w-full px-3 py-2 rounded-lg border border-ink-200 text-sm">{{ $complaint->resolution_notes }}</textarea>
                    <button class="w-full px-4 py-2 bg-ink-950 text-cream rounded-lg text-sm font-bold">Update Status</button>
                </form>
            </div>

            {{-- Priority --}}
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Priority</h3>
                <form method="POST" action="{{ route('owner.complaints.priority', $complaint) }}">
                    @csrf @method('PATCH')
                    <select name="priority" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-lg border border-ink-200 text-sm">
                        <option value="low" @selected($complaint->priority==='low')>🟢 Low</option>
                        <option value="medium" @selected($complaint->priority==='medium')>🟡 Medium</option>
                        <option value="high" @selected($complaint->priority==='high')>🟠 High</option>
                        <option value="urgent" @selected($complaint->priority==='urgent')>🔴 Urgent</option>
                    </select>
                </form>
            </div>

            {{-- Assign --}}
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">👨‍🔧 Assign To</h3>
                <form method="POST" action="{{ route('owner.complaints.assign', $complaint) }}" class="space-y-2">
                    @csrf @method('PATCH')
                    <input name="assigned_to_name" required value="{{ $complaint->assigned_to_name }}" placeholder="Staff/vendor name" class="w-full px-3 py-2 rounded-lg border border-ink-200 text-sm">
                    <input name="assigned_to_phone" value="{{ $complaint->assigned_to_phone }}" placeholder="Phone (optional)" class="w-full px-3 py-2 rounded-lg border border-ink-200 text-sm">
                    <button class="w-full px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-bold">{{ $complaint->assigned_to_name ? 'Reassign' : 'Assign' }}</button>
                </form>
                @if($complaint->assigned_to_phone)
                    <a href="tel:{{ $complaint->assigned_to_phone }}" class="block mt-2 w-full text-center px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-bold">📞 Call Assignee</a>
                @endif
            </div>

            <form method="POST" action="{{ route('owner.complaints.destroy', $complaint) }}" onsubmit="return confirm('Delete this complaint?')">
                @csrf @method('DELETE')
                <button class="w-full px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-bold">🗑️ Delete</button>
            </form>
        </div>
    </div>
</div>

@endsection