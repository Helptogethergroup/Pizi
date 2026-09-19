@extends('layouts.dashboard')
@section('title', $complaint->title . ' — Admin')
@section('content')

<div class="mb-6">
    <a href="{{ route('admin.complaints.index') }}" class="text-coral-500 font-bold">← Back to all complaints</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-center gap-2 flex-wrap mb-3">
                <span class="text-xs font-mono font-bold bg-cream px-2 py-1 rounded">{{ $complaint->ticket_number }}</span>
                <span class="text-xs bg-cream px-2 py-1 rounded-full font-bold">{{ $complaint->category_label }}</span>

                @if($complaint->priority === 'urgent')
                    <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-circle fa-fw" style="color:#ef4444"></i> Urgent</span>
                @elseif($complaint->priority === 'high')
                    <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-circle fa-fw" style="color:#f97316"></i> High</span>
                @elseif($complaint->priority === 'medium')
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-circle fa-fw" style="color:#eab308"></i> Medium</span>
                @else
                    <span class="text-xs bg-ink-100 text-ink-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-circle fa-fw" style="color:#22c55e"></i> Low</span>
                @endif

                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold">{{ $complaint->status_label }}</span>
            </div>

            <h1 class="font-display font-black text-2xl">{{ $complaint->title }}</h1>

            @if($complaint->description)
                <div class="mt-4 p-4 bg-cream rounded-xl">
                    <p class="text-sm whitespace-pre-line">{{ $complaint->description }}</p>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-ink-100 text-sm">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Tenant</div>
                    <div class="font-bold">{{ $complaint->tenant?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Owner</div>
                    <div class="font-bold">{{ $complaint->owner?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Property</div>
                    <div class="font-bold">{{ $complaint->property?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Reported</div>
                    <div class="font-bold">{{ $complaint->created_at->format('d M, h:i A') }}</div>
                </div>
            </div>

            @if($complaint->resolution_notes)
                <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <div class="text-xs font-bold uppercase text-emerald-700 mb-1">Resolution</div>
                    <p class="text-sm text-emerald-900 whitespace-pre-line">{{ $complaint->resolution_notes }}</p>
                </div>
            @endif
        </div>

        @if($complaint->media->count())
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-camera fa-fw"></i> Media</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($complaint->media as $m)
                    <a href="{{ $m->url }}" target="_blank" class="rounded-xl border border-ink-100 overflow-hidden">
                        @if($m->media_type === 'photo')
                            <img src="{{ $m->url }}" class="w-full aspect-square object-cover">
                        @else
                            <video src="{{ $m->url }}" controls class="w-full aspect-square object-cover"></video>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-comment-dots fa-fw"></i> Comments ({{ $complaint->comments->count() }})</h2>

            @if($complaint->comments->count())
                <div class="space-y-3 mb-5">
                    @foreach($complaint->comments as $c)
                        <div class="p-3 {{ $c->is_internal ? 'bg-amber-50 border border-amber-200' : 'bg-cream' }} rounded-xl">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-bold text-sm">{{ $c->author_name }}</span>
                                <span class="text-xs text-ink-500 capitalize">({{ $c->author_role }})</span>
                                @if($c->is_internal)
                                    <span class="text-xs bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-lock fa-fw"></i> Internal</span>
                                @endif
                                <span class="text-xs text-ink-500">{{ $c->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm whitespace-pre-line">{{ $c->comment }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.complaints.comments', $complaint) }}" class="space-y-2">
                @csrf
                <textarea name="comment" required rows="3" placeholder="Add admin comment..." class="w-full px-4 py-3 rounded-xl border border-ink-200"></textarea>
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="is_internal" value="1" class="rounded">
                        <span><i class="fa-solid fa-lock fa-fw"></i> Internal note</span>
                    </label>
                    <button class="px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg text-sm font-bold">Post as Admin</button>
                </div>
            </form>
        </div>

    </div>

    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 space-y-4">

            <div class="bg-amber-50 p-5 rounded-2xl border border-amber-200">
                <h3 class="font-display font-bold text-amber-900 mb-3"><i class="fa-solid fa-bolt fa-fw"></i> Admin Override</h3>
                <form method="POST" action="{{ route('admin.complaints.status', $complaint) }}" class="space-y-2 mb-3">
                    @csrf @method('PATCH')
                    <label class="text-xs font-bold uppercase text-amber-900">Force Status</label>
                    <select name="status" class="w-full px-3 py-2.5 rounded-lg border border-amber-200 text-sm">
                        <option value="open" @selected($complaint->status==='open')>Open</option>
                        <option value="assigned" @selected($complaint->status==='assigned')>Assigned</option>
                        <option value="in_progress" @selected($complaint->status==='in_progress')>In Progress</option>
                        <option value="resolved" @selected($complaint->status==='resolved')>Resolved</option>
                        <option value="closed" @selected($complaint->status==='closed')>Closed</option>
                        <option value="cancelled" @selected($complaint->status==='cancelled')>Cancelled</option>
                    </select>
                    <textarea name="resolution_notes" rows="2" placeholder="Resolution notes" class="w-full px-3 py-2 rounded-lg border border-amber-200 text-sm">{{ $complaint->resolution_notes }}</textarea>
                    <button class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-bold">Update</button>
                </form>

                <form method="POST" action="{{ route('admin.complaints.priority', $complaint) }}" class="mb-3">
                    @csrf @method('PATCH')
                    <label class="text-xs font-bold uppercase text-amber-900">Force Priority</label>
                    <select name="priority" onchange="this.form.submit()" class="w-full mt-1 px-3 py-2.5 rounded-lg border border-amber-200 text-sm">
                        <option value="low" @selected($complaint->priority==='low')>Low</option>
                        <option value="medium" @selected($complaint->priority==='medium')>Medium</option>
                        <option value="high" @selected($complaint->priority==='high')>High</option>
                        <option value="urgent" @selected($complaint->priority==='urgent')>Urgent</option>
                    </select>
                </form>

                <form method="POST" action="{{ route('admin.complaints.destroy', $complaint) }}" onsubmit="return confirm('Delete complaint?')">
                    @csrf @method('DELETE')
                    <button class="w-full px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete</button>
                </form>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Contact</h3>
                <div class="space-y-2">
                    <a href="https://wa.me/91{{ $complaint->tenant?->phone }}" target="_blank" class="block w-full text-center px-4 py-2.5 bg-emerald-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp Tenant</a>
                    <a href="tel:{{ $complaint->owner?->phone }}" class="block w-full text-center px-4 py-2.5 bg-purple-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call Owner</a>
                    @if($complaint->assigned_to_phone)
                        <a href="tel:{{ $complaint->assigned_to_phone }}" class="block w-full text-center px-4 py-2.5 bg-blue-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call {{ $complaint->assigned_to_name }}</a>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@endsection