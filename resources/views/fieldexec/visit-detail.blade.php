@extends('layouts.fieldexec')
@section('title', 'Visit Details')

@section('back-link')
    <a href="{{ route('fieldexec.dashboard') }}" class="text-cream/80 text-2xl">←</a>
@endsection

@section('content')

{{-- Lead info --}}
<div class="bg-white p-5 rounded-2xl border border-ink-900/10 mb-4">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="font-display font-bold text-2xl">{{ $visit->lead?->name }}</h2>
            <div class="text-sm text-ink-900/60 mt-1">
                ⏰ Scheduled: <strong>{{ $visit->scheduled_at->format('d M, h:i A') }}</strong>
            </div>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $visit->outcome_badge }}">{{ $visit->outcome }}</span>
    </div>

    <div class="grid grid-cols-2 gap-2 mt-4">
        <a href="tel:{{ $visit->lead?->phone }}" class="flex items-center justify-center gap-2 py-3 bg-emerald-500 text-white rounded-xl font-bold">
            📞 Call
        </a>
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $visit->lead?->phone) }}" target="_blank" class="flex items-center justify-center gap-2 py-3 bg-emerald-600 text-white rounded-xl font-bold">
            💬 WhatsApp
        </a>
    </div>

    @if($visit->lead?->message)
        <div class="mt-4 p-3 rounded-xl bg-cream text-sm">
            <div class="text-xs font-semibold text-ink-900/60 uppercase">Lead's message</div>
            <p class="mt-1">{{ $visit->lead->message }}</p>
        </div>
    @endif

    {{-- Lead requirements --}}
    @if($visit->lead?->budget_max || $visit->lead?->preferred_locality)
    <div class="grid grid-cols-2 gap-2 mt-4 text-xs">
        @if($visit->lead->budget_max)
            <div class="p-2 bg-cream rounded">💰 Budget: ₹{{ number_format($visit->lead->budget_max) }}</div>
        @endif
        @if($visit->lead->preferred_gender)
            <div class="p-2 bg-cream rounded capitalize">👤 {{ $visit->lead->preferred_gender }}</div>
        @endif
    </div>
    @endif
</div>

{{-- Property info --}}
<div class="bg-white p-5 rounded-2xl border border-ink-900/10 mb-4">
    <div class="text-xs font-semibold text-ink-900/60 uppercase mb-2">Property to show</div>

    @if($visit->property?->cover_image)
        <img src="{{ $visit->property->cover_url }}" class="w-full aspect-[16/9] object-cover rounded-xl mb-3">
    @endif

    <h3 class="font-display font-bold text-xl">{{ $visit->property?->name }}</h3>
    <div class="text-sm text-ink-900/60 mt-1">{{ $visit->property?->address_line }}</div>
    <div class="text-sm text-ink-900/60">📍 {{ $visit->property?->locality?->name }}, {{ $visit->property?->city?->name }}</div>

    <div class="grid grid-cols-2 gap-2 mt-4 text-sm">
        <div class="p-2 bg-cream rounded">💰 {{ $visit->property?->rent_range }}</div>
        <div class="p-2 bg-cream rounded">🛡️ ₹{{ number_format($visit->property?->security_deposit ?? 0) }}</div>
    </div>

    {{-- Maps navigation --}}
    @if($visit->property?->latitude && $visit->property?->longitude)
        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $visit->property->latitude }},{{ $visit->property->longitude }}" target="_blank"
           class="block w-full text-center py-3 mt-4 bg-blue-500 text-white rounded-xl font-bold">
            🗺️ Get Directions on Google Maps
        </a>
    @else
        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($visit->property?->address_line . ', ' . $visit->property?->locality?->name) }}" target="_blank"
           class="block w-full text-center py-3 mt-4 bg-blue-500 text-white rounded-xl font-bold">
            🗺️ Find on Google Maps
        </a>
    @endif
</div>

