@extends('layouts.tenant')
@section('title', 'Notice Period')
@section('content')

<h1 class="font-display font-black text-3xl text-ink-950">Notice Period</h1>
<p class="text-ink-900/60 mt-1">PG chodne ka notice yahaan submit kare</p>

@if($tenant->notice_date)
    <div class="mt-6 bg-amber-50 border border-amber-200 rounded-2xl p-6">
        <h3 class="font-bold text-lg">⏳ Notice Active</h3>
        <p class="text-sm mt-1">Aapne notice de diya hai. Move-out date: <strong>{{ \Carbon\Carbon::parse($tenant->notice_date)->format('d M Y') }}</strong></p>
        @if($tenant->notice_reason)
            <p class="text-sm mt-2 italic">Reason: {{ $tenant->notice_reason }}</p>
        @endif
    </div>
@endif

<form method="POST" action="{{ route('tenant.notice.submit') }}" class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-6 space-y-4 max-w-2xl">
    @csrf

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-900">
        <strong>📌 Note:</strong> Standard notice period generally 30 days hota hai. Apne agreement check kare exact terms ke liye.
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Move-out Date *</label>
        <input type="date" name="notice_date" required min="{{ now()->addDays(1)->format('Y-m-d') }}" value="{{ old('notice_date', now()->addDays(30)->format('Y-m-d')) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
        <p class="text-xs text-ink-900/50 mt-1">Kis date pe PG chodna hai</p>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Reason (optional)</label>
        <textarea name="reason" rows="4" placeholder="Why are you leaving? (job change, family, etc.)" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">{{ old('reason') }}</textarea>
    </div>

    <button type="submit" class="px-8 py-3 bg-rose-500 hover:bg-rose-600 text-white rounded-xl font-bold" onclick="return confirm('Are you sure? Owner will be notified.')">
        📤 Submit Notice
    </button>
</form>

@endsection