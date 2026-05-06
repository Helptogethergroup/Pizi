@extends('layouts.app')
@section('title', 'Blog — PG Living Tips & Guides | PGFind')
@section('meta_description', 'Tips, guides, and stories about PG living, finding the right room, and city moving guides.')
@section('content')
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
    <span class="text-xs font-semibold text-coral-600 uppercase tracking-wider">PGFind Journal</span>
    <h1 class="font-display font-black text-5xl md:text-6xl mt-3">Stories from the city.</h1>
    <p class="text-lg text-ink-900/70 mt-3 max-w-2xl">PG hunting tips, locality guides, moving checklists & more.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16">
        @forelse($blogs as $blog)
            <a href="{{ route('blog.show', $blog->slug) }}" class="group block">
                <div class="aspect-[4/3] bg-ink-900/5 rounded-2xl overflow-hidden mb-4">
                    <img src="{{ $blog->cover_image ?: 'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=800&q=80' }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition" alt="{{ $blog->title }}">
                </div>
                <h3 class="font-display font-bold text-2xl group-hover:text-coral-600 leading-tight">{{ $blog->title }}</h3>
                <p class="text-ink-900/60 mt-2 line-clamp-2">{{ $blog->excerpt }}</p>
                <p class="text-xs text-ink-900/40 mt-3">{{ $blog->published_at?->format('M d, Y') }}</p>
            </a>
        @empty
            <p class="col-span-3 text-center text-ink-900/60 py-20">Articles coming soon.</p>
        @endforelse
    </div>
    <div class="mt-12">{{ $blogs->links() }}</div>
</section>
@endsection