{{-- Check-in section --}}
@if($visit->outcome === 'pending')
    @if(!$visit->checked_in_at)
        <div id="checkinPanel" class="bg-amber-50 border-2 border-amber-300 p-5 rounded-2xl mb-4">
            <div class="text-amber-900 font-bold mb-1">⏳ Not checked in yet</div>
            <p class="text-xs text-amber-900/70 mb-3">You must be within <strong>100m</strong> of the property to check in.</p>

            {{-- Live distance display --}}
            <div id="distanceDisplay" class="hidden mb-3 p-3 rounded-xl bg-white border border-amber-200">
                <div class="text-xs text-ink-900/60 uppercase">Your distance from property</div>
                <div class="flex items-center gap-2 mt-1">
                    <div id="distanceValue" class="font-display font-black text-3xl">—</div>
                    <div id="distanceStatus" class="text-sm font-semibold"></div>
                </div>
                <div id="accuracyHint" class="text-xs text-ink-900/50 mt-1"></div>
            </div>

            <form method="POST" action="{{ route('fieldexec.visits.checkin', $visit) }}" id="checkinForm">
                @csrf
                <input type="hidden" name="latitude" id="lat">
                <input type="hidden" name="longitude" id="lng">

                <button type="button" id="getLocationBtn" onclick="getMyLocation()"
                        class="w-full py-4 bg-amber-500 text-white rounded-xl font-bold text-lg">
                    📍 Get my location
                </button>

                <button type="submit" id="checkinBtn" disabled
                        class="hidden w-full py-4 bg-emerald-500 text-white rounded-xl font-bold text-lg mt-3 disabled:opacity-50 disabled:cursor-not-allowed">
                    ✅ Check-in now
                </button>

                <p id="locationError" class="hidden text-rose-600 text-sm mt-2"></p>

                {{-- Admin override --}}
                @if(auth()->user()->isAdmin())
                    <label class="hidden mt-3 p-2 rounded bg-rose-50 border border-rose-200 cursor-pointer" id="adminOverride">
                        <input type="checkbox" name="force" value="1" class="rounded">
                        <span class="text-xs text-rose-700">⚠️ Admin override — check-in despite distance</span>
                    </label>
                @endif
            </form>
        </div>
    @else
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl mb-4 text-sm">
            ✅ Checked in at <strong>{{ $visit->checked_in_at->format('h:i A') }}</strong>
            @if($visit->checkin_distance_m !== null)
                · 📍 Was {{ $visit->checkin_distance_m }}m away
            @endif
        </div>
    @endif
@endif

{{-- Outcome / Complete visit form --}}
@if($visit->outcome === 'pending' && $visit->checked_in_at)
    <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-xl mb-4">Mark visit outcome</h3>

        <form method="POST" action="{{ route('fieldexec.visits.complete', $visit) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="text-xs font-semibold text-ink-900/60 uppercase">Outcome *</label>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <label>
                        <input type="radio" name="outcome" value="closed" class="peer hidden" required>
                        <div class="text-center text-sm py-3 rounded-xl border-2 border-ink-900/15 cursor-pointer peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500">✅ Closed (Booked)</div>
                    </label>
                    <label>
                        <input type="radio" name="outcome" value="rejected" class="peer hidden">
                        <div class="text-center text-sm py-3 rounded-xl border-2 border-ink-900/15 cursor-pointer peer-checked:bg-rose-500 peer-checked:text-white peer-checked:border-rose-500">❌ Rejected</div>
                    </label>
                    <label>
                        <input type="radio" name="outcome" value="revisit" class="peer hidden">
                        <div class="text-center text-sm py-3 rounded-xl border-2 border-ink-900/15 cursor-pointer peer-checked:bg-violet-500 peer-checked:text-white peer-checked:border-violet-500">🔄 Re-visit</div>
                    </label>
                    <label>
                        <input type="radio" name="outcome" value="no_show" class="peer hidden">
                        <div class="text-center text-sm py-3 rounded-xl border-2 border-ink-900/15 cursor-pointer peer-checked:bg-slate-500 peer-checked:text-white peer-checked:border-slate-500">👻 No-show</div>
                    </label>
                </div>
            </div>

            {{-- Closed: Token amount --}}
            <div id="closed-fields" class="hidden space-y-4">
                <div>
                    <label class="text-xs font-semibold text-emerald-700 uppercase">Token amount collected ₹ *</label>
                    <input name="token_amount" type="number" min="0" step="100" placeholder="2000" class="w-full mt-1 px-4 py-3 rounded-xl border-2 border-emerald-300 text-2xl font-bold focus:border-emerald-500 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-emerald-700 uppercase">Receipt photo</label>
                    <input name="receipt_image" type="file" accept="image/*" capture="environment" class="w-full mt-1 px-4 py-3 rounded-xl border border-emerald-300">
                    <p class="text-xs text-ink-900/50 mt-1">📸 Take a photo of the cash/transfer receipt</p>
                </div>
            </div>

            {{-- Rejected: reason --}}
            <div id="rejected-fields" class="hidden">
                <label class="text-xs font-semibold text-rose-700 uppercase">Why rejected? *</label>
                <select name="rejection_reason" class="w-full mt-1 px-4 py-3 rounded-xl border-2 border-rose-300">
                    <option value="">Select reason...</option>
                    <option value="too_expensive">Too expensive</option>
                    <option value="location_issue">Didn't like the location</option>
                    <option value="property_condition">Property condition not good</option>
                    <option value="amenities_missing">Amenities missing</option>
                    <option value="distance_far">Too far from work/college</option>
                    <option value="changed_mind">Changed mind / not moving anymore</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-ink-900/60 uppercase">Tenant feedback / notes</label>
                <textarea name="tenant_feedback" rows="3" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15" placeholder="What did the tenant say? Any specific feedback?"></textarea>
            </div>

            <button class="w-full py-4 bg-coral-500 text-white rounded-xl font-bold text-lg">
                Submit visit report →
            </button>
        </form>
    </div>
@endif

