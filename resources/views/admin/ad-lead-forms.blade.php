@extends('layouts.dashboard')
@section('title', 'Ad Lead Form Mapping — Admin')
@section('content')

<h1 class="font-display font-black text-3xl mb-2">📋 Ad Lead Form Mapping</h1>
<p class="text-ink-900/60 mb-6">Register each Meta/Google Ads lead-form ID here — mark which form is for Owners, which is for Tenants. Leads coming from that form will then be classified automatically (no more "Unknown").</p>

@if(session('success'))
    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 px-4 py-3 rounded text-emerald-700 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white p-6 rounded-2xl border border-ink-100 mb-8">
    <h2 class="font-display font-bold text-lg mb-4">+ Add / Update Mapping</h2>
    <form method="POST" action="{{ route('admin.ad-lead-forms.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        @csrf
        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Platform</label>
            <select name="platform" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                <option value="meta">Meta (Facebook/Instagram)</option>
                <option value="google">Google Ads</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Form ID</label>
            <input name="form_id" required placeholder="e.g. 1107893524912631" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Label (optional)</label>
            <input name="label" placeholder="e.g. Owner - List your PG" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">This form is for</label>
            <select name="inquiry_type" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                <option value="tenant">🧳 Tenant</option>
                <option value="owner">🏠 Owner</option>
            </select>
        </div>
        <div class="md:col-span-4">
            <button class="px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Save Mapping</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl border border-ink-100 overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-ink-900/5 text-left text-xs uppercase text-ink-900/60">
            <tr>
                <th class="px-4 py-3">Platform</th>
                <th>Form ID</th>
                <th>Label</th>
                <th>Type</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($mappings as $m)
                <tr class="border-t border-ink-900/5">
                    <td class="px-4 py-3">{{ $m->platform === 'meta' ? '📘 Meta' : '🔴 Google' }}</td>
                    <td class="font-mono text-xs">{{ $m->form_id }}</td>
                    <td>{{ $m->label ?? '—' }}</td>
                    <td>{{ $m->inquiry_type === 'owner' ? '🏠 Owner' : '🧳 Tenant' }}</td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.ad-lead-forms.destroy', $m) }}" onsubmit="return confirm('Remove this mapping?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 font-bold text-xs">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-8 text-center text-ink-900/50">No mappings yet — add one above.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
