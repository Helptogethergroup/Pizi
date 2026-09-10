@extends('layouts.dashboard')
@section('title', 'SEO Dashboard')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl">SEO Dashboard</h1>
    <p class="text-ink-900/60 mt-1">Manage SEO settings for all pages</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white p-5 rounded-2xl border border-ink-100">
        <div class="text-3xl mb-2">📄</div>
        <div class="text-xs text-ink-500 uppercase font-bold">Total Pages</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total_pages'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-emerald-200">
        <div class="text-3xl mb-2">✅</div>
        <div class="text-xs text-emerald-700 uppercase font-bold">Active Pages</div>
        <div class="font-display font-black text-3xl text-emerald-700 mt-1">{{ $stats['active_pages'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-rose-200">
        <div class="text-3xl mb-2">⚠️</div>
        <div class="text-xs text-rose-700 uppercase font-bold">Missing Meta</div>
        <div class="font-display font-black text-3xl text-rose-700 mt-1">{{ $stats['missing_meta'] }}</div>
    </div>
</div>

<div class="flex justify-between items-center mb-4">
    <h2 class="font-display font-bold text-xl">Recently Updated</h2>
    <a href="{{ route('seo.settings.index') }}" class="text-coral-500 font-bold text-sm">View all →</a>
</div>

<div class="bg-white rounded-2xl border border-ink-100 divide-y divide-ink-100">
    @foreach($recentSettings as $s)
        <div class="p-4 flex items-center justify-between">
            <div>
                <h3 class="font-bold">{{ $s->page_label }}</h3>
                <p class="text-xs text-ink-500">{{ $s->page_key }} · Updated {{ $s->updated_at->diffForHumans() }}</p>
            </div>
            <a href="{{ route('seo.settings.edit', $s) }}" class="px-3 py-1.5 bg-coral-500 text-white rounded-lg text-sm font-bold">Edit</a>
        </div>
    @endforeach
</div>

@endsection