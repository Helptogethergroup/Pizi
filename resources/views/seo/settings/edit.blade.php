@extends('layouts.dashboard')
@section('title', 'Edit SEO Page')
@section('content')

<div class="mb-6">
    <a href="{{ route('seo.settings.index') }}" class="text-coral-500 font-bold">← Back to SEO Pages</a>
    <h1 class="font-display font-black text-3xl mt-2">Edit: {{ $setting->page_label }}</h1>
    <p class="text-ink-900/60 mt-1">Update SEO settings for this page</p>
</div>

<form method="POST" action="{{ route('seo.settings.update', $setting) }}" enctype="multipart/form-data" class="bg-white p-6 rounded-2xl border border-ink-100 max-w-3xl space-y-4">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Page Key *</label>
            <input name="page_key" required value="{{ old('page_key', $setting->page_key) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            <p class="text-xs text-ink-500 mt-1">Unique identifier (no spaces)</p>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Page Label *</label>
            <input name="page_label" required value="{{ old('page_label', $setting->page_label) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
        </div>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Meta Title</label>
        <input name="meta_title" maxlength="255" value="{{ old('meta_title', $setting->meta_title) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
        <p class="text-xs text-ink-500 mt-1">Best practice: 50-60 characters</p>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Meta Description</label>
        <textarea name="meta_description" rows="3" maxlength="500" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ old('meta_description', $setting->meta_description) }}</textarea>
        <p class="text-xs text-ink-500 mt-1">Best practice: 150-160 characters</p>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Meta Keywords</label>
        <input name="meta_keywords" value="{{ old('meta_keywords', $setting->meta_keywords) }}" placeholder="comma, separated, keywords" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
    </div>

    <div class="pt-4 border-t border-ink-100">
        <h3 class="font-bold mb-3"><i class="fa-solid fa-mobile-screen fa-fw"></i> Open Graph (Social Sharing)</h3>
        <div class="space-y-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">OG Title</label>
                <input name="og_title" value="{{ old('og_title', $setting->og_title) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">OG Description</label>
                <textarea name="og_description" rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ old('og_description', $setting->og_description) }}</textarea>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">OG Image</label>
                @if($setting->og_image)
                    <div class="mt-2 mb-3">
                        <img src="{{ str_starts_with($setting->og_image, 'http') ? $setting->og_image : asset('storage/' . $setting->og_image) }}" class="w-48 rounded-lg border">
                        <p class="text-xs text-ink-500 mt-1">Current image. Upload new to replace.</p>
                    </div>
                @endif
                <input type="file" name="og_image" accept="image/*" class="w-full text-sm">
                <p class="text-xs text-ink-500 mt-1">Recommended: 1200×630 px</p>
            </div>
        </div>
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($setting->is_active) class="rounded w-5 h-5">
        <span class="font-bold">Active</span>
    </label>

    <div class="flex gap-3 pt-4 border-t border-ink-100">
        <button type="submit" class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Update SEO Page</button>
        <a href="{{ route('seo.settings.index') }}" class="px-6 py-3 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>

    <div class="pt-4 border-t border-ink-100 text-xs text-ink-500">
        Last updated: {{ $setting->updated_at?->format('d M Y, h:i A') ?? 'Never' }}
    </div>
</form>

@endsection