{{-- Already completed view --}}
@if(in_array($visit->outcome, ['closed', 'rejected', 'revisit', 'no_show']))
    <div class="bg-white p-5 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-xl mb-3">Visit Report</h3>

        <div class="space-y-3 text-sm">
            <div>
                <div class="text-xs text-ink-900/60 uppercase">Outcome</div>
                <div class="font-bold text-lg capitalize">{{ str_replace('_',' ',$visit->outcome) }}</div>
            </div>

            @if($visit->checked_in_at)
                <div>
                    <div class="text-xs text-ink-900/60 uppercase">Checked in / out</div>
                    <div>{{ $visit->checked_in_at->format('h:i A') }} → {{ $visit->checked_out_at?->format('h:i A') ?? '—' }}</div>
                </div>
            @endif

            @if($visit->token_amount)
                <div>
                    <div class="text-xs text-emerald-700 uppercase">💰 Token collected</div>
                    <div class="font-display font-bold text-2xl text-emerald-700">₹{{ number_format($visit->token_amount) }}</div>
                </div>
            @endif

            @if($visit->receipt_image)
                <div>
                    <div class="text-xs text-ink-900/60 uppercase">Receipt</div>
                    <img src="{{ $visit->receipt_url }}" class="mt-2 rounded-xl border border-ink-900/10">
                </div>
            @endif

            @if($visit->rejection_reason)
                <div>
                    <div class="text-xs text-rose-700 uppercase">Rejection reason</div>
                    <div class="capitalize">{{ str_replace('_',' ',$visit->rejection_reason) }}</div>
                </div>
            @endif

            @if($visit->tenant_feedback)
                <div>
                    <div class="text-xs text-ink-900/60 uppercase">Feedback</div>
                    <div class="italic">"{{ $visit->tenant_feedback }}"</div>
                </div>
            @endif
        </div>
    </div>
@endif

@push('scripts')
<script>
const PROPERTY_LAT = {{ $visit->property?->latitude ?? 'null' }};
const PROPERTY_LNG = {{ $visit->property?->longitude ?? 'null' }};
const FENCE_RADIUS = 100;

document.querySelectorAll('input[name="outcome"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('closed-fields')?.classList.toggle('hidden', this.value !== 'closed');
        document.getElementById('rejected-fields')?.classList.toggle('hidden', this.value !== 'rejected');
    });
});

function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLng/2)**2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return Math.round(R * c);
}

function formatDistance(m) {
    return m < 1000 ? `${m}m` : `${(m/1000).toFixed(1)}km`;
}

function getMyLocation() {
    const btn = document.getElementById('getLocationBtn');
    const errorEl = document.getElementById('locationError');
    const display = document.getElementById('distanceDisplay');

    errorEl.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = '⏳ Getting GPS location...';

    if (!navigator.geolocation) {
        errorEl.textContent = 'GPS not supported by this browser.';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '📍 Get my location';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const myLat = pos.coords.latitude;
            const myLng = pos.coords.longitude;
            const accuracy = Math.round(pos.coords.accuracy);

            document.getElementById('lat').value = myLat;
            document.getElementById('lng').value = myLng;

            if (!PROPERTY_LAT || !PROPERTY_LNG) {
                document.getElementById('checkinBtn').disabled = false;
                document.getElementById('checkinBtn').classList.remove('hidden');
                btn.innerHTML = '✅ Location captured';
                btn.disabled = true;
                return;
            }

            const distance = haversine(myLat, myLng, PROPERTY_LAT, PROPERTY_LNG);
            const within = distance <= FENCE_RADIUS;

            display.classList.remove('hidden');
            document.getElementById('distanceValue').textContent = formatDistance(distance);

            const statusEl = document.getElementById('distanceStatus');
            if (within) {
                statusEl.textContent = '✅ Inside geo-fence';
                statusEl.className = 'text-sm font-semibold text-emerald-600';
                document.getElementById('checkinBtn').disabled = false;
                document.getElementById('checkinBtn').classList.remove('hidden');
                btn.classList.add('hidden');
            } else {
                statusEl.textContent = `❌ Need to be within ${FENCE_RADIUS}m`;
                statusEl.className = 'text-sm font-semibold text-rose-600';
                btn.innerHTML = '🔄 Try again (move closer)';
                btn.disabled = false;

                const override = document.getElementById('adminOverride');
                if (override) {
                    override.classList.remove('hidden');
                    document.getElementById('checkinBtn').disabled = false;
                    document.getElementById('checkinBtn').classList.remove('hidden');
                    document.getElementById('checkinBtn').innerHTML = '⚠️ Force check-in (admin)';
                    document.getElementById('checkinBtn').classList.remove('bg-emerald-500');
                    document.getElementById('checkinBtn').classList.add('bg-rose-500');
                }
            }

            document.getElementById('accuracyHint').textContent = `GPS accuracy: ±${accuracy}m`;
        },
        function(err) {
            const messages = {
                1: 'Location permission denied. Enable GPS access in browser settings.',
                2: 'Could not get location. Check GPS / network.',
                3: 'Location request timed out. Try again.',
            };
            errorEl.textContent = messages[err.code] || 'Failed to get location.';
            errorEl.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = '📍 Get my location';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}
</script>
@endpush

@endsection