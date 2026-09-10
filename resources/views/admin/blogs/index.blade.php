@extends('layouts.dashboard')
@section('title', 'Blogs')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">Blogs</h1>
        <p class="text-ink-900/60">Manage all blog posts</p>
    </div>
    <a href="{{ route('admin.blogs.create') }}" class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
        + New Blog
    </a>
</div>

@if(session('success'))
    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <table class="w-full">
        <thead class="bg-cream border-b border-ink-900/10">
            <tr>
                <th class="text-left py-4 px-4 text-xs font-bold uppercase text-ink-900/60">Blog</th>
                <th class="text-center py-4 px-4 text-xs font-bold uppercase text-ink-900/60">Status</th>
                <th class="text-center py-4 px-4 text-xs font-bold uppercase text-ink-900/60">Views</th>
                <th class="text-center py-4 px-4 text-xs font-bold uppercase text-ink-900/60">Date</th>
                <th class="text-center py-4 px-4 text-xs font-bold uppercase text-ink-900/60">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($blogs as $blog)
            <tr class="border-b border-ink-900/5 hover:bg-cream/50">
                <td class="py-4 px-4">
                    <div class="flex items-center gap-3">
                        @if($blog->cover_image)
                            <img src="{{ asset('storage/' . $blog->cover_image) }}" class="w-14 h-14 rounded-lg object-cover flex-shrink-0">
                        @else
                            <div class="w-14 h-14 rounded-lg bg-ink-900/5 flex items-center justify-center flex-shrink-0">📝</div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-bold text-ink-950 truncate">{{ $blog->title }}</p>
                            <p class="text-xs text-ink-900/50 truncate">{{ Str::limit($blog->excerpt, 60) }}</p>
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    @if($blog->is_published)
                        <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold">● Published</span>
                    @else
                        <span class="inline-block px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold">● Draft</span>
                    @endif
                </td>
                <td class="text-center text-sm text-ink-900/60">{{ number_format($blog->view_count) }}</td>
                <td class="text-center text-sm text-ink-900/60">{{ $blog->created_at->format('d M Y') }}</td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-2">
                        @if($blog->is_published)
                            <a href="{{ route('blog.show', $blog->slug) }}" target="_blank" class="text-xs px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg font-bold hover:bg-blue-100">View</a>
                        @endif
                        <a href="{{ route('admin.blogs.edit', $blog) }}" class="text-xs px-3 py-1.5 bg-ink-900/5 text-ink-900 rounded-lg font-bold hover:bg-ink-900/10">Edit</a>
                        
                        <form method="POST" action="{{ route('admin.blogs.toggle', $blog) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-xs px-3 py-1.5 {{ $blog->is_published ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} rounded-lg font-bold">
                                {{ $blog->is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>
                        
                        <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" class="inline" onsubmit="return confirm('Delete this blog permanently?')">
                            @csrf @method('DELETE')
                            <button class="text-xs px-3 py-1.5 bg-rose-50 text-rose-700 rounded-lg font-bold hover:bg-rose-100">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="py-12 text-center text-ink-900/50">
                    No blogs yet. <a href="{{ route('admin.blogs.create') }}" class="text-coral-600 font-bold">Create your first blog →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $blogs->links() }}</div>

@endsection