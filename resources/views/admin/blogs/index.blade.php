@extends('layouts.dashboard')
@section('title', 'Blogs')
@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">Blogs</h1>
        <p class="text-ink-900/60">Manage all blog posts</p>
    </div>
    <a href="{{ route('admin.blogs.create') }}" class="inline-flex items-center justify-center px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm shrink-0">
        + New Blog
    </a>
</div>

@if(session('success'))
    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
        {{ session('success') }}
    </div>
@endif

{{-- STATS --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-2xl border border-ink-900/10 p-4">
        <div class="text-xs text-ink-900/50 uppercase font-bold">Total</div>
        <div class="font-display font-black text-2xl mt-1">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-ink-900/10 p-4">
        <div class="text-xs text-ink-900/50 uppercase font-bold">Published</div>
        <div class="font-display font-black text-2xl mt-1 text-emerald-600">{{ number_format($stats['published']) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-ink-900/10 p-4">
        <div class="text-xs text-ink-900/50 uppercase font-bold">Drafts</div>
        <div class="font-display font-black text-2xl mt-1 text-amber-600">{{ number_format($stats['drafts']) }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-ink-900/10 p-4">
        <div class="text-xs text-ink-900/50 uppercase font-bold">Total views</div>
        <div class="font-display font-black text-2xl mt-1">{{ number_format($stats['views']) }}</div>
    </div>
</div>

{{-- FILTERS --}}
<form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title..."
           class="flex-1 px-4 py-2.5 rounded-xl border border-ink-900/15 text-sm">
    <select name="status" class="px-4 py-2.5 rounded-xl border border-ink-900/15 text-sm">
        <option value="">All statuses</option>
        <option value="published" @selected(request('status') === 'published')>Published</option>
        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-xl text-sm font-bold shrink-0">Filter</button>
    @if(request('search') || request('status'))
        <a href="{{ route('admin.blogs.index') }}" class="px-5 py-2.5 border border-ink-900/15 rounded-xl text-sm font-bold text-center shrink-0">Clear</a>
    @endif
</form>

@forelse($blogs as $blog)
    {{-- Card layout — works identically on mobile and desktop, no columns
         to overflow or cut off action buttons. --}}
    <div class="bg-white rounded-2xl border border-ink-900/10 p-4 sm:p-5 mb-3 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            @if($blog->cover_image)
                <img src="{{ asset('storage/' . $blog->cover_image) }}" class="w-16 h-16 rounded-xl object-cover flex-shrink-0" alt="">
            @else
                <div class="w-16 h-16 rounded-xl bg-cream flex items-center justify-center flex-shrink-0 text-2xl"><i class="fa-solid fa-pen-to-square fa-fw"></i></div>
            @endif
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="font-bold text-ink-950 truncate">{{ $blog->title }}</p>
                    @if($blog->is_published)
                        <span class="shrink-0 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-bold">● Published</span>
                    @else
                        <span class="shrink-0 px-2.5 py-0.5 bg-amber-50 text-amber-700 rounded-full text-[11px] font-bold">● Draft</span>
                    @endif
                </div>
                <p class="text-xs text-ink-900/50 truncate mt-0.5">{{ Str::limit($blog->excerpt, 90) }}</p>
                <p class="text-xs text-ink-900/40 mt-1"><i class="fa-solid fa-eye fa-fw"></i> {{ number_format($blog->view_count) }} views · {{ $blog->created_at->format('d M Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap shrink-0">
            @if($blog->is_published)
                <a href="{{ route('blog.show', $blog->slug) }}" target="_blank" class="text-xs px-3 py-2 bg-blue-50 text-blue-700 rounded-lg font-bold hover:bg-blue-100 whitespace-nowrap">View</a>
            @endif
            <a href="{{ route('admin.blogs.edit', $blog) }}" class="text-xs px-3 py-2 bg-ink-900/5 text-ink-900 rounded-lg font-bold hover:bg-ink-900/10 whitespace-nowrap">Edit</a>

            <form method="POST" action="{{ route('admin.blogs.toggle', $blog) }}" class="inline">
                @csrf @method('PATCH')
                <button class="text-xs px-3 py-2 {{ $blog->is_published ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} rounded-lg font-bold whitespace-nowrap">
                    {{ $blog->is_published ? 'Unpublish' : 'Publish' }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" class="inline" onsubmit="return confirm('Delete this blog permanently?')">
                @csrf @method('DELETE')
                <button class="text-xs px-3 py-2 bg-rose-50 text-rose-700 rounded-lg font-bold hover:bg-rose-100 whitespace-nowrap">Delete</button>
            </form>
        </div>
    </div>
@empty
    <div class="bg-white rounded-2xl border border-ink-900/10 py-16 text-center text-ink-900/50">
        No blogs found. <a href="{{ route('admin.blogs.create') }}" class="text-coral-600 font-bold">Create your first blog →</a>
    </div>
@endforelse

<div class="mt-6">{{ $blogs->links() }}</div>

@endsection
