<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Amenity;

use App\Models\City;
use App\Models\Locality;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    public function index()
    {
        $this->checkAccess();
        $properties = Property::whereIn('id', auth()->user()->getManagedPropertyIds())
            ->with(['city', 'locality'])
            ->latest()->paginate(15);
        return view('owner.properties.index', compact('properties'));
    }

public function create()
{
    if (auth()->user()->role === 'pg_manager') {
        abort(403, 'Only the property owner can add new listings.');
    }
    $cities = City::orderBy('name')->get();
    $localities = Locality::all();
    $amenities = Amenity::all();
    $landmarks = \App\Models\Landmark::where('is_active', true)->orderBy('name')->get();

    return view('owner.properties.create', compact('cities', 'localities', 'amenities', 'landmarks'));
}
public function store(Request $request)
{
    if (auth()->user()->role === 'pg_manager') {
        abort(403, 'Only the property owner can add new listings.');
    }
    try {
        $data = $this->validateProperty($request);
        $data['owner_id'] = auth()->id();
        $data['nearby_university_id'] = $request->nearby_university_id;
        $data['pet_allowed'] = $request->boolean('pet_allowed');
        $data['guest_entry_allowed'] = $request->boolean('guest_entry_allowed');
        $data['food_timing'] = $this->buildFoodTiming($request);

        // New properties must be verified by admin before going live
       $data['is_active'] = false;

   // Handle manual university entry
        if (empty($data['nearby_university_id']) && !empty($request->university_name)) {
            $uniName = trim($request->university_name);
            $existing = \DB::table('universities')->where('name', $uniName)->first();
            
            if ($existing) {
                $data['nearby_university_id'] = $existing->id;
            } else {
                // Use the property's actual selected city — not hardcoded Delhi.
                $selectedCity = \App\Models\City::find($data['city_id']);
                $uniId = \DB::table('universities')->insertGetId([
                    'name' => $uniName,
                    'abbreviation' => $request->university_abbreviation ?? strtoupper(substr($uniName, 0, 3)),
                    'city' => $selectedCity->name ?? 'Unknown',
                    'type' => 'university',
                    'latitude' => $selectedCity->latitude ?? null,
                    'longitude' => $selectedCity->longitude ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $data['nearby_university_id'] = $uniId;
            }
        }
        // Auto-generate unique slug
        $slug = \Illuminate\Support\Str::slug($data['name']);
        $originalSlug = $slug;
        $counter = 1;
        
        // withTrashed() zaroori hai — soft-deleted property ka slug bhi DB
        // ke unique index me abhi tak baitha hota hai, sirf yahan check na
        // karne se dobara wahi slug try hota reh jata aur INSERT fail hota.
        while (Property::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $data['slug'] = $slug;

        // Handle cover image
        if ($request->hasFile('cover_image')) {
            try {
                $data['cover_image'] = $request->file('cover_image')
                    ->store('properties/covers', 'public');
            } catch (\Exception $e) {
                return redirect()->back()->withInput()
                    ->with('error', 'Image upload failed. Please try a smaller image.');
            }
        }

        $sharing = [];
        foreach (['single', 'double', 'triple'] as $type) {
            if ($request->filled("sharing_$type")) {
                $sharing[$type] = (float) $request->input("sharing_$type");
            }
        }
        $data['sharing_options'] = $sharing;
        
        // Handle manual locality entry
        if (empty($data['locality_id']) && !empty($request->locality_name)) {
            $locality = \App\Models\Locality::firstOrCreate(
                [
                    'city_id' => $data['city_id'],
                    'name' => trim($request->locality_name),
                ],
                [
                    'slug' => \Illuminate\Support\Str::slug($request->locality_name) . '-' . \Illuminate\Support\Str::random(4),
                    'is_active' => true,
                ]
            );
            $data['locality_id'] = $locality->id;
        }

        $property = Property::create($data);

        try {
            \App\Models\User::where('role', 'admin')->get()->each(
                fn ($admin) => $admin->notify(new \App\Notifications\PropertySubmittedForVerification($property))
            );
        } catch (\Exception $e) {
            \Log::warning('Property submission notification failed: ' . $e->getMessage());
        }

        if ($request->filled('amenities')) {
            $property->amenities()->sync($request->amenities);
        }

        $this->syncLandmarks($request, $property);

        // Additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $img) {
                try {
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'image_path' => $img->store('properties/gallery', 'public'),
                        'display_order' => $i,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Image upload failed for property', ['property_id' => $property->id]);
                }
            }
        }

        return redirect()->route('owner.properties.index')
            ->with('success', '✅ Property added successfully! Admin will verify within 24 hours.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withInput()->withErrors($e->errors());

    } catch (\Exception $e) {
        \Log::error('Property creation failed', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()->withInput()
            ->with('error', 'Unable to add property. Please try again or contact support.');
    }
}
    public function edit(Property $property)
    {
        $this->authorizeOwner($property);
        $cities = City::where('is_active', true)->orderBy('name')->get();
        $localities = Locality::where('is_active', true)->orderBy('name')->get();
        $amenities = Amenity::orderBy('name')->get();
        $landmarks = \App\Models\Landmark::where('is_active', true)->orderBy('name')->get();
        $property->load('landmarks');
        return view('owner.properties.edit', compact('property', 'cities', 'localities', 'amenities', 'landmarks'));
    }


public function update(Request $request, Property $property)
{
    try {
        $this->authorizeOwner($property);
        $data = $this->validateProperty($request);
         $data['nearby_university_id'] = $request->nearby_university_id;
         $data['pet_allowed'] = $request->boolean('pet_allowed');
         $data['guest_entry_allowed'] = $request->boolean('guest_entry_allowed');
         $data['food_timing'] = $this->buildFoodTiming($request);
         
     // Handle manual university entry
        if (empty($data['nearby_university_id']) && !empty($request->university_name)) {
            $uniName = trim($request->university_name);
            $existing = \DB::table('universities')->where('name', $uniName)->first();
            
            if ($existing) {
                $data['nearby_university_id'] = $existing->id;
            } else {
                // Use the property's actual selected city — not hardcoded Delhi.
                $selectedCity = \App\Models\City::find($data['city_id']);
                $uniId = \DB::table('universities')->insertGetId([
                    'name' => $uniName,
                    'abbreviation' => $request->university_abbreviation ?? strtoupper(substr($uniName, 0, 3)),
                    'city' => $selectedCity->name ?? 'Unknown',
                    'type' => 'university',
                    'latitude' => $selectedCity->latitude ?? null,
                    'longitude' => $selectedCity->longitude ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $data['nearby_university_id'] = $uniId;
            }
        }

        // Auto-generate unique slug if name changed
        if ($data['name'] !== $property->name) {
            $slug = \Illuminate\Support\Str::slug($data['name']);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Property::where('slug', $slug)->where('id', '!=', $property->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('cover_image')) {
            try {
                if ($property->cover_image && !str_starts_with($property->cover_image, 'http')) {
                    Storage::disk('public')->delete($property->cover_image);
                }
                $data['cover_image'] = $request->file('cover_image')
                    ->store('properties/covers', 'public');
            } catch (\Exception $e) {
                \Log::warning('Cover image update failed', ['property_id' => $property->id]);
                return redirect()->back()->withInput()
                    ->with('error', 'Image upload failed. Please try a smaller image.');
            }
        }

        $sharing = [];
        foreach (['single', 'double', 'triple'] as $type) {
            if ($request->filled("sharing_$type")) {
                $sharing[$type] = (float) $request->input("sharing_$type");
            }
        }
        $data['sharing_options'] = $sharing;
        
        // Handle manual locality entry
        if (empty($data['locality_id']) && !empty($request->locality_name)) {
            $locality = \App\Models\Locality::firstOrCreate(
                [
                    'city_id' => $data['city_id'],
                    'name' => trim($request->locality_name),
                ],
                [
                    'slug' => \Illuminate\Support\Str::slug($request->locality_name) . '-' . \Illuminate\Support\Str::random(4),
                    'is_active' => true,
                ]
            );
            $data['locality_id'] = $locality->id;
        }

        $property->update($data);

        if ($request->has('amenities')) {
            $property->amenities()->sync($request->amenities ?? []);
        }

        $this->syncLandmarks($request, $property);

        if ($request->hasFile('images')) {
            $start = $property->images()->max('display_order') ?? 0;
            foreach ($request->file('images') as $i => $img) {
                try {
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'image_path' => $img->store('properties/gallery', 'public'),
                        'display_order' => $start + 1 + $i,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Gallery image upload failed', ['property_id' => $property->id]);
                }
            }
        }

        return redirect()->route('owner.properties.index')
            ->with('success', '✅ Property updated successfully!');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withInput()->withErrors($e->errors());

} catch (\Throwable $e) {
        \Log::error('Property update failed', [
            'property_id' => $property->id,
            'error' => $e->getMessage(),
        ]);
        
        // 🔥 TEMPORARY DEBUG
        return redirect()->back()->withInput()
            ->with('error', 'DEBUG: ' . $e->getMessage());
    }
}

    public function toggle(Property $property)
    {
        $this->authorizeOwner($property);
        $property->is_active = !$property->is_active;
        $property->save();
        return back()->with('success', 'Listing status updated.');
    }

    public function destroy(Property $property)
    {
        $this->authorizeOwner($property);
        if (auth()->user()->role === 'pg_manager') {
            abort(403, 'Only the property owner can delete a listing.');
        }
        $property->delete();
        return back()->with('success', 'Property removed.');
    }
   private function authorizeOwner(Property $property): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $this->checkAccess();

        if (!auth()->user()->getManagedPropertyIds()->contains($property->id)) {
            abort(403);
        }
    }

    private function checkAccess(): void
    {
        if (!auth()->user()->hasFeature('properties')) {
            abort(403, 'You do not have access to Properties.');
        }
    }

    /**
     * Meal-wise timing + which days it's served — e.g. breakfast & dinner
     * daily, lunch only on weekends. Stored as JSON on food_timing.
     */
    private function buildFoodTiming(Request $request): ?array
    {
        $timing = [];
        foreach (['breakfast', 'lunch', 'dinner'] as $meal) {
            $time = $request->input("{$meal}_timing");
            $days = $request->input("{$meal}_days");
            if ($time || ($days && $days !== 'none')) {
                $timing[$meal] = ['timing' => $time, 'days' => $days ?: 'all'];
            }
        }
        return $timing ?: null;
    }

    /**
     * Nearby landmarks (metro/hospital/market/etc) with distance — synced
     * into the property_landmarks pivot. Expects landmark_id[] + matching
     * landmark_distance[] arrays from the form.
     */
    private function syncLandmarks(Request $request, Property $property): void
    {
        if (!$request->has('landmark_id')) return;

        $ids = $request->input('landmark_id', []);
        $distances = $request->input('landmark_distance', []);
        $sync = [];
        foreach ($ids as $i => $id) {
            if (!$id) continue;
            $sync[$id] = ['distance_km' => $distances[$i] ?? null];
        }
        $property->landmarks()->sync($sync);
    }

    private function validateProperty(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'rules' => 'nullable|string',
            'city_id' => 'required|exists:cities,id',
            'locality_id' => 'required_without:locality_name|nullable|integer|exists:localities,id',
            'locality_name' => 'required_without:locality_id|nullable|string|max:120',
            'gender' => 'required|in:male,female,unisex',
            'property_type' => 'required|in:pg,hostel,coliving,flatmate',
            'rent_min' => 'required|numeric|min:0',
            'rent_max' => 'required|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'food_included' => 'nullable|boolean',
            'food_type' => 'nullable|in:veg,non_veg,both',
            'breakfast_timing' => 'nullable|string|max:60',
            'breakfast_days' => 'nullable|in:all,weekdays,weekends,none',
            'lunch_timing' => 'nullable|string|max:60',
            'lunch_days' => 'nullable|in:all,weekdays,weekends,none',
            'dinner_timing' => 'nullable|string|max:60',
            'dinner_days' => 'nullable|in:all,weekdays,weekends,none',
            'construction_year' => 'nullable|integer|min:1950|max:' . (date('Y') + 1),
            'pet_allowed' => 'nullable|boolean',
            'guest_entry_allowed' => 'nullable|boolean',
            'address_line' => 'required|string|max:255',
            'landmark' => 'nullable|string|max:120',
            'nearby_police_station' => 'nullable|string|max:200',
            'pincode' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'total_rooms' => 'nullable|integer|min:0',
            'available_rooms' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:320',
            'cover_image' => 'nullable|image|max:4096',
            'images.*' => 'nullable|image|max:4096',
        ]);
    }
}