@php $isEdit = isset($property); @endphp
<form method="POST" action="{{ $isEdit ? route('owner.properties.update', $property) : route('owner.properties.store') }}" enctype="multipart/form-data" class="space-y-6 bg-white p-8 rounded-2xl border border-ink-900/10">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Property name</label>
            <input name="name" required value="{{ old('name', $property->name ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">City</label>
            <select name="city_id" id="citySelect" required onchange="filterLocalities()" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                @foreach($cities as $c)
                    <option value="{{ $c->id }}" @selected(old('city_id', $property->city_id ?? '') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

       <div>
    <label class="text-xs font-bold uppercase text-ink-900/60">Locality *</label>
    <div class="mt-1 flex gap-2">
        <select name="locality_id" id="localitySelect" class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-ink-900/15">
            <option value="">— Select locality —</option>
            @foreach($localities as $l)
                <option value="{{ $l->id }}" data-city="{{ $l->city_id }}" @selected(old('locality_id', $property->locality_id ?? '') == $l->id)>{{ $l->name }}</option>
            @endforeach
        </select>
        <button type="button" onclick="toggleManualLocality()" class="px-3 py-2 bg-ink-950 text-cream rounded-xl text-sm font-bold whitespace-nowrap">+ New</button>
    </div>

    <div id="manualLocalityWrap" class="hidden mt-2">
        <input name="locality_name" id="manualLocality" placeholder="Type new locality name..." class="w-full px-4 py-3 rounded-xl border-2 border-coral-500 bg-coral-50">
        <p class="text-xs text-coral-700 mt-1">💡 New locality will be saved automatically.</p>
    </div>
</div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">For</label>
            <select name="gender" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                @foreach(['unisex' => 'Unisex', 'male' => 'Boys only', 'female' => 'Girls only'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('gender', $property->gender ?? '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Type</label>
            <select name="property_type" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                @foreach(['pg' => 'PG', 'hostel' => 'Hostel', 'coliving' => 'Co-living', 'flatmate' => 'Flatmate'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('property_type', $property->property_type ?? '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>
    
   <div>
        <label class="text-xs font-semibold text-ink-900/60 uppercase">🎓 Nearby University (Optional)</label>
        <div class="mt-1 flex gap-2">
            <select name="nearby_university_id" id="universitySelect" class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-ink-900/15">
                <option value="">-- Select University --</option>
                @php
                    $universities = \DB::table('universities')->orderBy('city')->orderBy('name')->get();
                @endphp
                @foreach($universities as $uni)
                    <option value="{{ $uni->id }}" {{ old('nearby_university_id', $property->nearby_university_id ?? '') == $uni->id ? 'selected' : '' }}>
                        {{ $uni->name }} ({{ $uni->abbreviation }})
                    </option>
                @endforeach
            </select>
            <button type="button" onclick="toggleManualUniversity()" class="px-3 py-2 bg-ink-950 text-cream rounded-xl text-sm font-bold whitespace-nowrap">+ New</button>
        </div>

        <div id="manualUniversityWrap" class="hidden mt-2">
            <input name="university_name" id="manualUniversity" placeholder="Type university name..." class="w-full px-4 py-3 rounded-xl border-2 border-coral-500 bg-coral-50">
            <input name="university_abbreviation" id="manualUniversityAbbr" placeholder="Abbreviation (e.g., DU)" class="w-full px-4 py-3 rounded-xl border-2 border-coral-500 bg-coral-50 mt-2">
            <p class="text-xs text-coral-700 mt-1">💡 New university will be saved automatically.</p>
        </div>
    </div>

    <script>
    function toggleManualUniversity() {
        const wrap = document.getElementById('manualUniversityWrap');
        const select = document.getElementById('universitySelect');
        
        wrap.classList.toggle('hidden');
        
        if (!wrap.classList.contains('hidden')) {
            select.disabled = true;
            select.value = '';
        } else {
            select.disabled = false;
            document.getElementById('manualUniversity').value = '';
            document.getElementById('manualUniversityAbbr').value = '';
        }
    }

    // Shows only localities belonging to the currently-selected city.
    // Was being called on city change but was never actually defined —
    // that's why the locality dropdown never filtered before.
    function filterLocalities() {
        const cityId = document.getElementById('citySelect').value;
        const localitySelect = document.getElementById('localitySelect');
        const options = localitySelect.querySelectorAll('option[data-city]');

        let selectedStillValid = false;
        options.forEach(opt => {
            const belongsToCity = opt.getAttribute('data-city') === cityId;
            opt.hidden = !belongsToCity;
            opt.disabled = !belongsToCity;
            if (belongsToCity && opt.value === localitySelect.value) {
                selectedStillValid = true;
            }
        });

        // If the previously-selected locality doesn't belong to the newly
        // selected city, clear it so a mismatched combo can't be submitted.
        if (!selectedStillValid) {
            localitySelect.value = '';
        }
    }

    function toggleManualLocality() {
        const wrap = document.getElementById('manualLocalityWrap');
        const select = document.getElementById('localitySelect');

        wrap.classList.toggle('hidden');

        if (!wrap.classList.contains('hidden')) {
            select.disabled = true;
            select.value = '';
        } else {
            select.disabled = false;
            document.getElementById('manualLocality').value = '';
        }
    }

    // Run once on load so the dropdown is correctly filtered for the
    // pre-selected city (important on the Edit page).
    document.addEventListener('DOMContentLoaded', filterLocalities);
    </script>
    
    

    <div>
        <label class="text-xs font-semibold text-ink-900/60 uppercase">Description</label>
        <textarea name="description" rows="4" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">{{ old('description', $property->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Rent (min ₹)</label>
            <input name="rent_min" type="number" required value="{{ old('rent_min', $property->rent_min ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Rent (max ₹)</label>
            <input name="rent_max" type="number" required value="{{ old('rent_max', $property->rent_max ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Deposit ₹</label>
            <input name="security_deposit" type="number" value="{{ old('security_deposit', $property->security_deposit ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div class="flex items-end pb-2">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="food_included" value="1" @checked(old('food_included', $property->food_included ?? false)) class="rounded">
                Food included
            </label>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Single sharing ₹</label>
            <input name="sharing_single" type="number" value="{{ old('sharing_single', $property->sharing_options['single'] ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Double sharing ₹</label>
            <input name="sharing_double" type="number" value="{{ old('sharing_double', $property->sharing_options['double'] ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Triple sharing ₹</label>
            <input name="sharing_triple" type="number" value="{{ old('sharing_triple', $property->sharing_options['triple'] ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
    </div>

    <div>
        <label class="text-xs font-semibold text-ink-900/60 uppercase">Address</label>
        <input name="address_line" required value="{{ old('address_line', $property->address_line ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
             <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Landmark</label><input name="landmark" value="{{ old('landmark', $property->landmark ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">🚓 Nearby Police Station</label><input name="nearby_police_station" value="{{ old('nearby_police_station', $property->nearby_police_station ?? '') }}" placeholder="e.g. Sector 24 Police Station" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Pincode</label><input name="pincode" value="{{ old('pincode', $property->pincode ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Total rooms</label><input name="total_rooms" type="number" value="{{ old('total_rooms', $property->total_rooms ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Available rooms</label><input name="available_rooms" type="number" value="{{ old('available_rooms', $property->available_rooms ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
    </div>

{{-- ===== INTERACTIVE LOCATION MAP (Leaflet - FREE) ===== --}}
<div class="md:col-span-2">
    <label class="text-xs font-bold uppercase text-ink-900/60 mb-2 block">📍 Property Location on Map</label>

    {{-- Search bar --}}
    <div class="flex flex-wrap gap-2 mb-3">
        <input type="text" id="mapSearchInput" placeholder="Search address / paste Google Maps coordinates..." class="flex-1 min-w-[200px] px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
        <button type="button" id="mapSearchBtn" class="px-5 py-3 bg-coral-500 text-white rounded-xl font-bold whitespace-nowrap hover:bg-coral-600">🔍 Search</button>
        <button type="button" id="mapMyLocBtn" class="px-4 py-3 bg-emerald-500 text-white rounded-xl font-bold whitespace-nowrap hover:bg-emerald-600">📍 My Location</button>
    </div>

    {{-- Map container --}}
    <div id="locationMap" style="height: 400px; width: 100%; border-radius: 16px; border: 2px solid #e5e7eb; position: relative; z-index:0;"></div>

    {{-- Lat/Lng display --}}
    <div class="mt-3 flex flex-wrap gap-2 items-center">
        <div class="text-xs text-ink-900/60">
            <strong>Selected:</strong>
            <span id="latDisplay">--</span>, <span id="lngDisplay">--</span>
        </div>
        <div class="flex-1"></div>
        <a id="openInGmaps" href="#" target="_blank" rel="noreferrer" class="hidden text-xs px-3 py-1 bg-blue-50 border border-blue-300 text-blue-700 rounded-lg font-bold hover:bg-blue-100">
            🗺️ Open in Google Maps
        </a>
    </div>

    {{-- Helper instructions --}}
    <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900">
        <strong>💡 How to set location:</strong>
        <ul class="list-disc ml-5 mt-1 space-y-0.5">
            <li><strong>Drag the marker</strong> to the exact spot (most accurate)</li>
            <li>Or type an address and click <strong>Search</strong></li>
            <li>Or paste Google Maps coordinates like <code>28.5512, 77.2588</code></li>
            <li>Or click <strong>My Location</strong> if you are at the PG right now</li>
        </ul>
    </div>

    {{-- Hidden lat/lng - These ACTUALLY save to DB --}}
    <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude', $property->latitude ?? '') }}">
    <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude', $property->longitude ?? '') }}">
</div>

    <div>
        <label class="text-xs font-semibold text-ink-900/60 uppercase">Amenities</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
            @php $selected = isset($property) ? $property->amenities->pluck('id')->toArray() : []; @endphp
            @foreach($amenities as $a)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="amenities[]" value="{{ $a->id }}" @checked(in_array($a->id, old('amenities', $selected))) class="rounded">
                    <span>{{ $a->icon }} {{ $a->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div>
        <label class="text-xs font-semibold text-ink-900/60 uppercase">Cover image</label>
        <input type="file" name="cover_image" accept="image/*" class="w-full mt-1">
        @if(isset($property) && $property->cover_image)<img src="{{ $property->cover_url }}" class="w-32 h-24 mt-2 rounded-lg object-cover">@endif
    </div>
    <div>
        <label class="text-xs font-semibold text-ink-900/60 uppercase">Gallery images (multiple)</label>
        <input type="file" name="images[]" accept="image/*" multiple class="w-full mt-1">
    </div>

    <div>
        <label class="text-xs font-semibold text-ink-900/60 uppercase">House rules</label>
        <textarea name="rules" rows="3" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">{{ old('rules', $property->rules ?? '') }}</textarea>
    </div>

    <div class="flex gap-3">
        <button class="px-8 py-3 bg-coral-500 text-white rounded-xl font-bold">{{ $isEdit ? 'Update property' : 'Submit for verification' }}</button>
        <a href="{{ route('owner.properties.index') }}" class="px-6 py-3 rounded-xl border border-ink-900/15 font-semibold">Cancel</a>
    </div>

    {{-- ===== LEAFLET MAP (FREE - OpenStreetMap) ===== --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    (function () {
        var map, marker;

        function ready(fn) {
            if (document.readyState !== 'loading') fn();
            else document.addEventListener('DOMContentLoaded', fn);
        }

        function updateCoords(lat, lng) {
            document.getElementById('latInput').value = lat.toFixed(6);
            document.getElementById('lngInput').value = lng.toFixed(6);
            var latDisp = document.getElementById('latDisplay');
            var lngDisp = document.getElementById('lngDisplay');
            if (latDisp) latDisp.textContent = lat.toFixed(6);
            if (lngDisp) lngDisp.textContent = lng.toFixed(6);
            var gmapsLink = document.getElementById('openInGmaps');
            if (gmapsLink) {
                gmapsLink.href = 'https://www.google.com/maps?q=' + lat + ',' + lng;
                gmapsLink.classList.remove('hidden');
            }
        }

        function isValidIndiaCoord(lat, lng) {
            return lat >= 6 && lat <= 38 && lng >= 68 && lng <= 98;
        }

        function extractCoords(text) {
            var m, lat, lng;
            m = text.match(/@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/);              if (m) { lat = +m[1]; lng = +m[2]; if (isValidIndiaCoord(lat, lng)) return [lat, lng]; }
            m = text.match(/[?&](?:q|query|ll|center)=(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/); if (m) { lat = +m[1]; lng = +m[2]; if (isValidIndiaCoord(lat, lng)) return [lat, lng]; }
            m = text.match(/!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/);          if (m) { lat = +m[1]; lng = +m[2]; if (isValidIndiaCoord(lat, lng)) return [lat, lng]; }
            m = text.match(/^\s*(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)\s*$/); if (m) { lat = +m[1]; lng = +m[2]; if (isValidIndiaCoord(lat, lng)) return [lat, lng]; }
            return null;
        }

        function setPin(lat, lng, zoom, popup) {
            map.setView([lat, lng], zoom || 17);
            marker.setLatLng([lat, lng]);
            updateCoords(lat, lng);
            if (popup) marker.bindPopup(popup).openPopup();
        }

        function doSearch() {
            var raw = (document.getElementById('mapSearchInput').value || '').trim();
            if (!raw) { alert('Type an address, or paste Google Maps coordinates'); return; }

            var c = extractCoords(raw);
            if (c) { setPin(c[0], c[1], 17, '📍 Pinned from coordinates'); return; }

            if (/^https?:\/\//i.test(raw)) {
                // Any Google Maps link (short share.google / maps.app.goo.gl,
                // or a full one) — resolve it server-side, since a browser
                // can't follow the short link's redirect itself (CORS).
                var linkBtn = document.getElementById('mapSearchBtn');
                var linkOrig = linkBtn.innerHTML; linkBtn.innerHTML = '⏳ Opening link...'; linkBtn.disabled = true;
                fetch('{{ route('map.resolve') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}' },
                    body: JSON.stringify({ url: raw })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        linkBtn.innerHTML = linkOrig; linkBtn.disabled = false;
                        if (d.success) { setPin(d.lat, d.lng, 17, '📍 Pinned from Google Maps link'); }
                        else { alert(d.message || 'Could not read that link. Try dragging the marker instead.'); }
                    })
                    .catch(function () {
                        linkBtn.innerHTML = linkOrig; linkBtn.disabled = false;
                        alert('Could not open that link. Try dragging the marker instead.');
                    });
                return;
            }

            var btn = document.getElementById('mapSearchBtn');
            var orig = btn.innerHTML; btn.innerHTML = '⏳ Searching...'; btn.disabled = true;
            var url = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=in&limit=1&q=' + encodeURIComponent(raw + ', India');
            fetch(url, { headers: { 'Accept-Language': 'en' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    btn.innerHTML = orig; btn.disabled = false;
                    if (!d.length) { alert('Location not found. Try a more specific address — or just drag the marker.'); return; }
                    setPin(parseFloat(d[0].lat), parseFloat(d[0].lon), 17, '📍 ' + d[0].display_name.substring(0, 90));
                })
                .catch(function () { btn.innerHTML = orig; btn.disabled = false; alert('Search failed. Drag the marker manually.'); });
        }

        function doMyLocation() {
            if (!navigator.geolocation) { alert('Geolocation not supported'); return; }
            var btn = document.getElementById('mapMyLocBtn');
            var orig = btn.innerHTML; btn.innerHTML = '⏳...'; btn.disabled = true;
            navigator.geolocation.getCurrentPosition(
                function (p) { btn.innerHTML = orig; btn.disabled = false; setPin(p.coords.latitude, p.coords.longitude, 17, '📍 Your current location'); },
                function () { btn.innerHTML = orig; btn.disabled = false; alert('Could not get location. Please allow location access in the browser.'); }
            );
        }

        ready(function () {
            var el = document.getElementById('locationMap');
            if (!el || typeof L === 'undefined') { return; }

            var lat = parseFloat(document.getElementById('latInput').value) || 28.6139;
            var lng = parseFloat(document.getElementById('lngInput').value) || 77.2090;

            map = L.map('locationMap').setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);

            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.bindPopup('🏠 Drag me to your PG location').openPopup();
            marker.on('dragend', function () { var pos = marker.getLatLng(); updateCoords(pos.lat, pos.lng); });
            map.on('click', function (e) { marker.setLatLng(e.latlng); updateCoords(e.latlng.lat, e.latlng.lng); });

            setTimeout(function () { map.invalidateSize(); }, 300);

            if (document.getElementById('latInput').value) updateCoords(lat, lng);

            document.getElementById('mapSearchBtn').addEventListener('click', doSearch);
            document.getElementById('mapMyLocBtn').addEventListener('click', doMyLocation);
            document.getElementById('mapSearchInput').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doSearch(); } });
        });
    })();
    </script>

</form>