@extends('layouts.dashboard')
@section('title', 'Lead — ' . $lead->name)
@section('content')

<a href="{{ route('telecaller.leads.index') }}" class="text-sm text-ink-900/60">← Back to leads</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
    {{-- Lead info & actions --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-8 rounded-2xl border border-ink-900/10">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="font-display font-black text-3xl">{{ $lead->name }}</h1>
                    <p class="text-ink-900/60 mt-1">📞 <a href="tel:{{ $lead->phone }}" class="font-semibold text-ink-900 hover:text-coral-600">{{ $lead->phone }}</a> · {{ $lead->email ?? 'No email' }}</p>
                    <span class="inline-block mt-3 px-3 py-1 rounded-full text-xs font-semibold {{ $lead->statusBadge() }}">{{ str_replace('_',' ',$lead->status) }}</span>
                </div>
                <div class="text-right text-xs text-ink-900/50">
                    <div>Source: <strong>{{ $lead->source }}</strong></div>
                    <div>Created: {{ $lead->created_at->format('d M Y, h:i A') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
                <a href="tel:{{ $lead->phone }}" class="text-center py-3 bg-emerald-500 text-white rounded-xl font-bold">📞 Call</a>
                @if($lead->property)
                <a href="{{ route('telecaller.leads.whatsapp', [$lead, $lead->property]) }}" target="_blank" class="text-center py-3 bg-emerald-600 text-white rounded-xl font-bold">💬 Send PG details</a>
                @endif
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" class="text-center py-3 bg-coral-500 text-white rounded-xl font-bold">WhatsApp</a>
                <a href="mailto:{{ $lead->email }}" class="text-center py-3 border border-ink-900/15 rounded-xl font-bold">✉ Email</a>
            </div>

            @if($lead->message)
                <div class="mt-6 p-4 bg-cream rounded-xl">
                    <div class="text-xs font-semibold text-ink-900/60 uppercase">Lead's message</div>
                    <p class="mt-1">{{ $lead->message }}</p>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6 text-sm">
                <div><div class="text-xs text-ink-900/60 uppercase">Budget</div><div class="font-semibold">{{ $lead->budget_min ? '₹'.number_format($lead->budget_min) : '?' }} – {{ $lead->budget_max ? '₹'.number_format($lead->budget_max) : '?' }}</div></div>
                <div><div class="text-xs text-ink-900/60 uppercase">Move-in</div><div class="font-semibold">{{ $lead->move_in_date?->format('d M Y') ?? 'Flexible' }}</div></div>
                <div><div class="text-xs text-ink-900/60 uppercase">Preferred locality</div><div class="font-semibold">{{ $lead->preferred_locality ?: '—' }}</div></div>
            </div>
        </div>

        {{-- Update status --}}
        <div class="bg-white p-8 rounded-2xl border border-ink-900/10">
            <h2 class="font-display font-bold text-xl mb-4">Update status</h2>
            <form method="POST" action="{{ route('telecaller.leads.update', $lead) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">New status</label>
                        <select name="status" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                            @foreach(['contacted','interested','follow_up','visit_scheduled','visit_done','closed_won','closed_lost','junk'] as $s)
                                <option value="{{ $s }}" @selected($lead->status === $s)>{{ str_replace('_',' ',$s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">Next follow-up</label>
                        <input type="datetime-local" name="next_follow_up_at" value="{{ $lead->next_follow_up_at?->format('Y-m-d\TH:i') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Notes</label>
                    <textarea name="telecaller_notes" rows="3" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">{{ $lead->telecaller_notes }}</textarea>
                </div>
                <button class="px-6 py-3 bg-coral-500 text-white rounded-xl font-bold">Save update</button>
            </form>
        </div>

        {{-- Schedule visit --}}
        <div class="bg-white p-8 rounded-2xl border border-ink-900/10">
            <h2 class="font-display font-bold text-xl mb-4">Schedule a site visit</h2>
            <form method="POST" action="{{ route('telecaller.leads.visit', $lead) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <input type="hidden" name="property_id" value="{{ $lead->property_id }}">
                    <div>
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">Date & time</label>
                        <input type="datetime-local" name="scheduled_at" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">Property</label>
                        <input value="{{ $lead->property?->name ?? 'No property linked' }}" disabled class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 bg-ink-900/5">
                    </div>
                </div>
                <button class="px-6 py-3 bg-ink-900 text-cream rounded-xl font-bold">Schedule visit</button>
            </form>
        </div>
    </div>

    {{-- Property snapshot --}}
    <div>
        @if($lead->property)
            <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden sticky top-6">
                <img src="{{ $lead->property->cover_url }}" class="w-full aspect-[4/3] object-cover">
                <div class="p-6">
                    <h3 class="font-display font-bold text-xl">{{ $lead->property->name }}</h3>
                    <p class="text-sm text-ink-900/60 mt-1">{{ $lead->property->locality?->name }}</p>
                    <div class="font-display font-black text-2xl text-coral-500 mt-3">{{ $lead->property->rent_range }}</div>
                    <a href="{{ route('property.show', $lead->property->slug) }}" target="_blank" class="block text-center mt-4 py-2 border border-ink-900/15 rounded-lg text-sm font-semibold">View on site →</a>
                </div>
            </div>
        @else
            <div class="bg-white p-6 rounded-2xl border border-ink-900/10 text-center text-ink-900/60">
                General inquiry — no specific property attached.
            </div>
        @endif
    </div>
</div>
@endsection
