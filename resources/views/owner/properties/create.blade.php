@extends('layouts.dashboard')
@section('title', 'Add Property')
@section('content')
<a href="{{ route('owner.properties.index') }}" class="text-sm text-ink-900/60">← Back to my properties</a>
<h1 class="font-display font-black text-3xl mt-2 mb-6">Add a new property</h1>
@include('owner.properties._form')
@endsection
