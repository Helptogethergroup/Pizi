@extends('layouts.dashboard')
@section('title', 'Lead — ' . $lead->name)
@section('content')

@php
    $isSuccess = in_array($lead->call_status, ['contacted', 'interested']);
    $isClosed  = $lead->call_status === 'not_interested';
    $attempts  = (int) ($lead->call_attempts ?? 0);
    // A verified lead needs a city so it actually reaches the right owners —
    // without it, it falls into the "city unknown" bucket shown to everyone.
    $hasCity = $lead->property_id || $lead->preferred_city;
@endphp

<div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
    <div class="flex items-center gap-3">
        <a href="{{ route('telecaller.leads.index') }}" class="text-2xl">←</a>
        <div>
            <h1 class="font-display font-black text-2xl">{{ $lead->name }}</h1>
            <p class="text-xs text-ink-900/60">Lead #{{ $lead->id }} · {{ ucfirst($lead->source ?? 'website') }} · {{ $lead->created_at->format('d M, h:i A') }}</p>
        </div>
    </div>

    @if($lead->lead_type === 'verified')
        <span class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 text-sm font-bold"><i class="fa-solid fa-circle-check fa-fw"></i> Verified Lead</span>
    @elseif($lead->lead_type === 'converted')
        <span class="px-4 py-2 rounded-xl bg-coral-100 text-coral-700 text-sm font-bold"><i class="fa-solid fa-trophy fa-fw"></i> Converted Lead</span>
    @elseif($isSuccess && $hasCity && !$isOwnerClaimed)
        <form method="POST" action="{{ route('telecaller.leads.verify', $lead) }}" onsubmit="return confirm('Mark this lead as Verified? Unlocking it will now cost owners 40 credits instead of 20 (higher trust, higher value).')">
            @csrf @method('PATCH')
            <button class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold">✓ Mark as Verified</button>
        </form>
    @elseif($isSuccess && !$hasCity && !$isOwnerClaimed)
        <span class="px-4 py-2 rounded-xl bg-amber-100 text-amber-700 text-sm font-bold" title="Fill in the Preferred City field below, then you can Verify"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Fill city to Verify</span>
    @elseif(!$isOwnerClaimed)
        <span class="px-4 py-2 rounded-xl bg-ink-900/5 text-ink-900/50 text-sm font-bold" title="Mark the call as Contacted or Interested first"><i class="fa-solid fa-lock fa-fw"></i> Verify (call first)</span>
    @endif
</div>

@if($isOwnerClaimed)
<div class="bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-xl text-sm mb-6 font-semibold">
    <i class="fa-solid fa-lock fa-fw"></i> This lead has been claimed by PG owner: {{ $isOwnerClaimed }}. No further edits or calls allowed on this lead.
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- LEFT: Lead info + edit form --}}
    <div class="space-y-4">

        {{-- Quick contact --}}
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <div class="grid grid-cols-2 gap-2">
                <a href="tel:{{ $lead->phone }}" class="flex items-center justify-center gap-2 py-3 bg-emerald-500 text-white rounded-xl font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call</a>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" class="flex items-center justify-center gap-2 py-3 bg-emerald-600 text-white rounded-xl font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp</a>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
                <div><div class="text-xs text-ink-900/50 uppercase">Phone</div><div class="font-bold">{{ $lead->phone }}</div></div>
                <div><div class="text-xs text-ink-900/50 uppercase">Email</div><div class="font-bold">{{ $lead->email ?? '—' }}</div></div>
            </div>
        </div>

        {{-- Edit form (live updates) --}}
        <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
            <h3 class="font-display font-bold text-lg mb-3"><i class="fa-solid fa-pen-to-square fa-fw"></i> Live update preferences</h3>
            <p class="text-xs text-ink-900/60 mb-4">Change budget or area — matching properties on the right update instantly.</p>

            <form id="leadEditForm" method="POST" action="{{ route('telecaller.leads.update', $lead) }}">
                @csrf @method('PATCH')
                <fieldset {{ $isOwnerClaimed ? 'disabled style=opacity:0.5' : '' }}>

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
                        <label class="text-xs font-bold text-ink-900/60 uppercase"><i class="fa-solid fa-location-dot fa-fw"></i> Preferred Locality</label>
                        <input name="preferred_locality" value="{{ $lead->preferred_locality }}"
                               class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                               placeholder="e.g. Mukherjee Nagar">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase"><i class="fa-solid fa-city fa-fw"></i> Preferred City</label>
                        <select name="preferred_city" class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                            <option value="">— Select city —</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->name }}" @selected(strcasecmp($lead->preferred_city ?? '', $city->name) === 0)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        @if($lead->preferred_city && !$cities->contains(fn($c) => strcasecmp($c->name, $lead->preferred_city) === 0))
                            <p class="text-xs text-amber-600 mt-1"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Current value "{{ $lead->preferred_city }}" doesn't match any city in the list — please pick the correct one above.</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold text-ink-900/60 uppercase"><i class="fa-solid fa-sack-dollar fa-fw"></i> Budget Min</label>
                            <input type="number" name="budget_min" value="{{ $lead->budget_min }}"
                                   class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                                   placeholder="6000">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-ink-900/60 uppercase"><i class="fa-solid fa-sack-dollar fa-fw"></i> Budget Max</label>
                            <input type="number" name="budget_max" value="{{ $lead->budget_max }}"
                                   class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                                   placeholder="10000">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase"><i class="fa-solid fa-user fa-fw"></i> Gender preference</label>
                        <select name="preferred_gender" class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                            <option value="">—</option>
                            <option value="male" @selected($lead->preferred_gender === 'male')>Male</option>
                            <option value="female" @selected($lead->preferred_gender === 'female')>Female</option>
                            <option value="unisex" @selected($lead->preferred_gender === 'unisex')>Unisex / No preference</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-ink-900/60 uppercase"><i class="fa-solid fa-pen-to-square fa-fw"></i> Notes</label>
                        <textarea name="notes" rows="3"
                                  class="liveField w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15"
                                  placeholder="Tenant feedback, preferences, follow-up info...">{{ $lead->notes }}</textarea>
                    </div>
                </div>

                <div id="saveStatus" class="text-xs mt-3 h-4">
                       <button class="w-full py-2.5 bg-coral-500 text-white rounded-lg font-bold">Update</button>
                </div>
                </fieldset>
            </form>
        </div>

