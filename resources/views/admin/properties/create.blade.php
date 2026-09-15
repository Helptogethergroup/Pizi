@extends('layouts.dashboard')
@section('title', 'Add Property — Admin')
@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.properties.index') }}" class="text-2xl">←</a>
    <div>
        <h1 class="font-display font-black text-3xl">Add Property</h1>
        <p class="text-ink-900/60">Create a new PG listing on behalf of an owner</p>
    </div>
</div>

{{-- ERROR MESSAGE --}}
@if(session('error'))
    <div class="mb-6 bg-rose-50 border-l-4 border-rose-500 px-4 py-3 rounded-xl">
        <p class="text-rose-700 font-bold flex items-center gap-2">
            <span class="text-lg">❌</span> Error
        </p>
        <p class="text-rose-600 text-sm mt-1">{{ session('error') }}</p>
    </div>
@endif

{{-- SUCCESS MESSAGE --}}
@if(session('success'))
    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 px-4 py-3 rounded-xl">
        <p class="text-emerald-700 font-bold flex items-center gap-2">
            <span class="text-lg">✅</span> Success
        </p>
        <p class="text-emerald-600 text-sm mt-1">{{ session('success') }}</p>
    </div>
@endif

{{-- VALIDATION ERRORS --}}
@if($errors->any())
    <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 px-4 py-3 rounded-xl">
        <p class="text-amber-700 font-bold flex items-center gap-2">
            <span class="text-lg">⚠️</span> Please fix these fields:
        </p>
        <ul class="list-disc pl-5 text-amber-600 text-sm mt-2 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.properties.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    

    {{-- BASIC INFO --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-lg mb-4">🏠 Basic info</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-900/60">Property name <span class="text-rose-500">*</span></label>
                <input name="name" required value="{{ old('name') }}" placeholder="e.g. Sai Stay PG" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Property type <span class="text-rose-500">*</span></label>
                <select name="property_type" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                    <option value="pg" @selected(old('property_type')==='pg')>🏠 PG</option>
                    <option value="hostel" @selected(old('property_type')==='hostel')>🏨 Hostel</option>
                    <option value="coliving" @selected(old('property_type')==='coliving')>🛋️ Coliving</option>
                    <option value="flatmate" @selected(old('property_type')==='flatmate')>👯 Flatmate</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Gender <span class="text-rose-500">*</span></label>
                <select name="gender" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                    <option value="male" @selected(old('gender')==='male')>👨 Boys only</option>
                    <option value="female" @selected(old('gender')==='female')>👩 Girls only</option>
                    <option value="unisex" @selected(old('gender')==='unisex')>👥 Unisex</option>
                </select>
            </div>
        </div>
    </div>

    {{-- LOCATION --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-lg mb-4">📍 Location</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">City <span class="text-rose-500">*</span></label>
                <select name="city_id" id="citySelect" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15" onchange="filterLocalities()">
                    <option value="">— Select city —</option>
                    @foreach($cities as $c)
                        <option value="{{ $c->id }}" @selected(old('city_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 🔥 SMART LOCALITY FIELD --}}
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Locality <span class="text-rose-500">*</span></label>
                <div class="mt-1 flex gap-2">
                    <select name="locality_id" id="localitySelect" class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-ink-900/15">
                        <option value="">— Select locality —</option>
                        @foreach($localities as $l)
                            <option value="{{ $l->id }}" data-city="{{ $l->city_id }}" @selected(old('locality_id') == $l->id)>{{ $l->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="toggleManualLocality()" class="px-3 py-2 bg-ink-950 text-cream rounded-xl text-sm font-bold whitespace-nowrap">+ New</button>
                </div>

                <div id="manualLocalityWrap" class="hidden mt-2">
                    <input name="locality_name" id="manualLocality" placeholder="Type new locality name (e.g. Sector 62, Mukherjee Nagar)..." class="w-full px-4 py-3 rounded-xl border-2 border-coral-500 bg-coral-50">
                    <p class="text-xs text-coral-700 mt-1">💡 This locality will be added to the database automatically.</p>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-900/60">Full address <span class="text-rose-500">*</span></label>
                <textarea name="address_line" required rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15" placeholder="e.g. H-No 45, Mukherjee Nagar Main Road, near Batra Cinema">{{ old('address_line') }}</textarea>
            </div>

            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Pincode</label>
                <input name="pincode" value="{{ old('pincode') }}" placeholder="110009" maxlength="6" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
                     <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Landmark (nearby)</label>
                <input name="landmark" value="{{ old('landmark') }}" placeholder="e.g. Near IIT Delhi gate" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">🚓 Nearby Police Station</label>
                <input name="nearby_police_station" value="{{ old('nearby_police_station') }}" placeholder="e.g. Sector 24 Police Station" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                <p class="text-xs text-ink-900/40 mt-1">Local police station ka naam — tenant safety ke liye display hoga.</p>
            </div>
            
            
            
<!-- Nearby University (NEW) -->
<!-- Nearby University (NEW) -->
<div class="form-group mb-4">
    <label class="block text-sm font-semibold text-ink-900 mb-2">
        🎓 Which University/College is this PG near?
    </label>
    <div class="flex gap-2">
        <select name="nearby_university_id" id="adminUniversitySelect" class="flex-1 min-w-0 w-full px-4 py-2 border border-ink-900/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
            <option value="">-- Select University (Optional) --</option>
            @php
                $universities = \DB::table('universities')->orderBy('city')->orderBy('name')->get();
            @endphp
            @foreach($universities as $uni)
                <option value="{{ $uni->id }}" {{ old('nearby_university_id') == $uni->id ? 'selected' : '' }}>
                    {{ $uni->name }} ({{ $uni->abbreviation }}) — {{ ucfirst($uni->city) }}
                </option>
            @endforeach
        </select>
        <button type="button" onclick="toggleAdminManualUniversity()" class="px-3 py-2 bg-ink-950 text-cream rounded-lg text-sm font-bold whitespace-nowrap">+ New</button>
    </div>
    
    <div id="adminManualUniversityWrap" class="hidden mt-2">
        <input name="university_name" id="adminManualUniversity" placeholder="Type university name..." class="w-full px-4 py-2 rounded-lg border-2 border-coral-500 bg-coral-50 mt-1">
        <input name="university_abbreviation" id="adminManualUniversityAbbr" placeholder="Abbreviation (e.g., DU)" class="w-full px-4 py-2 rounded-lg border-2 border-coral-500 bg-coral-50 mt-1">
        <p class="text-xs text-coral-700 mt-1">💡 New university will be saved automatically.</p>
    </div>
    
    <p class="text-xs text-ink-900/50 mt-1">Select the nearest university/college. The PG will automatically show on that university's page.</p>
</div>

<script>
function toggleAdminManualUniversity() {
    const wrap = document.getElementById('adminManualUniversityWrap');
    const select = document.getElementById('adminUniversitySelect');
    
    wrap.classList.toggle('hidden');
    
    if (!wrap.classList.contains('hidden')) {
        select.disabled = true;
        select.value = '';
    } else {
        select.disabled = false;
        document.getElementById('adminManualUniversity').value = '';
        document.getElementById('adminManualUniversityAbbr').value = '';
    }
}
</script>
            
            
            
{{-- 📍 INTERACTIVE LOCATION MAP (this is what actually saves lat/lng) --}}
<div class="md:col-span-2">
    <label class="text-xs font-bold uppercase text-ink-900/60 mb-2 block">📍 Property Location on Map</label>

    <div class="flex flex-wrap gap-2 mb-3">
        <input type="text" id="mapSearchInput" placeholder="Search address / paste Google Maps coordinates..." class="flex-1 min-w-[200px] px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
        <button type="button" id="mapSearchBtn" onclick="searchLocation()" class="px-5 py-3 bg-coral-500 text-white rounded-xl font-bold whitespace-nowrap hover:bg-coral-600">🔍 Search</button>
        <button type="button" id="mapMyLocBtn" onclick="useMyLocation()" class="px-4 py-3 bg-emerald-500 text-white rounded-xl font-bold whitespace-nowrap hover:bg-emerald-600">📍 My Location</button>
    </div>

    <div id="locationMap" style="height: 400px; width: 100%; border-radius: 16px; border: 2px solid #e5e7eb; position: relative; z-index:0;"></div>

    <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900">
        <strong>💡 How to set location:</strong>
        <ul class="list-disc ml-5 mt-1 space-y-0.5">
            <li>Map auto-locates as you type the address above — <strong>drag the marker</strong> to fine-tune (most accurate)</li>
            <li>Or type a place and click <strong>Search</strong></li>
            <li>Or click <strong>My Location</strong> if you're at the PG right now</li>
        </ul>
    </div>

    {{-- 🔥 THIS is what actually saves to DB --}}
    <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude') }}">
    <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude') }}">
</div>

{{-- Optional: keep the raw share-link too, just as a reference note, not used for coordinates --}}
<div class="md:col-span-2">
    <label class="text-xs font-bold uppercase text-ink-900/60 mb-2 block">🗺️ Google Maps Link (optional, reference only)</label>
    <input type="url" name="google_map_link" value="{{ old('google_map_link') }}" placeholder="https://maps.app.goo.gl/... or https://www.google.com/maps/place/..." class="w-full px-4 py-3 rounded-xl border border-ink-900/15 text-sm">
    <p class="text-xs text-ink-900/50 mt-1">This is just saved as text for reference — it does NOT set the map pin. Use the map above to set the actual location.</p>
</div>
        </div>
    </div>

    {{-- PRICING --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-lg mb-4">💰 Pricing & Rooms</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Rent (min) ₹ <span class="text-rose-500">*</span></label>
                <input name="rent_min" required type="number" min="0" value="{{ old('rent_min') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Rent (max) ₹ <span class="text-rose-500">*</span></label>
                <input name="rent_max" required type="number" min="0" value="{{ old('rent_max') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Security ₹</label>
                <input name="security_deposit" type="number" min="0" value="{{ old('security_deposit') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Total rooms</label>
                <input name="total_rooms" type="number" min="0" value="{{ old('total_rooms') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Available rooms</label>
                <input name="available_rooms" type="number" min="0" value="{{ old('available_rooms') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div class="col-span-3 flex items-end">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="food_included" value="1" @checked(old('food_included')) class="rounded w-5 h-5">
                    <span class="font-semibold">🍽️ Food included</span>
                </label>
            </div>
        </div>

        {{-- Extra info: food type, construction year, pet & guest policy --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Food type</label>
                <select name="food_type" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                    <option value="">— Select —</option>
                    <option value="veg" @selected(old('food_type') === 'veg')>Veg only</option>
                    <option value="non_veg" @selected(old('food_type') === 'non_veg')>Non-veg only</option>
                    <option value="both" @selected(old('food_type') === 'both')>Both veg & non-veg</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Building construction year</label>
                <input name="construction_year" type="number" min="1950" max="{{ date('Y') + 1 }}" placeholder="e.g. 2018" value="{{ old('construction_year') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
            </div>
            <div class="flex flex-col justify-end gap-2 pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="pet_allowed" value="1" @checked(old('pet_allowed')) class="rounded w-5 h-5">
                    <span class="font-semibold">🐾 Pets allowed</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="guest_entry_allowed" value="1" @checked(old('guest_entry_allowed')) class="rounded w-5 h-5">
                    <span class="font-semibold">🚪 Guest entry allowed</span>
                </label>
            </div>
        </div>

        {{-- Meal timing — per meal, and which days it's served --}}
        <div class="mt-4">
            <label class="text-xs font-bold uppercase text-ink-900/60 mb-1 block">Meal timings</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach(['breakfast' => '🥣 Breakfast', 'lunch' => '🍛 Lunch', 'dinner' => '🍽 Dinner'] as $meal => $label)
                    <div class="border border-ink-900/10 rounded-xl p-3">
                        <p class="text-sm font-semibold mb-2">{{ $label }}</p>
                        <input name="{{ $meal }}_timing" placeholder="e.g. 8-9 AM"
                               value="{{ old("{$meal}_timing") }}"
                               class="w-full px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
                        <select name="{{ $meal }}_days" class="w-full mt-2 px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
                            <option value="all" @selected(old("{$meal}_days") === 'all')>All days</option>
                            <option value="weekdays" @selected(old("{$meal}_days") === 'weekdays')>Weekdays only (Mon-Fri)</option>
                            <option value="weekends" @selected(old("{$meal}_days") === 'weekends')>Weekends only (Sat-Sun)</option>
                            <option value="none" @selected(old("{$meal}_days", 'none') === 'none')>Not served</option>
                        </select>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-ink-900/50 mt-1">Example: Mon–Fri breakfast + dinner only, Sat–Sun sab teen meals — bas Lunch ko "Weekends only" set kar do.</p>
        </div>

        {{-- Nearby locations — metro, hospital, market, university etc --}}
        <div class="mt-4">
            <label class="text-xs font-bold uppercase text-ink-900/60 mb-1 block">Nearby locations</label>
            <div id="landmarkRows" class="space-y-2">
                <div class="flex flex-col sm:flex-row gap-2 landmark-row">
                    <select name="landmark_id[]" class="flex-1 px-3 py-2.5 rounded-lg border border-ink-900/15 text-sm">
                        <option value="">— Select location —</option>
                        @foreach($landmarks as $l)
                            <option value="{{ $l->id }}">{{ $l->name }} ({{ ucfirst($l->type) }})</option>
                        @endforeach
                    </select>
                    <input name="landmark_distance[]" type="number" step="0.1" min="0" placeholder="Distance (km)"
                           class="w-full sm:w-40 px-3 py-2.5 rounded-lg border border-ink-900/15 text-sm">
                    <button type="button" onclick="this.closest('.landmark-row').remove()"
                            class="px-3 py-2.5 rounded-lg border border-rose-200 text-rose-600 text-sm shrink-0">✕ Remove</button>
                </div>
            </div>
            <button type="button" id="addLandmarkRow" class="mt-2 text-sm font-semibold text-coral-600">+ Add another nearby location</button>
        </div>
        <script>
            document.getElementById('addLandmarkRow')?.addEventListener('click', function () {
                const rows = document.getElementById('landmarkRows');
                const first = rows.querySelector('.landmark-row');
                const clone = first.cloneNode(true);
                clone.querySelectorAll('select, input').forEach(el => el.value = '');
                rows.appendChild(clone);
            });
        </script>
    </div>

    {{-- DESCRIPTION --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-lg mb-4">📝 About this PG</h3>
        <div class="space-y-3">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Description</label>
                <textarea name="description" rows="4" placeholder="Brief description of the PG, location benefits, vibe..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">House rules</label>
                <textarea name="rules" rows="3" placeholder="e.g. No smoking, gates close at 10 PM, visitors allowed in common area only" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">{{ old('rules') }}</textarea>
            </div>
        </div>
    </div>

    {{-- AMENITIES --}}
    @if($amenities->count())
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-lg mb-4">✨ Amenities</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        @foreach($amenities as $a)
 <label class="flex items-center gap-2 p-3 rounded-xl border border-ink-900/10 cursor-pointer hover:bg-cream-200 hover:border-coral-300 transition min-w-0 overflow-hidden">
        <input type="checkbox" name="amenities[]" value="{{ $a->id }}" class="rounded">
        <span class="text-lg">{{ $a->icon ?? '✨' }}</span>
       <span class="text-sm break-words leading-tight whitespace-normal">
    {{ $a->name }}
</span>
    </label>
@endforeach
        </div>
    </div>
    @endif

    {{-- IMAGES --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-lg mb-4">📸 Photos</h3>
        <div class="space-y-3">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Cover image (main photo) <span class="text-rose-500">*</span></label>
                <input type="file" name="cover_image" accept="image/*" class="w-full mt-1 text-sm">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Additional photos (multiple)</label>
                <input type="file" name="images[]" accept="image/*" multiple class="w-full mt-1 text-sm">
                <p class="text-xs text-ink-900/50 mt-1">Upload 5-10 photos: rooms, bathroom, common area, mess, exterior</p>
            </div>
        </div>
    </div>

    {{-- STATUS --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h3 class="font-display font-bold text-lg mb-4">⚙️ Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <label class="flex items-center gap-2 p-4 rounded-xl border-2 border-ink-900/10 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked class="rounded w-5 h-5">
                <div>
                    <div class="font-bold">Active</div>
                    <div class="text-xs text-ink-900/60">Visible on site</div>
                </div>
            </label>
            <label class="flex items-center gap-2 p-4 rounded-xl border-2 border-emerald-200 cursor-pointer">
                <input type="checkbox" name="is_verified" value="1" checked class="rounded w-5 h-5">
                <div>
                    <div class="font-bold text-emerald-700">✓ Verified</div>
                    <div class="text-xs text-ink-900/60">Admin verified PG</div>
                </div>
            </label>
            <label class="flex items-center gap-2 p-4 rounded-xl border-2 border-coral-200 cursor-pointer">
                <input type="checkbox" name="is_featured" value="1" class="rounded w-5 h-5">
                <div>
                    <div class="font-bold text-coral-700">⭐ Featured</div>
                    <div class="text-xs text-ink-900/60">Top of search results</div>
                </div>
            </label>
        </div>
    </div>

    {{-- SUBMIT --}}
    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg shadow-lg shadow-coral-500/30">
            ✓ Create Property
        </button>
        <a href="{{ route('admin.properties.index') }}" class="px-8 py-4 border border-ink-900/15 rounded-xl font-bold">Cancel</a>
    </div>
</form>

<script>
// 🔥 Filter localities based on selected city
function filterLocalities() {
    const cityId = document.getElementById('citySelect').value;
    const localitySelect = document.getElementById('localitySelect');

    Array.from(localitySelect.options).forEach(opt => {
        if (!opt.value) return; // skip placeholder
        const optCity = opt.dataset.city;
        opt.style.display = (cityId === '' || optCity === cityId) ? '' : 'none';
    });

    // Reset selection if hidden
    if (localitySelect.selectedOptions[0]?.style.display === 'none') {
        localitySelect.value = '';
    }
}

// 🔥 Toggle manual locality input
function toggleManualLocality() {
    const wrap = document.getElementById('manualLocalityWrap');
    const dropdown = document.getElementById('localitySelect');
    const manualInput = document.getElementById('manualLocality');

    wrap.classList.toggle('hidden');

    if (!wrap.classList.contains('hidden')) {
        // Manual mode active
        dropdown.value = ''; // clear dropdown
        dropdown.disabled = true;
        manualInput.required = true;
        manualInput.focus();
    } else {
        // Back to dropdown
        dropdown.disabled = false;
        manualInput.required = false;
        manualInput.value = '';
    }
}

// On page load — filter once
filterLocalities();
</script>

{{-- ===== LEAFLET MAP (FREE - OpenStreetMap) ===== --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map, marker, addressDebounce;

document.addEventListener('DOMContentLoaded', function() {
    const mapEl = document.getElementById('locationMap');
    if (!mapEl) return;

    const defaultLat = parseFloat(document.getElementById('latInput').value) || 28.6139;
    const defaultLng = parseFloat(document.getElementById('lngInput').value) || 77.2090;

    map = L.map('locationMap').setView([defaultLat, defaultLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    marker = L.marker([defaultLat, defaultLng], {
        draggable: true,
        title: 'Drag to your PG location'
    }).addTo(map);

    marker.bindPopup('🏠 Your PG location<br>Drag me to adjust').openPopup();

    marker.on('dragend', function() {
        const pos = marker.getLatLng();
        updateCoords(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng.lat, e.latlng.lng);
    });

    // 🔥 AUTO-UPDATE MAP when address fields change
    setupAutoMap();
});

function setupAutoMap() {
    // Watch address-related fields
    const fieldsToWatch = ['address_line', 'pincode', 'landmark'];

    fieldsToWatch.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.addEventListener('input', () => triggerAddressSearch());
            field.addEventListener('change', () => triggerAddressSearch());
        }
    });

    // Watch city + locality dropdowns
    const citySelect = document.getElementById('citySelect');
    const localitySelect = document.getElementById('localitySelect');
    const manualLocality = document.getElementById('manualLocality');

    if (citySelect) citySelect.addEventListener('change', () => triggerAddressSearch());
    if (localitySelect) localitySelect.addEventListener('change', () => triggerAddressSearch());
    if (manualLocality) manualLocality.addEventListener('input', () => triggerAddressSearch());
}

function triggerAddressSearch() {
    clearTimeout(addressDebounce);
    addressDebounce = setTimeout(() => {
        autoUpdateMapFromAddress();
    }, 1500); // wait 1.5 sec after user stops typing
}

function autoUpdateMapFromAddress() {
    const addressLine = (document.querySelector('[name="address_line"]')?.value || '').trim();
    const pincode = (document.querySelector('[name="pincode"]')?.value || '').trim();
    const landmark = (document.querySelector('[name="landmark"]')?.value || '').trim();
    const citySelect = document.getElementById('citySelect');
    const cityName = citySelect ? citySelect.options[citySelect.selectedIndex]?.text : '';

    const localitySelect = document.getElementById('localitySelect');
    const localityName = localitySelect ? localitySelect.options[localitySelect.selectedIndex]?.text : '';
    const manualLocality = document.getElementById('manualLocality')?.value || '';

    const finalLocality = manualLocality || (localityName !== '— Select locality —' ? localityName : '');

    // Build search query
    const parts = [addressLine, landmark, finalLocality, cityName, pincode]
        .filter(p => p && p !== '— Select city —' && p !== '— Select locality —' && p.length > 2);

    if (parts.length < 2) return; // need at least 2 parts

    const query = parts.join(', ') + ', India';

    // Search Nominatim
    const viewbox = '68.0,8.0,97.5,37.5';
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=in&limit=1&viewbox=${viewbox}&bounded=1`;

    fetch(url, { headers: { 'Accept-Language': 'en' } })
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) return; // silently fail
            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);

            // Animate to new location
            map.setView([lat, lng], 17);
            marker.setLatLng([lat, lng]);
            updateCoords(lat, lng);
            marker.bindPopup('📍 Auto-located<br><small>Drag if needed</small>').openPopup();

            // Visual feedback
            showAutoLocateBadge();
        })
        .catch(() => {});
}

function showAutoLocateBadge() {
    let badge = document.getElementById('autoLocateBadge');
    if (!badge) {
        badge = document.createElement('div');
        badge.id = 'autoLocateBadge';
        badge.style.cssText = 'position:absolute; top:10px; right:10px; background:#10b981; color:white; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:bold; z-index:1000; box-shadow:0 4px 10px rgba(0,0,0,0.2);';
        badge.textContent = '✓ Auto-located!';
        document.getElementById('locationMap').appendChild(badge);
    } else {
        badge.style.display = 'block';
    }
    setTimeout(() => { if (badge) badge.style.display = 'none'; }, 2500);
}

function updateCoords(lat, lng) {
    document.getElementById('latInput').value = lat.toFixed(6);
    document.getElementById('lngInput').value = lng.toFixed(6);
}

function searchLocation() {
    const query = document.getElementById('mapSearchInput').value.trim();
    if (!query) {
        alert('Please type an address to search');
        return;
    }

    const searchBtn = event.target;
    const originalText = searchBtn.innerHTML;

    // Any Google Maps link (short share.google / maps.app.goo.gl, or a full
    // one) — resolve it server-side, since a browser can't follow the short
    // link's redirect itself (CORS).
    if (/^https?:\/\//i.test(query)) {
        searchBtn.innerHTML = '⏳ Opening link...';
        searchBtn.disabled = true;
        fetch('{{ route('map.resolve') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}' },
            body: JSON.stringify({ url: query })
        })
            .then(r => r.json())
            .then(d => {
                searchBtn.innerHTML = originalText;
                searchBtn.disabled = false;
                if (d.success) { selectResult({ lat: d.lat, lon: d.lng, display_name: 'Pinned from Google Maps link' }); }
                else { alert(d.message || 'Could not read that link. Try dragging the marker instead.'); }
            })
            .catch(() => {
                searchBtn.innerHTML = originalText;
                searchBtn.disabled = false;
                alert('Could not open that link. Try dragging the marker instead.');
            });
        return;
    }

    searchBtn.innerHTML = '⏳ Searching...';
    searchBtn.disabled = true;

    const viewbox = '68.0,8.0,97.5,37.5';
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', India')}&countrycodes=in&limit=5&viewbox=${viewbox}&bounded=1&addressdetails=1`;

    fetch(url, { headers: { 'Accept-Language': 'en' } })
        .then(r => r.json())
        .then(data => {
            searchBtn.innerHTML = originalText;
            searchBtn.disabled = false;

            if (data.length === 0) {
                alert('Location not found. Try a more specific address like "Sector 44, Noida, UP"');
                return;
            }

            if (data.length > 1) {
                showResultsPicker(data);
            } else {
                selectResult(data[0]);
            }
        })
        .catch(err => {
            searchBtn.innerHTML = originalText;
            searchBtn.disabled = false;
            alert('Search failed. Please try again.');
        });
}

function showResultsPicker(results) {
    let html = '<div style="max-height:300px; overflow-y:auto;">';
    html += '<p style="margin-bottom:10px; font-weight:bold;">Pick the right location:</p>';
    results.forEach((r, i) => {
        html += `<button type="button" onclick="window.selectMapResult(${i})" style="display:block; width:100%; padding:10px; margin-bottom:5px; text-align:left; border:1px solid #ddd; border-radius:6px; background:white; cursor:pointer;" onmouseover="this.style.background='#fff3f1'" onmouseout="this.style.background='white'">
            <strong>${r.display_name.split(',')[0]}</strong><br>
            <small style="color:#666;">${r.display_name}</small>
        </button>`;
    });
    html += '</div>';

    const dialog = document.createElement('div');
    dialog.id = 'mapResultsDialog';
    dialog.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border-radius:12px; box-shadow:0 20px 50px rgba(0,0,0,0.3); z-index:10000; width:90%; max-width:500px;';
    dialog.innerHTML = html + '<button type="button" onclick="document.getElementById(\'mapResultsDialog\').remove()" style="margin-top:10px; padding:8px 16px; background:#ddd; border:none; border-radius:6px; cursor:pointer;">Cancel</button>';
    document.body.appendChild(dialog);

    window._mapResults = results;
    window.selectMapResult = function(i) {
        selectResult(window._mapResults[i]);
        document.getElementById('mapResultsDialog').remove();
    };
}

function selectResult(result) {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    map.setView([lat, lng], 17);
    marker.setLatLng([lat, lng]);
    updateCoords(lat, lng);
    marker.bindPopup('📍 ' + result.display_name.substring(0, 100)).openPopup();
}

function useMyLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation not supported');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 17);
            marker.setLatLng([lat, lng]);
            updateCoords(lat, lng);
            marker.bindPopup('📍 Your current location').openPopup();
        },
        function(err) {
            alert('Could not get your location. Please allow location access.');
        }
    );
}
</script>

{{-- Jump to whichever field failed validation and show the exact
     reason right next to it, instead of only a generic banner at top. --}}
@if($errors->any())
<script>
(function () {
    const errors = @json($errors->messages());
    let firstField = null;
    Object.keys(errors).forEach(function (key) {
        const base = key.split('.')[0];
        const field = document.querySelector('[name="' + base + '"]') || document.querySelector('[name="' + base + '[]"]');
        if (!field) return;
        field.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
        const msg = document.createElement('p');
        msg.className = 'text-rose-600 text-xs mt-1 font-semibold';
        msg.textContent = errors[key][0];
        field.insertAdjacentElement('afterend', msg);
        if (!firstField) firstField = field;
    });
    if (firstField) {
        firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstField.focus();
    }
})();
</script>
@endif

@endsection