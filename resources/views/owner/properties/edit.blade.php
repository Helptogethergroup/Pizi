@extends('layouts.dashboard')
@section('title', 'Edit Property')
@section('content')
<a href="{{ route('owner.properties.index') }}" class="text-sm text-ink-900/60">← Back to my properties</a>
<h1 class="font-display font-black text-3xl mt-2 mb-6">Edit property</h1>

{{-- ERROR MESSAGE --}}
@if(session('error'))
    <div class="mb-6 bg-rose-50 border-l-4 border-rose-500 px-4 py-3 rounded">
        <p class="text-rose-700 font-bold"><i class="fa-solid fa-circle-xmark fa-fw"></i> Error</p>
        <p class="text-rose-600 text-sm mt-1">{{ session('error') }}</p>
    </div>
@endif

{{-- SUCCESS MESSAGE --}}
@if(session('success'))
    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 px-4 py-3 rounded">
        <p class="text-emerald-700 font-bold"><i class="fa-solid fa-circle-check fa-fw"></i> Success</p>
        <p class="text-emerald-600 text-sm mt-1">{{ session('success') }}</p>
    </div>
@endif

{{-- VALIDATION ERRORS --}}
@if($errors->any())
    <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 px-4 py-3 rounded">
        <p class="text-amber-700 font-bold"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Please fix these fields:</p>
        <ul class="list-disc pl-5 text-amber-600 text-sm mt-2 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('owner.properties._form')
@endsection