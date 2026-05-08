@extends('layouts.dashboard')
@section('title', 'Blogs — SEO')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">📝 Blogs</h1>
        <p class="text-ink-900/60 mt-1">Create and manage SEO-optimized blog content</p>
    </div>
    <a href="{{ route('seo.blogs.create') }}" class="px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ New Blog</a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="p-4 rounded-2xl bg-ink-950 text-cream">
        <div class="text-xs uppercase opacity-70">Total</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-emerald-500 text-white">
        <div class="text-xs uppercase opacity-90">Published</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['published'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-amber-100 text-amber-900">
        <div class="text-xs uppercase">Drafts</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['drafts'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-coral-500 text-white">
        <div class="text-xs uppercase opacity-90">Total Views</div>
        <div class="font-display font-black text-3xl mt-1">{{ number_format($stats['views']) }}</div>
    </div>
</div>

<form class="flex gap-2 mb-4 bg-white p-3 rounded-xl border border-ink-900/10">
    <input name="search" value="{{ request('search') }}" placeholder="Search…" class="flex-1 px-3 py-2 rounded-lg border border-ink-900/15">
    <select name="status" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <option value="">All</option>
        <option value="published" @selected(request('status')==='published')>Published</option>
        <option value="draft" @selected(request('status')==='draft')>Drafts</option>
    </select>
    <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg font-semibold">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
<table class="w-full text-sm">
    <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
        <tr>
            <th class="px-4 py-3">Blog</th>
            <th>Author</th>
            <th>Views</th>
            <th>Status</th>
            <th class="text-right pr-4">Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse($blogs as $b)
        <tr class="border-t border-ink-900/5">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    @if($b->cover_image)
                        <img src="{{ str_starts_with($b->cover_image, 'http') ? $b->cover_image : asset('storage/' . $b->cover_image) }}" class="w-16 h-12 rounded object-cover">
                    @else
                        <div class="w-16 h-12 rounded bg-ink-900/10 flex items-center justify-center">📝</div>
                    @endif
                    <div>
                        <div class="font-semibold">{{ Str::limit($b->title, 50) }}</div>
                        <div class="text-xs text-ink-900/50">{{ Str::limit($b->excerpt, 60) }}</div>
                    </div>
                </div>
            </td>
            <td class="text-xs">{{ $b->author?->name }}</td>
            <td class="font-bold">{{ number_format($b->view_count) }}</td>
            <td>
                @if($b->is_published)
                    <span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">✓ Live</span>
                @else
                    <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">Draft</span>
                @endif
            </td>
            <td class="px-4 py-3 text-right">
                <div class="flex flex-wrap gap-1 justify-end">
                    @if($b->is_published)
                        <a href="{{ route('blog.show', $b->slug) }}" target="_blank" class="text-xs px-2 py-1 rounded-lg border border-ink-900/15">👁</a>
                    @endif
                    <a href="{{ route('seo.blogs.edit', $b) }}" class="text-xs px-2 py-1 rounded-lg bg-blue-500 text-white">✏️ Edit</a>
                    <form method="POST" action="{{ route('seo.blogs.toggle', $b) }}" class="inline">@csrf @method('PATCH')
                        <button class="text-xs px-2 py-1 rounded-lg {{ $b->is_published ? 'bg-amber-500' : 'bg-emerald-500' }} text-white">{{ $b->is_published ? '⏸' : '🚀' }}</button>
                    </form>
                    <form method="POST" action="{{ route('seo.blogs.destroy', $b) }}" class="inline" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')
                        <button class="text-xs px-2 py-1 rounded-lg bg-rose-500 text-white">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="px-4 py-12 text-center text-ink-900/50">No blogs yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div class="mt-6">{{ $blogs->links() }}</div>

@endsection