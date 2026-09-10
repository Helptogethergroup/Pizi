@extends('layouts.dashboard')
@section('title', 'Add Owner Prospect')
@section('content')

<a href="{{ route('telecaller.owner-prospects.index') }}" class="text-sm text-ink-900/60">← Back to owner prospects</a>
<h1 class="font-display font-black text-3xl mt-2 mb-6">+ Add Owner Prospect</h1>

@if($errors->any())
    <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 px-4 py-3 rounded">
        <ul class="list-disc pl-5 text-amber-700 text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('telecaller.owner-prospects.store') }}" class="space-y-5 max-w-xl bg-white p-6 rounded-2xl border border-ink-900/10">
    @csrf
    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Owner Name *</label>
        <input name="name" required value="{{ old('name') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
    </div>
    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Phone Number *</label>
        <input name="phone" required value="{{ old('phone') }}" placeholder="10-digit mobile" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
    </div>
    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Source</label>
        <select name="source" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            <option value="">— Select —</option>
            <option value="cold_call" @selected(old('source')=='cold_call')>Cold call</option>
            <option value="referral" @selected(old('source')=='referral')>Referral</option>
            <option value="market_visit" @selected(old('source')=='market_visit')>Market visit</option>
            <option value="other" @selected(old('source')=='other')>Other</option>
        </select>
    </div>
    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Notes</label>
        <textarea name="notes" rows="3" placeholder="PG location, size, any context..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">{{ old('notes') }}</textarea>
    </div>
    <div class="flex gap-3">
        <button class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">✓ Add Prospect</button>
        <a href="{{ route('telecaller.owner-prospects.index') }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

@endsection
