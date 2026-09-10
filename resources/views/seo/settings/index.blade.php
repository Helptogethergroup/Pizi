@extends('layouts.dashboard')
@section('title', 'SEO Settings')
@section('content')

<div class="flex justify-between items-center mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">SEO Settings</h1>
        <p class="text-ink-900/60 mt-1">All pages with SEO configuration</p>
    </div>
    <a href="{{ route('seo.settings.create') }}" class="px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold transition shadow-lg shadow-coral-500/30">+ Add SEO Page</a>
</div>

@if($settings->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3">🔍</div>
        <p class="text-ink-700 mb-4">No SEO pages yet.</p>
        <a href="{{ route('seo.settings.create') }}" class="inline-block px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ Create First Page</a>
    </div>
@else
    <div class="bg-white rounded-2xl border border-ink-100 divide-y divide-ink-100">
        @foreach($settings as $s)
            <div class="p-5 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <h3 class="font-bold text-lg">{{ $s->page_label }}</h3>
                        @if($s->is_active)
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Active</span>
                        @else
                            <span class="text-xs bg-ink-100 text-ink-700 px-2 py-0.5 rounded-full">Inactive</span>
                        @endif
                    </div>
                    <p class="text-xs text-ink-500">Key: <code class="bg-cream px-2 py-0.5 rounded">{{ $s->page_key }}</code></p>
                    @if($s->meta_title)
                        <p class="text-sm text-ink-700 mt-2 truncate">{{ $s->meta_title }}</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('seo.settings.edit', $s) }}" class="px-3 py-1.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg text-sm font-bold">Edit</a>
                    <form method="POST" action="{{ route('seo.settings.destroy', $s) }}" onsubmit="return confirm('Delete this SEO page?')" class="inline">
                        @csrf @method('DELETE')
                        <button class="px-3 py-1.5 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-sm font-bold">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $settings->links() }}</div>
@endif

@endsection