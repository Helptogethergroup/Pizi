{{-- Shared blog form partial. Used by both admin + owner. --}}

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main content --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <label class="text-xs font-bold text-ink-900/60 uppercase">Title *</label>
            <input name="title" value="{{ old('title', $blog->title ?? '') }}" required maxlength="200"
                   placeholder="e.g. How to Choose the Right PG in Delhi NCR"
                   class="w-full mt-1 px-4 py-3 rounded-xl border-2 border-ink-900/15 text-xl font-display font-bold focus:border-coral-500 outline-none">
        </div>

        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <label class="text-xs font-bold text-ink-900/60 uppercase">Excerpt (short summary)</label>
            <textarea name="excerpt" rows="2" maxlength="500"
                      placeholder="A 1-2 line summary that appears in lists and SEO"
                      class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <label class="text-xs font-bold text-ink-900/60 uppercase">Content *</label>
            <p class="text-xs text-ink-900/50 mt-1 mb-2">You can use HTML tags like &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;a href=""&gt;</p>
            <textarea id="contentArea" name="content" rows="20" required
                      placeholder="<h2>Section heading</h2>&#10;<p>Your paragraph here...</p>"
                      class="w-full px-4 py-3 rounded-xl border border-ink-900/15 font-mono text-sm">{{ old('content', $blog->content ?? '') }}</textarea>

            {{-- Quick HTML buttons --}}
            <div class="flex flex-wrap gap-2 mt-3">
                <button type="button" onclick="insertTag('<h2>', '</h2>')" class="text-xs px-2 py-1 rounded bg-ink-900/5">H2 heading</button>
                <button type="button" onclick="insertTag('<h3>', '</h3>')" class="text-xs px-2 py-1 rounded bg-ink-900/5">H3 heading</button>
                <button type="button" onclick="insertTag('<p>', '</p>')" class="text-xs px-2 py-1 rounded bg-ink-900/5">Paragraph</button>
                <button type="button" onclick="insertTag('<strong>', '</strong>')" class="text-xs px-2 py-1 rounded bg-ink-900/5"><strong>Bold</strong></button>
                <button type="button" onclick="insertTag('<em>', '</em>')" class="text-xs px-2 py-1 rounded bg-ink-900/5"><em>Italic</em></button>
                <button type="button" onclick="insertTag('<ul>\n  <li>', '</li>\n  <li></li>\n</ul>')" class="text-xs px-2 py-1 rounded bg-ink-900/5">List</button>
                <button type="button" onclick="insertTag('<a href=&quot;&quot;>', '</a>')" class="text-xs px-2 py-1 rounded bg-ink-900/5">Link</button>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold mb-3">SEO settings</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold text-ink-900/60 uppercase">Meta title</label>
                    <input name="meta_title" value="{{ old('meta_title', $blog->meta_title ?? '') }}" maxlength="160"
                           placeholder="Auto-generated from title if empty"
                           class="w-full mt-1 px-4 py-2 rounded-xl border border-ink-900/15">
                </div>
                <div>
                    <label class="text-xs font-bold text-ink-900/60 uppercase">Meta description</label>
                    <textarea name="meta_description" rows="2" maxlength="320"
                              placeholder="Auto-generated from excerpt if empty"
                              class="w-full mt-1 px-4 py-2 rounded-xl border border-ink-900/15">{{ old('meta_description', $blog->meta_description ?? '') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-ink-900/60 uppercase">Keywords (comma-separated)</label>
                    <input name="keywords" value="{{ old('keywords', $blog->keywords ?? '') }}" maxlength="255"
                           placeholder="pg in delhi, paying guest, hostel"
                           class="w-full mt-1 px-4 py-2 rounded-xl border border-ink-900/15">
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold mb-3">Cover image</h3>

            @if(isset($blog) && $blog->cover_image)
                <img src="{{ str_starts_with($blog->cover_image, 'http') ? $blog->cover_image : asset('storage/' . $blog->cover_image) }}" class="w-full rounded-xl mb-3">
            @endif

            <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
            <p class="text-xs text-ink-900/50 mt-2">Recommended: 1200×600px, max 4 MB</p>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
                <h3 class="font-display font-bold mb-3">Publishing</h3>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1"
                           @if(old('is_published', $blog->is_published ?? false)) checked @endif
                           class="w-5 h-5 rounded">
                    <span class="font-semibold">Publish immediately</span>
                </label>
                <p class="text-xs text-ink-900/50 mt-2">If unchecked, blog will be saved as draft.</p>
            </div>
        @else
            <div class="bg-amber-50 p-5 rounded-2xl border border-amber-200">
                <h3 class="font-display font-bold text-amber-900 mb-2">📝 Draft Mode</h3>
                <p class="text-sm text-amber-900/80">Your blog will be saved as a draft. Admin will review and publish it.</p>
            </div>
        @endif

        <button type="submit" class="w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg">
            {{ isset($blog) && $blog->exists ? '💾 Update Blog' : '✓ Save Blog' }}
        </button>
    </div>
</div>

<script>
function insertTag(open, close) {
    const ta = document.getElementById('contentArea');
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const selected = ta.value.substring(start, end);
    ta.value = ta.value.substring(0, start) + open + selected + close + ta.value.substring(end);
    ta.focus();
    ta.selectionStart = start + open.length;
    ta.selectionEnd = start + open.length + selected.length;
}
</script>