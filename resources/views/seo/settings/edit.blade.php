@extends('layouts.dashboard')
@section('title', 'Edit SEO Page')
@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('seo.settings.index') }}" class="text-2xl">←</a>
    <h1 class="font-display font-black text-3xl">Edit SEO Settings</h1>
</div>

<form method="POST" action="{{ route('seo.settings.update', $setting) }}" enctype="multipart/form-data">
    @csrf @method('PATCH')
    @include('seo.settings._form')
</form>

@endsection