<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">

        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold mb-3">Page identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Page Key (URL slug) *</label>
                    <input name="page_key" value="{{ old('page_key', $setting->page_key ?? '') }}" required
                           placeholder="e.g. home, pg-in-delhi, search"
                           class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 font-mono text-sm">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Display Label *</label>
                    <input name="page_label" value="{{ old('page_label', $setting->page_label ?? '') }}" required
                           placeholder="e.g. Home Page, PG in Delhi"
                           class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold mb-3">Meta tags</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Meta Title</label>
                    <input name="meta_title" value="{{ old('meta_title', $setting->meta_title ?? '') }}" maxlength="160"
                           class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                    <p class="text-xs text-ink-900/50 mt-1">Max 60 chars recommended for Google</p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Meta Description</label>
                    <textarea name="meta_description" rows="3" maxlength="500"
                              class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">{{ old('meta_description', $setting->meta_description ?? '') }}</textarea>
                    <p class="text-xs text-ink-900/50 mt-1">Max 160 chars recommended</p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Keywords (comma-separated)</label>
                    <input name="keywords" value="{{ old('keywords', $setting->keywords ?? '') }}"
                           placeholder="pg in delhi, paying guest, hostel delhi"
                           class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold mb-3">Open Graph (Facebook / WhatsApp / Twitter)</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">OG Title</label>
                    <input name="og_title" value="{{ old('og_title', $setting->og_title ?? '') }}" maxlength="160"
                           class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">OG Description</label>
                    <textarea name="og_description" rows="2" maxlength="500"
                              class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">{{ old('og_description', $setting->og_description ?? '') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">OG Image (1200x630 recommended)</label>
                    @if(isset($setting) && $setting->og_image)
                        <img src="{{ asset('storage/' . $setting->og_image) }}" class="w-full max-w-sm rounded-xl mb-3 mt-2">
                    @endif
                    <input type="file" name="og_image" accept="image/*" class="w-full text-sm mt-1">
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold mb-3">Advanced (optional)</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Schema JSON-LD</label>
                    <textarea name="schema_json" rows="6"
                              placeholder='{"@context":"https://schema.org","@type":"Organization",...}'
                              class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 font-mono text-xs">{{ old('schema_json', $setting->schema_json ?? '') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Custom HTML for &lt;head&gt;</label>
                    <textarea name="custom_head_html" rows="4"
                              placeholder="<!-- Google Analytics, custom meta, etc. -->"
                              class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15 font-mono text-xs">{{ old('custom_head_html', $setting->custom_head_html ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold mb-3">Status</h3>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @if(old('is_active', $setting->is_active ?? true)) checked @endif
                       class="w-5 h-5 rounded">
                <span class="font-semibold">Active (apply on site)</span>
            </label>
        </div>

        <button type="submit" class="w-full py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg">
            {{ isset($setting) && $setting->exists ? '💾 Update' : '✓ Save' }}
        </button>
    </div>
</div>