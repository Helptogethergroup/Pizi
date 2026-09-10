@extends('layouts.dashboard')
@section('title', 'New Blog')
@section('content')

<div class="mb-6">
    <a href="{{ route('seo.blogs.index') }}" class="text-coral-500 font-bold">← Back to blogs</a>
    <h1 class="font-display font-black text-3xl mt-2">Create New Blog</h1>
</div>

<form method="POST" action="{{ route('seo.blogs.store') }}" enctype="multipart/form-data" class="bg-white p-6 rounded-2xl border border-ink-100 max-w-4xl space-y-4">
    @csrf

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Title *</label>
        <input name="title" required value="{{ old('title') }}" placeholder="Enter blog title..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 text-lg">
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Excerpt (short summary)</label>
        <textarea name="excerpt" rows="2" maxlength="500" placeholder="2-3 line summary..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ old('excerpt') }}</textarea>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Content *</label>
        <textarea name="content" required rows="15" placeholder="Write your blog content here. HTML supported." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ old('content') }}</textarea>
        <p class="text-xs text-ink-500 mt-1">💡 You can use basic HTML tags like &lt;b&gt;, &lt;i&gt;, &lt;a&gt;, &lt;br&gt;</p>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Cover Image</label>
        <input type="file" name="cover_image" accept="image/*" class="w-full mt-1 text-sm">
    </div>

    <div class="pt-4 border-t border-ink-100">
        <h3 class="font-bold mb-3">🔍 SEO Settings (optional)</h3>
        <div class="space-y-3">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Meta Title</label>
                <input name="meta_title" maxlength="200" value="{{ old('meta_title') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Meta Description</label>
                <textarea name="meta_description" rows="2" maxlength="500" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ old('meta_description') }}</textarea>
            </div>
        </div>
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="rounded w-5 h-5 text-coral-500">
        <span class="font-bold">Publish immediately</span>
    </label>

    <div class="flex gap-3 pt-4">
        <button type="submit" class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Save Blog</button>
        <a href="{{ route('seo.blogs.index') }}" class="px-6 py-3 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

@endsection