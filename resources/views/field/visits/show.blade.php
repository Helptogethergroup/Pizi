@extends('layouts.dashboard')
@section('title', 'Visit Details')
@section('content')

<div class="mb-6">
    <a href="{{ route('field.visits.index') }}" class="text-sm text-coral-500 font-bold">← Back to visits</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <span class="text-xs font-bold uppercase px-2 py-1 rounded
                @if($visit->status === 'completed') bg-emerald-100 text-emerald-700
                @elseif($visit->status === 'in_progress') bg-amber-100 text-amber-700
                @elseif($visit->status === 'scheduled') bg-blue-100 text-blue-700
                @else bg-rose-100 text-rose-700 @endif">
                {{ str_replace('_', ' ', $visit->status) }}
            </span>
            <h1 class="font-display font-black text-2xl text-ink-950 mt-3">{{ $visit->property->name }}</h1>
            <p class="text-ink-700 mt-1">📍 {{ $visit->property->address_line }}, {{ $visit->property->locality?->name }}, {{ $visit->property->city?->name }}</p>

            @if(!empty($visit->property->google_map_link))
                <a href="{{ $visit->property->google_map_link }}" target="_blank" class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-bold">🧭 Get Directions</a>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-5 border-t border-ink-100">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Scheduled</div>
                    <div class="font-bold text-ink-950">{{ $visit->scheduled_at->format('d M, h:i A') }}</div>
                </div>
                @if($visit->started_at)
                    <div>
                        <div class="text-xs text-ink-500 uppercase font-bold">Started</div>
                        <div class="font-bold text-ink-950">{{ $visit->started_at->format('h:i A') }}</div>
                    </div>
                @endif
                @if($visit->completed_at)
                    <div>
                        <div class="text-xs text-emerald-700 uppercase font-bold">Completed</div>
                        <div class="font-bold text-emerald-700">{{ $visit->completed_at->format('h:i A') }}</div>
                    </div>
                @endif
            </div>
        </div>

        @if($visit->status === 'scheduled')
            <div class="bg-amber-50 border-2 border-amber-300 p-6 rounded-2xl">
                <h3 class="font-bold text-lg text-amber-900">⏳ Ready to start?</h3>
                <p class="text-sm text-amber-800 mt-1 mb-4">Click below to check-in. Your GPS location will be recorded.</p>
                <form method="POST" action="{{ route('field.visits.start', $visit) }}" id="startForm">
                    @csrf
                    <input type="hidden" name="lat" id="startLat">
                    <input type="hidden" name="lng" id="startLng">
                    <button type="button" onclick="startVisit()" class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-lg">
                        🚀 Check-in & Start Visit
                    </button>
                </form>
            </div>
        @endif

        @if(in_array($visit->status, ['in_progress', 'completed']))
            <div class="bg-white p-6 rounded-2xl border border-ink-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-display font-bold text-xl">✓ Verification Checklist</h2>
                    <div class="text-sm font-bold text-emerald-700">{{ $visit->verification_progress }}%</div>
                </div>

                <div class="w-full bg-ink-100 rounded-full h-2 mb-5">
                    <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $visit->verification_progress }}%"></div>
                </div>

                <form method="POST" action="{{ route('field.visits.verify', $visit) }}" class="space-y-3">
                    @csrf
                    @foreach([
                        'address_verified' => ['📍 Address Verified', 'Address matches property location'],
                        'amenities_verified' => ['✨ Amenities Verified', 'All listed amenities are present'],
                        'rooms_verified' => ['🛏️ Rooms Verified', 'Room types and counts match'],
                        'safety_verified' => ['🛡️ Safety Verified', 'Fire safety, CCTV, locks proper'],
                    ] as $field => $info)
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-ink-100 cursor-pointer hover:bg-cream">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked($visit->{$field}) class="mt-1 rounded w-5 h-5" {{ $visit->status === 'completed' ? 'disabled' : '' }}>
                            <div>
                                <div class="font-bold">{{ $info[0] }}</div>
                                <div class="text-xs text-ink-700">{{ $info[1] }}</div>
                            </div>
                        </label>
                    @endforeach

                    <textarea name="remarks" rows="3" placeholder="Remarks..." class="w-full px-4 py-3 rounded-xl border border-ink-200" {{ $visit->status === 'completed' ? 'readonly' : '' }}>{{ $visit->remarks }}</textarea>

                    @if($visit->status !== 'completed')
                        <button type="submit" class="px-5 py-2.5 bg-ink-950 text-cream rounded-xl font-bold text-sm">💾 Save Checklist</button>
                    @endif
                </form>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-ink-100">
                <h2 class="font-display font-bold text-xl mb-4">📸 Photos & Videos</h2>

                @if($visit->status !== 'completed')
                    <form method="POST" action="{{ route('field.visits.media', $visit) }}" enctype="multipart/form-data" class="space-y-3 mb-5">
                        @csrf
                        <input type="file" name="media[]" multiple accept="image/*,video/*" capture="environment" class="w-full text-sm" required>
                        <input type="text" name="caption" placeholder="Caption (optional)" class="w-full px-4 py-2 rounded-xl border border-ink-200 text-sm">
                        <button type="submit" class="px-5 py-2.5 bg-coral-500 text-white rounded-xl font-bold text-sm">📤 Upload</button>
                    </form>
                @endif

                @if($visit->media->count())
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($visit->media as $m)
                            <div class="rounded-xl overflow-hidden border border-ink-100">
                                @if($m->media_type === 'photo')
                                    <img src="{{ $m->url }}" class="w-full aspect-square object-cover">
                                @else
                                    <video src="{{ $m->url }}" controls class="w-full aspect-square object-cover"></video>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-ink-500">No media uploaded.</p>
                @endif
            </div>
        @endif

        @if($visit->status === 'in_progress')
            <div class="bg-emerald-50 border-2 border-emerald-300 p-6 rounded-2xl">
                <h3 class="font-bold text-lg text-emerald-900">✅ Done with the visit?</h3>
                <form method="POST" action="{{ route('field.visits.complete', $visit) }}" id="completeForm" class="mt-4">
                    @csrf
                    <input type="hidden" name="lat" id="endLat">
                    <input type="hidden" name="lng" id="endLng">
                    <button type="button" onclick="completeVisit()" class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-lg">
                        🏁 Check-out & Complete Visit
                    </button>
                </form>
            </div>
        @endif

        @if($visit->status === 'completed')
            <div class="bg-emerald-50 border-2 border-emerald-300 p-6 rounded-2xl text-center">
                <div class="text-5xl mb-2">🎉</div>
                <h3 class="font-bold text-lg text-emerald-900">Visit Completed</h3>
                <p class="text-sm text-emerald-800 mt-1">Great work! Submitted on {{ $visit->completed_at->format('d M, h:i A') }}</p>
            </div>
        @endif
    </div>

    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 bg-white p-5 rounded-2xl border border-ink-100">
            <h3 class="font-display font-bold text-lg mb-3">🏠 Property Info</h3>
            <div class="space-y-2 text-sm">
                <div><span class="text-ink-500">Type:</span> <strong class="capitalize">{{ $visit->property->property_type }}</strong></div>
                <div><span class="text-ink-500">Gender:</span> <strong class="capitalize">{{ $visit->property->gender }}</strong></div>
                <div><span class="text-ink-500">Rent:</span> <strong>₹{{ number_format($visit->property->rent_min) }}+</strong></div>
                <div><span class="text-ink-500">Rooms:</span> <strong>{{ $visit->property->total_rooms ?? '—' }}</strong></div>
            </div>

            @if($visit->property->amenities->count())
                <div class="mt-4 pt-4 border-t border-ink-100">
                    <div class="text-xs font-bold uppercase text-ink-500 mb-2">Amenities</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($visit->property->amenities as $a)
                            <span class="px-2 py-0.5 rounded-full bg-cream text-xs">{{ $a->icon ?? '✨' }} {{ $a->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($visit->assignedBy)
                <div class="mt-4 pt-4 border-t border-ink-100 text-xs">
                    Assigned by: <strong>{{ $visit->assignedBy->name }}</strong>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function getLocation(cb) {
    if (!navigator.geolocation) { cb(null, null); return; }
    navigator.geolocation.getCurrentPosition(
        p => cb(p.coords.latitude, p.coords.longitude),
        e => { if (confirm('Could not get GPS. Continue without?')) cb(null, null); },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}
function startVisit() {
    getLocation((lat, lng) => {
        document.getElementById('startLat').value = lat || '';
        document.getElementById('startLng').value = lng || '';
        document.getElementById('startForm').submit();
    });
}
function completeVisit() {
    getLocation((lat, lng) => {
        document.getElementById('endLat').value = lat || '';
        document.getElementById('endLng').value = lng || '';
        document.getElementById('completeForm').submit();
    });
}
</script>

@endsection