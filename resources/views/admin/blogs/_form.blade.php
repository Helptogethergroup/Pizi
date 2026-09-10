@php $isEdit = isset($blog) && $blog; @endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid lg:grid-cols-3 gap-6">
        
        {{-- MAIN CONTENT --}}
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
                <label class="text-xs font-bold uppercase text-ink-900/60">Title *</label>
                <input name="title" required value="{{ old('title', $blog->title ?? '') }}" placeholder="10 Tips for Finding the Perfect PG" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 text-lg font-bold outline-none focus:border-coral-500">
            </div>

            <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
                <label class="text-xs font-bold uppercase text-ink-900/60">Excerpt (short summary)</label>
                <textarea name="excerpt" rows="2" placeholder="A brief description shown in blog list..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
                <label class="text-xs font-bold uppercase text-ink-900/60">Content *</label>
                <p class="text-xs text-ink-900/50 mt-1 mb-2">Use HTML: &lt;h2&gt;Heading&lt;/h2&gt;, &lt;p&gt;paragraph&lt;/p&gt;, &lt;strong&gt;bold&lt;/strong&gt;, &lt;a href="..."&gt;link&lt;/a&gt;</p>
                <textarea name="content" rows="20" required placeholder="Write your blog content here. You can use HTML tags." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 font-mono text-sm">{{ old('content', $blog->content ?? '') }}</textarea>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="space-y-6">
            
            {{-- Publish --}}
            <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
                <h3 class="font-bold text-lg mb-4">Publish</h3>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $blog->is_published ?? false)) class="rounded w-5 h-5">
                    <span class="font-semibold">Publish immediately</span>
                </label>
                <p class="text-xs text-ink-900/50 mt-2">Unchecked = saved as draft</p>
                
                <button type="submit" class="w-full mt-4 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
                    {{ $isEdit ? 'Update Blog' : 'Save Blog' }}
                </button>
            </div>

            {{-- Cover Image --}}
            <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
                <h3 class="font-bold text-lg mb-4">Cover Image</h3>
                @if($isEdit && $blog->cover_image)
                    <img src="{{ asset('storage/' . $blog->cover_image) }}" class="w-full h-32 object-cover rounded-lg mb-3">
                @endif
                <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
                <p class="text-xs text-ink-900/50 mt-2">Recommended: 1200×630px</p>
            </div>

            {{-- SEO --}}
            <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
                <h3 class="font-bold text-lg mb-4">🔍 SEO Settings</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-900/60">Meta Title</label>
                        <input name="meta_title" value="{{ old('meta_title', $blog->meta_title ?? '') }}" placeholder="SEO title (60 chars)" maxlength="60" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm outline-none focus:border-coral-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-900/60">Meta Description</label>
                        <textarea name="meta_description" rows="3" placeholder="SEO description (160 chars)" maxlength="160" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm outline-none focus:border-coral-500">{{ old('meta_description', $blog->meta_description ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-900/60">Keywords</label>
                        <input name="keywords" value="{{ old('keywords', $blog->keywords ?? '') }}" placeholder="pg delhi, hostel noida" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm outline-none focus:border-coral-500">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>