{{-- ===================== STEP 2: ASSIGN FIELD EXECUTIVE ===================== --}}
        <div class="bg-white border border-ink-900/10 rounded-2xl p-5 relative overflow-hidden">
            <h3 class="font-display font-bold text-lg mb-1"><i class="fa-solid fa-user fa-fw"></i> Step 2 — Assign Field Executive</h3>
            <p class="text-xs text-ink-900/60 mb-4">Schedule a site visit. Available only after a successful call.</p>

            @unless($isSuccess)
                <div class="absolute inset-0 bg-white/70 backdrop-blur-[2px] flex flex-col items-center justify-center text-center px-4 z-10">
                    <div class="text-3xl mb-2"><i class="fa-solid fa-lock fa-fw"></i></div>
                    <p class="font-bold text-ink-900">Locked</p>
                    <p class="text-sm text-ink-900/60 mt-1">Mark the call as <strong>Contacted</strong> or <strong>Interested</strong> to unlock.</p>
                </div>
            @endunless

            <form method="POST" action="{{ route('telecaller.leads.visit', $lead) }}" class="space-y-3 {{ $isSuccess ? '' : 'pointer-events-none opacity-40' }}">
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
                    <select name="field_executive_id" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-900/15">
                        <option value="">Select field executive...</option>
                        @foreach($fieldExecs as $fe)
                            <option value="{{ $fe->id }}">{{ $fe->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="w-full py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg font-bold">Assign & Schedule Visit</button>
            </form>
        </div>
    </div>

    {{-- RIGHT: Live matching properties --}}
    <div>
        <div class="bg-cream p-5 rounded-2xl sticky top-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-display font-bold text-lg"><i class="fa-solid fa-bullseye fa-fw"></i> Matching Properties</h3>
                <span id="matchCount" class="text-xs text-ink-900/60">{{ $matchingProperties->count() }} matches</span>
            </div>
            <p class="text-xs text-ink-900/60 mb-4">Live results based on lead's current budget + area. Send via WhatsApp instantly.</p>

            <div id="matchingResults">
                @include('telecaller._matching_properties', compact('matchingProperties'))
            </div>
        </div>
    </div>
</div>

{{-- ===================== STEP 1: CALL STATUS ===================== --}}
<div class="bg-white border border-ink-900/10 rounded-2xl p-5 mb-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-display font-bold text-lg"><i class="fa-solid fa-phone fa-fw"></i> Step 1 — Call the Lead</h3>
        @if($isSuccess)
            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">✓ SUCCESSFUL</span>
        @elseif($isClosed)
            <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-xs font-bold">✗ CLOSED</span>
        @elseif($attempts > 0)
            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold"><i class="fa-solid fa-hourglass-half fa-fw"></i> {{ $attempts }} ATTEMPT{{ $attempts > 1 ? 'S' : '' }}</span>
        @else
            <span class="px-3 py-1 rounded-full bg-ink-900/5 text-ink-900/60 text-xs font-bold">NOT CALLED</span>
        @endif
    </div>

    @if($lead->called_at)
        <div class="bg-cream rounded-xl p-3 mb-3 text-sm">
            Last call: <strong>{{ ucfirst(str_replace('_', ' ', $lead->call_status)) }}</strong>
            · {{ \Carbon\Carbon::parse($lead->called_at)->diffForHumans() }}
            @if($lead->call_notes)<div class="mt-1 text-xs italic text-ink-900/60">Note: {{ $lead->call_notes }}</div>@endif
        </div>
    @endif

    <a href="tel:{{ $lead->phone }}" class="block text-center py-3 mb-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm">
        <i class="fa-solid fa-mobile-screen fa-fw"></i> Call {{ $lead->phone }}
    </a>

    @unless($isClosed)
    <form method="POST" action="{{ route('telecaller.leads.call', $lead->id) }}" class="space-y-3">
        @csrf
        <div>
            <label class="text-xs font-bold uppercase text-ink-900/60">Call Notes</label>
            <textarea name="call_notes" rows="2" placeholder="What did the tenant say..."
                      class="w-full mt-1 px-3 py-2.5 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 text-sm">{{ $lead->call_notes }}</textarea>
        </div>

        <div>
            <p class="text-xs font-bold uppercase text-ink-900/50 mb-2">If the call connected</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="submit" name="call_status" value="contacted"
                        class="py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm">
                    <i class="fa-solid fa-circle-check fa-fw"></i> Contacted
                </button>
                <button type="submit" name="call_status" value="interested"
                        class="py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm">
                    <i class="fa-solid fa-fire fa-fw"></i> Interested
                </button>
            </div>
        </div>

        <div>
            <p class="text-xs font-bold uppercase text-ink-900/50 mb-2">If the call did not connect</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="submit" name="call_status" value="not_reachable"
                        class="py-3 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-xl font-bold text-sm border border-amber-300">
                    <i class="fa-solid fa-phone-slash fa-fw"></i> Not Reachable {{ $attempts > 0 ? "(retry #".($attempts+1).")" : "" }}
                </button>
                <button type="button" id="notInterestedBtn"
                        class="py-3 bg-white hover:bg-rose-50 text-rose-700 rounded-xl font-bold text-sm border border-rose-300">
                    ✗ Not Interested
                </button>
            </div>
        </div>

        <div id="rejectionReasonWrap" class="hidden bg-rose-50 border border-rose-200 rounded-xl p-3">
            <label class="text-xs font-bold uppercase text-rose-700">Reason (for reporting)</label>
            <select name="rejection_reason" id="rejectionReasonSelect" class="w-full mt-1 px-3 py-2.5 rounded-xl border border-rose-300 text-sm">
                <option value="">— Select reason —</option>
                <option value="too_expensive">Too expensive</option>
                <option value="already_found_pg">Already found a PG</option>
                <option value="wrong_location">Wrong location/locality</option>
                <option value="not_ready_yet">Not ready to move yet</option>
                <option value="no_response_after_interest">Stopped responding</option>
                <option value="other">Other</option>
            </select>
            <input type="hidden" name="call_status" id="rejectionCallStatus" value="">
            <button type="submit" id="confirmRejectBtn" disabled
                    class="w-full mt-3 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-sm disabled:opacity-40">
                Confirm — Close as Not Interested
            </button>
        </div>
    </form>

    <script>
        (function () {
            const openBtn = document.getElementById('notInterestedBtn');
            const wrap = document.getElementById('rejectionReasonWrap');
            const select = document.getElementById('rejectionReasonSelect');
            const hiddenStatus = document.getElementById('rejectionCallStatus');
            const confirmBtn = document.getElementById('confirmRejectBtn');

            openBtn?.addEventListener('click', function () {
                wrap.classList.remove('hidden');
                openBtn.parentElement.parentElement.classList.add('opacity-40', 'pointer-events-none');
            });

            select?.addEventListener('change', function () {
                confirmBtn.disabled = !select.value;
                hiddenStatus.value = select.value ? 'not_interested' : '';
            });
        })();
    </script>

    @if($attempts >= 3 && !$isSuccess)
        <p class="text-xs text-amber-700 mt-3 bg-amber-50 border border-amber-200 rounded-lg p-2">
            <i class="fa-solid fa-triangle-exclamation fa-fw"></i> {{ $attempts }} failed attempts. Keep retrying or mark Not Interested to close.
        </p>
    @endif
    @else
        <p class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">
            This lead is closed (Not Interested). No further action needed.
        </p>
    @endunless
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