@extends('layouts.dashboard')
@section('title', 'SEO Dashboard')
@section('content')

<h1 class="font-display font-black text-3xl mb-1">🔍 SEO Manager Dashboard</h1>
<p class="text-ink-900/60">Manage SEO settings and blog content</p>

<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6 mb-8">
    <div class="p-4 rounded-2xl bg-ink-950 text-cream">
        <div class="text-xs uppercase opacity-70">SEO Pages</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['pages'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-emerald-500 text-white">
        <div class="text-xs uppercase opacity-90">Active</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['active_pages'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-coral-500 text-white">
        <div class="text-xs uppercase opacity-90">My Blogs</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total_blogs'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-blue-500 text-white">
        <div class="text-xs uppercase opacity-90">Published</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['published_blogs'] }}</div>
    </div>
    <div class="p-4 rounded-2xl bg-violet-500 text-white">
        <div class="text-xs uppercase opacity-90">Total Views</div>
        <div class="font-display font-black text-3xl mt-1">{{ number_format($stats['total_views']) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display font-bold text-xl">📝 Recent blogs</h2>
            <a href="{{ route('seo.blogs.create') }}" class="text-xs px-3 py-1.5 bg-coral-500 text-white rounded-lg font-bold">+ New</a>
        </div>
        @forelse($recentBlogs as $b)
            <a href="{{ route('seo.blogs.edit', $b) }}" class="flex items-center justify-between py-3 border-b border-ink-900/5 last:border-0 hover:bg-cream/40 -mx-5 px-5">
                <div class="flex-1 min-w-0">
                    <div class="font-semibold truncate">{{ $b->title }}</div>
                    <div class="text-xs text-ink-900/50">{{ $b->view_count }} views · {{ $b->created_at->format('d M') }}</div>
                </div>
                @if($b->is_published)
                    <span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">✓ Live</span>
                @else
                    <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">Draft</span>
                @endif
            </a>
        @empty
            <p class="text-ink-900/50 text-center py-8">No blogs yet. <a href="{{ route('seo.blogs.create') }}" class="text-coral-600 font-semibold">+ Create one</a></p>
        @endforelse
    </div>

    <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display font-bold text-xl">🔍 Recent SEO updates</h2>
            <a href="{{ route('seo.settings.create') }}" class="text-xs px-3 py-1.5 bg-coral-500 text-white rounded-lg font-bold">+ New page</a>
        </div>
        @forelse($recentSeo as $s)
            <a href="{{ route('seo.settings.edit', $s) }}" class="flex items-center justify-between py-3 border-b border-ink-900/5 last:border-0 hover:bg-cream/40 -mx-5 px-5">
                <div class="flex-1 min-w-0">
                    <div class="font-semibold truncate">{{ $s->page_label }}</div>
                    <div class="text-xs text-ink-900/50 font-mono">{{ $s->page_key }} · {{ $s->updated_at->diffForHumans() }}</div>
                </div>
                @if($s->is_active)
                    <span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">Active</span>
                @else
                    <span class="px-2 py-1 rounded-full text-xs bg-slate-100">Inactive</span>
                @endif
            </a>
        @empty
            <p class="text-ink-900/50 text-center py-8">No SEO settings yet.</p>
        @endforelse
    </div>
</div>

@endsection