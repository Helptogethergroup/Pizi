@extends('layouts.dashboard')
@section('title', 'New SEO Page')
@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('seo.settings.index') }}" class="text-2xl">←</a>
    <h1 class="font-display font-black text-3xl">New SEO Page Settings</h1>
</div>

<form method="POST" action="{{ route('seo.settings.store') }}" enctype="multipart/form-data">
    @csrf
    @include('seo.settings._form')
</form>

@endsection