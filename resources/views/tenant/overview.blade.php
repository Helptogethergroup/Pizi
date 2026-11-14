@extends('layouts.tenant')
@section('title', 'My details')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-3 max-w-4xl">
    <div>
        <h1 class="font-display font-black text-3xl">My details</h1>
        <p class="text-ink-900/60 mt-1">Everything your PG owner has about you, in one place.</p>
    </div>
    <a href="{{ route('tenant.profile') }}" class="px-4 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl text-sm font-bold">Edit my details</a>
</div>

@include('partials.tenant-overview', ['tenant' => $tenant, 'forOwner' => false])

@endsection
