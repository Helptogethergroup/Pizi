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
            <select name="city_id" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                @foreach($cities as $c)
                    <option value="{{ $c->id }}" @selected(old('city_id', $property->city_id ?? '') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    <div>
    <label class="text-xs font-semibold text-ink-900/60 uppercase">
        Address
    </label>

    <textarea 
        name="address"
        rows="3"
        class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"
        placeholder="Enter full address"
    >{{ old('address', $property->address ?? '') }}</textarea>
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
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Pincode</label><input name="pincode" value="{{ old('pincode', $property->pincode ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Total rooms</label><input name="total_rooms" type="number" value="{{ old('total_rooms', $property->total_rooms ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Available rooms</label><input name="available_rooms" type="number" value="{{ old('available_rooms', $property->available_rooms ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Latitude</label><input name="latitude" step="any" value="{{ old('latitude', $property->latitude ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
        <div><label class="text-xs font-semibold text-ink-900/60 uppercase">Longitude</label><input name="longitude" step="any" value="{{ old('longitude', $property->longitude ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15"></div>
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
</form>
