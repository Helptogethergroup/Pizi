@extends('layouts.dashboard')
@section('title', 'Lead — ' . $lead->name)
@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('telecaller.leads.index') }}" class="text-2xl">←</a>
    <div>
        <h1 class="font-display font-black text-2xl">{{ $lead->name }}</h1>
        <p class="text-xs text-ink-900/60">Lead #{{ $lead->id }} · {{ ucfirst($lead->source ?? 'website') }} · {{ $lead->created_at->format('d M, h:i A') }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- LEFT: Lead info + edit form --}}
    <div class="space-y-4">

        {{-- Quick contact --}}
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <div class="grid grid-cols-2 gap-2">
                <a href="tel:{{ $lead->phone }}" class="flex items-center justify-center gap-2 py-3 bg-emerald-500 text-white rounded-xl font-bold">📞 Call</a>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" class="flex items-center justify-center gap-2 py-3 bg-emerald-600 text-white rounded-xl font-bold">💬 WhatsApp</a>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
                <div><div class="text-xs text-ink-900/50 uppercase">Phone</div><div class="font-bold">{{ $lead->phone }}</div></div>
                <div><div class="text-xs text-ink-900/50 uppercase">Email</div><div class="font-bold">{{ $lead->email ?? '—' }}</div></div>
            </div>
        </div>

        {{-- Edit form (live updates) --}}
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold text-lg mb-3">📝 Live update preferences</h3>
            <p class="text-xs text-ink-900/60 mb-4">Change budget or area — matching properties on the right update instantly.</p>

            <form id="leadEditForm" method="POST" action="{{ route('telecaller.leads.update', $lead) }}">
                @csrf @method('PATCH')

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase">Status</label>
                        <select name="status" class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                            @foreach(['new'=>'New','contacted'=>'Contacted','interested'=>'Interested','follow_up'=>'Follow up','visit_scheduled'=>'Visit scheduled','visit_done'=>'Visit done','closed_won'=>'✓ Closed Won','closed_lost'=>'✗ Closed Lost','not_interested'=>'Not interested'] as $key => $label)
                                <option value="{{ $key }}" @selected($lead->status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase">📍 Preferred Locality</label>
                        <input name="preferred_locality" value="{{ $lead->preferred_locality }}"
                               class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                               placeholder="e.g. Mukherjee Nagar">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase">🏙 Preferred City</label>
                        <input name="preferred_city" value="{{ $lead->preferred_city }}"
                               class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                               placeholder="e.g. Delhi">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold text-ink-900/60 uppercase">💰 Budget Min</label>
                            <input type="number" name="budget_min" value="{{ $lead->budget_min }}"
                                   class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                                   placeholder="6000">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-ink-900/60 uppercase">💰 Budget Max</label>
                            <input type="number" name="budget_max" value="{{ $lead->budget_max }}"
                                   class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                                   placeholder="10000">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase">👤 Gender preference</label>
                        <select name="preferred_gender" class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                            <option value="">—</option>
                            <option value="male" @selected($lead->preferred_gender === 'male')>Male</option>
                            <option value="female" @selected($lead->preferred_gender === 'female')>Female</option>
                            <option value="unisex" @selected($lead->preferred_gender === 'unisex')>Unisex / No preference</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase">📝 Notes</label>
                        <textarea name="notes" rows="3"
                                  class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                                  placeholder="Tenant feedback, preferences, follow-up info...">{{ $lead->notes }}</textarea>
                    </div>
                </div>

                <div id="saveStatus" class="text-xs mt-3 h-4"></div>
            </form>
        </div>

        {{-- Schedule visit --}}
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold text-lg mb-3">📅 Schedule site visit</h3>
            <form method="POST" action="{{ route('telecaller.leads.visit', $lead) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-xs font-bold text-ink-900/60 uppercase">Property</label>
                    <select name="property_id" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                        <option value="">Select property...</option>
                        @foreach($matchingProperties as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->locality?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-ink-900/60 uppercase">Date & time</label>
                    <input type="datetime-local" name="scheduled_at" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                </div>
                <div>
                    <label class="text-xs font-bold text-ink-900/60 uppercase">Field Executive</label>
                    <select name="field_executive_id" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                        <option value="">— Auto-assign —</option>
                        @foreach($fieldExecs as $fe)
                            <option value="{{ $fe->id }}">{{ $fe->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="w-full py-2.5 bg-coral-500 text-white rounded-lg font-bold">Schedule visit</button>
            </form>
        </div>
    </div>

    {{-- RIGHT: Live matching properties --}}
    <div>
        <div class="bg-cream p-5 rounded-2xl sticky top-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-display font-bold text-lg">🎯 Matching Properties</h3>
                <span id="matchCount" class="text-xs text-ink-900/60">{{ $matchingProperties->count() }} matches</span>
            </div>
            <p class="text-xs text-ink-900/60 mb-4">Live results based on lead's current budget + area. Send via WhatsApp instantly.</p>

            <div id="matchingResults">
                @include('telecaller._matching_properties', compact('matchingProperties'))
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const form = document.getElementById('leadEditForm');
const status = document.getElementById('saveStatus');
const matchingDiv = document.getElementById('matchingResults');
const matchCount = document.getElementById('matchCount');
let saveTimer = null;

// Auto-save on field change (debounced 600ms)
document.querySelectorAll('.liveField').forEach(field => {
    field.addEventListener('input', triggerSave);
    field.addEventListener('change', triggerSave);
});

function triggerSave() {
    status.textContent = '⏳ Saving...';
    status.className = 'text-xs mt-3 h-4 text-ink-900/60';

    clearTimeout(saveTimer);
    saveTimer = setTimeout(saveForm, 600);
}

async function saveForm() {
    const formData = new FormData(form);

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!res.ok) throw new Error('Save failed');

        const data = await res.json();

        // Update matching properties live
        if (data.matches_html) {
            matchingDiv.innerHTML = data.matches_html;
            // Recount
            const cards = matchingDiv.querySelectorAll('.bg-white.border').length;
            matchCount.textContent = cards + ' matches';
        }

        status.textContent = '✓ Saved · matches updated';
        status.className = 'text-xs mt-3 h-4 text-emerald-600 font-bold';

        setTimeout(() => { status.textContent = ''; }, 2000);
    } catch (err) {
        status.textContent = '⚠️ Save failed — try again';
        status.className = 'text-xs mt-3 h-4 text-rose-600 font-bold';
    }
}

// WhatsApp share — open in new tab
function shareWA(propertyId) {
    const url = '/telecaller/leads/{{ $lead->id }}/whatsapp/' + propertyId;
    window.open(url, '_blank');
}
</script>
@endpush

@endsection