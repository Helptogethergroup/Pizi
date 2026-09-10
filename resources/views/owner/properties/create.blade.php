@extends('layouts.dashboard')
@section('title', 'Add Property')
@section('content')

<a href="{{ route('owner.properties.index') }}" class="text-sm text-ink-900/60">← Back to my properties</a>

<h1 class="font-display font-black text-3xl mt-2 mb-6">Add a new property</h1>

@if(session('error'))
    <div class="mb-6 bg-rose-50 border-l-4 border-rose-500 px-4 py-3 rounded">
        <p class="text-rose-700 font-bold">❌ Error</p>
        <p class="text-rose-600 text-sm mt-1">{{ session('error') }}</p>
    </div>
@endif

@if($errors->any())
    <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 px-4 py-3 rounded">
        <p class="text-amber-700 font-bold">⚠️ Please fix these fields:</p>
        <ul class="list-disc pl-5 text-amber-600 text-sm mt-2 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('owner.properties._form')

@endsection