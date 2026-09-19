@extends('layouts.dashboard')
@section('title', 'Blogs — SEO')
@section('content')

<div class="flex justify-between items-center mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">Blog Posts</h1>
        <p class="text-ink-900/60 mt-1">Manage all blog content</p>
    </div>
    <a href="{{ route('seo.blogs.create') }}" class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold transition shadow-lg shadow-coral-500/30">+ Add New Blog</a>
</div>

@if($blogs->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-pen-to-square fa-fw"></i></div>
        <p class="text-ink-700 mb-4">No blogs yet. Create your first one!</p>
        <a href="{{ route('seo.blogs.create') }}" class="inline-block px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ Create Blog</a>
    </div>
@else
    <div class="bg-white rounded-2xl border border-ink-100 divide-y divide-ink-100">
        @foreach($blogs as $blog)
            <div class="p-5 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        @if($blog->is_published)
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Published</span>
                        @else
                            <span class="text-xs bg-ink-100 text-ink-700 px-2 py-0.5 rounded-full font-bold">Draft</span>
                        @endif
                        <span class="text-xs text-ink-500">{{ $blog->created_at->format('d M Y') }}</span>
                    </div>
                    <h3 class="font-bold text-lg text-ink-950">{{ $blog->title }}</h3>
                    @if($blog->excerpt)
                        <p class="text-sm text-ink-700 mt-1 line-clamp-2">{{ $blog->excerpt }}</p>
                    @endif
                </div>
                <div class="flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('seo.blogs.toggle', $blog) }}" class="inline">
                        @csrf @method('PATCH')
                        <button class="px-3 py-1.5 {{ $blog->is_published ? 'bg-amber-500' : 'bg-emerald-500' }} text-white rounded-lg text-sm font-bold">
                            {{ $blog->is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                    </form>
                    <a href="{{ route('seo.blogs.edit', $blog) }}" class="px-3 py-1.5 bg-coral-500 text-white rounded-lg text-sm font-bold">Edit</a>
                    <form method="POST" action="{{ route('seo.blogs.destroy', $blog) }}" onsubmit="return confirm('Delete this blog?')" class="inline">
                        @csrf @method('DELETE')
                        <button class="px-3 py-1.5 bg-rose-500 text-white rounded-lg text-sm font-bold">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $blogs->links() }}</div>
@endif

@endsection