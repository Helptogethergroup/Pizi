@extends('layouts.dashboard')
@section('title', $tenant->name . ' · Overview')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-3 max-w-4xl">
    <div>
        <h1 class="font-display font-black text-3xl">Tenant overview</h1>
        <p class="text-ink-900/60 mt-1">Everything about this tenant in one place.</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="window.print()" class="px-4 py-2.5 bg-white border border-ink-200 rounded-xl text-sm font-bold"><i class="fa-solid fa-print fa-fw"></i> Print</button>
        <a href="{{ route('owner.tenants.show', $tenant) }}" class="px-4 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl text-sm font-bold">Manage tenant</a>
    </div>
</div>

@if(session('success'))<div class="mb-4 max-w-4xl p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold">{{ session('success') }}</div>@endif

@include('partials.tenant-overview', ['tenant' => $tenant, 'forOwner' => true])

@endsection
