@extends('layouts.dashboard')
@section('title', 'Edit Blog')
@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('owner.blogs.index') }}" class="text-2xl">←</a>
    <h1 class="font-display font-black text-3xl">Edit Blog</h1>
</div>

<form method="POST" action="{{ route('owner.blogs.update', $blog) }}" enctype="multipart/form-data">
    @csrf @method('PATCH')
    @include('admin.blogs._form')
</form>

@endsection