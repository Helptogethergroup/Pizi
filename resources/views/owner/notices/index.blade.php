@extends('layouts.dashboard')
@section('title', 'Tenant Notices')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl">Tenant Notices</h1>
    <p class="text-ink-900/60 mt-1">Post a notice and your tenants see it in the Pizi tenant app, with a notification on their phone.</p>
</div>

@if(session('success'))<div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="POST" action="{{ route('owner.notices.store') }}" class="bg-white p-6 rounded-2xl border border-ink-100 mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf
    <div>
        <label class="text-sm font-bold text-ink-700">For which PG</label>
        <select name="property_id" class="mt-1 w-full px-4 py-3 rounded-xl border border-ink-200">
            @if($canPostToAll)<option value="">All my PGs</option>@endif
            @foreach($properties as $p)<option value="{{ $p->id }}" @selected(old('property_id') == $p->id)>{{ $p->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="text-sm font-bold text-ink-700">Type</label>
        <select name="kind" class="mt-1 w-full px-4 py-3 rounded-xl border border-ink-200">
            <option value="notice">Notice</option>
            <option value="urgent">Urgent</option>
            <option value="menu">Food menu</option>
            <option value="rule">House rule</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="text-sm font-bold text-ink-700">Title</label>
        <input name="title" required maxlength="150" value="{{ old('title') }}" placeholder="e.g. Water supply off tomorrow 10am to 2pm" class="mt-1 w-full px-4 py-3 rounded-xl border border-ink-200">
    </div>
    <div class="md:col-span-2">
        <label class="text-sm font-bold text-ink-700">Message</label>
        <textarea name="body" required maxlength="3000" rows="4" class="mt-1 w-full px-4 py-3 rounded-xl border border-ink-200">{{ old('body') }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="pinned" value="1"> Keep at the top</label>
    <div class="md:text-right"><button class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Post notice</button></div>
</form>

<h2 class="font-display font-bold text-xl mb-3">Posted notices ({{ $notices->count() }})</h2>
<div class="space-y-3">
    @forelse($notices as $n)
        <div class="bg-white p-5 rounded-2xl border border-ink-100 flex gap-4 items-start justify-between">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold">{{ $n->title }}</span>
                    @if($n->kind === 'urgent')<span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-xs font-bold">Urgent</span>@endif
                    @if($n->pinned)<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">Pinned</span>@endif
                </div>
                <div class="text-sm text-ink-900/70 mt-1 whitespace-pre-line">{{ $n->body }}</div>
                <div class="text-xs text-ink-900/50 mt-2">{{ $n->property?->name ?? 'All my PGs' }} · {{ $n->created_at->format('d M Y, h:i A') }}</div>
            </div>
            <form method="POST" action="{{ route('owner.notices.destroy', $n->id) }}" onsubmit="return confirm('Remove this notice?')">
                @csrf @method('DELETE')
                <button class="px-3 py-2 text-sm font-bold text-rose-600 border border-rose-200 rounded-lg">Remove</button>
            </form>
        </div>
    @empty
        <div class="bg-white p-10 rounded-2xl border border-ink-100 text-center text-ink-900/60">No notices yet.</div>
    @endforelse
</div>
@endsection
