@extends('layouts.dashboard')
@section('title', 'New Blog')
@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.blogs.index') }}" class="text-2xl">←</a>
    <h1 class="font-display font-black text-3xl">New Blog Post</h1>
</div>

@if($errors->any())
    <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

@include('admin.blogs._form', ['action' => route('admin.blogs.store'), 'blog' => null])

@endsection