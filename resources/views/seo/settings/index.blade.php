@extends('layouts.dashboard')
@section('title', 'SEO Settings')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">🔍 SEO Settings</h1>
        <p class="text-ink-900/60 mt-1">Page-wise meta tags, OG tags, and structured data</p>
    </div>
    <a href="{{ route('seo.settings.create') }}" class="px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ New SEO Page</a>
</div>

<form class="flex gap-2 mb-4 bg-white p-3 rounded-xl border border-ink-900/10">
    <input name="search" value="{{ request('search') }}" placeholder="Search by page name or key…" class="flex-1 px-3 py-2 rounded-lg border border-ink-900/15">
    <select name="status" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <option value="">All</option>
        <option value="active" @selected(request('status')==='active')>Active</option>
        <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
    </select>
    <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg font-semibold">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
<table class="w-full text-sm">
    <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
        <tr>
            <th class="px-4 py-3">Page</th>
            <th>Meta Title</th>
            <th>Status</th>
            <th>Updated</th>
            <th class="text-right pr-4">Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse($pages as $p)
        <tr class="border-t border-ink-900/5 hover:bg-cream/40">
            <td class="px-4 py-3">
                <div class="font-semibold">{{ $p->page_label }}</div>
                <div class="text-xs text-ink-900/50 font-mono">{{ $p->page_key }}</div>
            </td>
            <td class="text-xs">{{ Str::limit($p->meta_title, 60) ?? '—' }}</td>
            <td>
                @if($p->is_active)
                    <span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">✓ Active</span>
                @else
                    <span class="px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-600">Inactive</span>
                @endif
            </td>
            <td class="text-xs">{{ $p->updated_at->format('d M, h:i A') }}<br><span class="text-ink-900/50">by {{ $p->updatedBy?->name ?? '—' }}</span></td>
            <td class="px-4 py-3 text-right">
                <div class="flex flex-wrap gap-1 justify-end">
                    <a href="{{ route('seo.settings.edit', $p) }}" class="text-xs px-2 py-1 rounded-lg bg-blue-500 text-white">✏️ Edit</a>
                    <form method="POST" action="{{ route('seo.settings.toggle', $p) }}" class="inline">@csrf @method('PATCH')
                        <button class="text-xs px-2 py-1 rounded-lg {{ $p->is_active ? 'bg-amber-500' : 'bg-emerald-500' }} text-white">
                            {{ $p->is_active ? '⏸ Deactivate' : '✓ Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('seo.settings.destroy', $p) }}" class="inline" onsubmit="return confirm('Delete this SEO page setting?');">@csrf @method('DELETE')
                        <button class="text-xs px-2 py-1 rounded-lg bg-rose-500 text-white">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="px-4 py-12 text-center text-ink-900/50">No SEO pages yet. <a href="{{ route('seo.settings.create') }}" class="text-coral-600 font-semibold">+ Create the first one</a></td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div class="mt-6">{{ $pages->links() }}</div>

@endsection