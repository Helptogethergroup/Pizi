@extends('layouts.tenant')
@section('title', 'Notice Period')
@section('content')

<h1 class="font-display font-black text-3xl text-ink-950">Notice Period</h1>
<p class="text-ink-900/60 mt-1">Submit Your PG Move-Out Notice Here</p>

@if(!$tenant->property_id)
    <div class="mt-6 bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center max-w-2xl">
        <div class="text-5xl mb-3">📤</div>
        <h3 class="font-bold text-lg">No PG assigned yet</h3>
        <p class="text-amber-800 mt-2">You need to complete the PG move-in process first. After that, you can use the notice period feature..</p>
    </div>
@else

    @if($tenant->notice_date)
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded-2xl p-6 max-w-2xl">
            <h3 class="font-bold text-lg">⏳ Notice Active</h3>
            <p class="text-sm mt-1">Your notice has already been submitted.. Move-out date: <strong>{{ \Carbon\Carbon::parse($tenant->notice_date)->format('d M Y') }}</strong></p>
            @if($tenant->notice_reason)
                <p class="text-sm mt-2 italic">Reason: {{ $tenant->notice_reason }}</p>
            @endif
            <p class="text-xs text-amber-800 mt-3">Your settlement amount will be calculated by the owner, who will contact you with the details.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.notice.submit') }}" class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-6 space-y-4 max-w-2xl">
        @csrf

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-900">
            <strong>📌 Note:</strong> Standard Notice Period: 30 Days  Please refer to your agreement for the exact notice period and applicable terms.


        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Move-out Date *</label>
            <input type="date" name="notice_date" required min="{{ now()->addDays(1)->format('Y-m-d') }}" value="{{ old('notice_date', now()->addDays(30)->format('Y-m-d')) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
            <p class="text-xs text-ink-900/50 mt-1">Kis date pe PG chodna hai</p>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Reason (optional)</label>
            <textarea name="reason" rows="4" placeholder="Job change, family, etc." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">{{ old('reason') }}</textarea>
        </div>

        <button type="submit" class="px-8 py-3 bg-rose-500 hover:bg-rose-600 text-white rounded-xl font-bold" onclick="return confirm('Sure? Owner will be notified.')">
            📤 Submit Notice
        </button>
    </form>

@endif

@endsection