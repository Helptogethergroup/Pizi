@php $isEdit = isset($blog) && $blog; @endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<style>
    #contentEditor { min-height: 360px; background: #fff; font-size: 0.95rem; }
    #contentEditor .ql-editor { min-height: 360px; }
    .ql-toolbar.ql-snow { border-radius: 0.75rem 0.75rem 0 0; border-color: rgba(15,15,15,0.1) !important; }
    .ql-container.ql-snow { border-radius: 0 0 0.75rem 0.75rem; border-color: rgba(15,15,15,0.1) !important; }
</style>

<form id="blogForm" method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- MAIN CONTENT --}}
        <div class="lg:col-span-2 space-y-6 min-w-0">

            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-ink-900/10">
                <label class="text-xs font-bold uppercase text-ink-900/60">Title *</label>
                <input name="title" id="titleInput" required value="{{ old('title', $blog->title ?? '') }}" placeholder="10 Tips for Finding the Perfect PG" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 text-lg font-bold outline-none focus:border-coral-500">
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-ink-900/10">
                <label class="text-xs font-bold uppercase text-ink-900/60">Content *</label>
                <p class="text-xs text-ink-900/50 mt-1 mb-2">Type/paste normally — headings, bold, links etc. are added automatically, no HTML needed.</p>
                <div id="contentEditor">{!! old('content', $blog->content ?? '') !!}</div>
                <textarea name="content" id="contentInput" required class="hidden">{{ old('content', $blog->content ?? '') }}</textarea>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-ink-900/10">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="text-xs font-bold uppercase text-ink-900/60">Excerpt (short summary)</label>
                </div>
                <textarea name="excerpt" id="excerptInput" rows="2" maxlength="500" placeholder="A brief description shown in blog list..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="space-y-6 min-w-0">

            {{-- AI Assist --}}
            <div class="bg-gradient-to-br from-coral-50 to-white p-5 sm:p-6 rounded-2xl border border-coral-200">
                <h3 class="font-bold text-lg mb-1">✨ AI Assist</h3>
                <p class="text-xs text-ink-900/60 mb-3">Title + content likh lo, fir click karo — excerpt aur SEO fields khud fill ho jayenge (edit kar sakte ho baad mein).</p>
                <button type="button" id="aiAssistBtn" class="w-full py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm disabled:opacity-50">
                    ✨ Auto-fill excerpt & SEO
                </button>
                <p id="aiAssistStatus" class="text-xs mt-2 h-4"></p>
            </div>

            {{-- Publish --}}
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-ink-900/10">
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
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-ink-900/10">
                <h3 class="font-bold text-lg mb-4">Cover Image</h3>
                @if($isEdit && $blog->cover_image)
                    <img src="{{ asset('storage/' . $blog->cover_image) }}" class="w-full h-32 object-cover rounded-lg mb-3" alt="">
                @endif
                <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
                <p class="text-xs text-ink-900/50 mt-2">Recommended: 1200×630px</p>
            </div>

            {{-- SEO --}}
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-ink-900/10">
                <h3 class="font-bold text-lg mb-4">🔍 SEO Settings</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold uppercase text-ink-900/60">Meta Title</label>
                            <span id="metaTitleCount" class="text-[11px] text-ink-900/40">0/60</span>
                        </div>
                        <input name="meta_title" id="metaTitleInput" value="{{ old('meta_title', $blog->meta_title ?? '') }}" placeholder="SEO title (60 chars)" maxlength="60" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm outline-none focus:border-coral-500">
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold uppercase text-ink-900/60">Meta Description</label>
                            <span id="metaDescCount" class="text-[11px] text-ink-900/40">0/160</span>
                        </div>
                        <textarea name="meta_description" id="metaDescInput" rows="3" placeholder="SEO description (160 chars)" maxlength="160" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm outline-none focus:border-coral-500">{{ old('meta_description', $blog->meta_description ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-900/60">Keywords</label>
                        <input name="keywords" id="keywordsInput" value="{{ old('keywords', $blog->keywords ?? '') }}" placeholder="pg delhi, hostel noida" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm outline-none focus:border-coral-500">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
(function () {
    const quill = new Quill('#contentEditor', {
        theme: 'snow',
        placeholder: 'Write your blog content here...',
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'blockquote'],
                ['clean'],
            ],
        },
    });

    const contentInput = document.getElementById('contentInput');
    const form = document.getElementById('blogForm');

    // Keep the hidden textarea (what actually gets submitted) in sync.
    quill.on('text-change', function () {
        contentInput.value = quill.root.innerHTML;
    });
    form.addEventListener('submit', function () {
        contentInput.value = quill.root.innerHTML;
    });

    // SEO char counters
    function bindCounter(inputId, counterId, max) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        if (!input || !counter) return;
        const update = () => { counter.textContent = input.value.length + '/' + max; };
        input.addEventListener('input', update);
        update();
    }
    bindCounter('metaTitleInput', 'metaTitleCount', 60);
    bindCounter('metaDescInput', 'metaDescCount', 160);

    // AI Assist
    document.getElementById('aiAssistBtn').addEventListener('click', async function () {
        const btn = this;
        const status = document.getElementById('aiAssistStatus');
        const title = document.getElementById('titleInput').value.trim();
        const contentText = quill.getText().trim();

        if (!title || contentText.length < 30) {
            status.textContent = '⚠️ Title aur thoda content likh lo pehle (min 30 chars).';
            status.className = 'text-xs mt-2 h-4 text-amber-600 font-semibold';
            return;
        }

        btn.disabled = true;
        status.textContent = '⏳ AI likh raha hai...';
        status.className = 'text-xs mt-2 h-4 text-ink-900/60';

        try {
            const res = await fetch('{{ route('admin.blogs.ai-assist') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ title: title, content_text: contentText }),
            });
            const data = await res.json();

            if (!data.success) {
                status.textContent = '⚠️ ' + (data.message || 'Try again.');
                status.className = 'text-xs mt-2 h-4 text-rose-600 font-semibold';
                return;
            }

            document.getElementById('excerptInput').value = data.data.excerpt;
            document.getElementById('metaTitleInput').value = data.data.meta_title;
            document.getElementById('metaDescInput').value = data.data.meta_description;
            document.getElementById('keywordsInput').value = data.data.keywords;
            document.getElementById('metaTitleInput').dispatchEvent(new Event('input'));
            document.getElementById('metaDescInput').dispatchEvent(new Event('input'));

            status.textContent = '✓ Done — check and edit as needed.';
            status.className = 'text-xs mt-2 h-4 text-emerald-600 font-semibold';
        } catch (err) {
            status.textContent = '⚠️ Network error — try again.';
            status.className = 'text-xs mt-2 h-4 text-rose-600 font-semibold';
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
