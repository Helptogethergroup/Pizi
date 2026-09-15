<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
public function index(Request $request)
    {
        $query = Property::with(['owner', 'city', 'locality']);

        $filter = $request->get('filter', 'all');

            if ($filter === 'my') {
            $query->where('owner_id', auth()->id());
        } elseif ($filter === 'owners') {
            $query->where('owner_id', '!=', auth()->id())
                  ->whereHas('owner', fn($q) => $q->where('role', 'owner'));
        } elseif ($filter === 'unassigned') {
            $query->whereNull('owner_id');
        }

        // Owner filter — from users page click
        if ($owner = $request->get('owner')) {
            $query->where('owner_id', $owner);
        }

        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $properties = $query->latest()->paginate(20)->withQueryString();

        // 🔥 Load all owners for the dropdown
        $allOwners = \App\Models\User::whereIn('role', ['owner', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'role']);

        return view('admin.properties', compact('properties', 'filter', 'allOwners'));
    }

public function verify(Property $property)
{
    $property->is_verified = !$property->is_verified;
    $property->verified_at = $property->is_verified ? now() : null;
    // is_active ko touch nahi karna — verify/unverify sirf verification
    // badge control kare, property ko site se hide na kare
    $property->save();

    // Sirf verify hone par bhejo — unverify par nahi
    if ($property->is_verified && $property->owner && $property->owner->phone) {
        try {
            app(\App\Services\WhatsAppService::class)->sendTemplate(
                $property->owner->phone,
                'property_verified_alert',
                [
                    $property->owner->name,
                    $property->name,
                    rtrim(config('app.url'), '/') . '/pg/' . $property->slug,
                ]
            );
        } catch (\Exception $e) {
            \Log::warning('Property verified WhatsApp alert failed: ' . $e->getMessage());
        }
    }

    return back()->with('success', 'Verification status updated.');
}

    public function feature(Property $property)
    {
        $property->is_featured = !$property->is_featured;
        $property->save();
        return back()->with('success', 'Featured status updated.');
    }
    
    /**
     * Show create property form (admin)
     */
   /**
     * Show create property form (admin) — no owner selection
     */
    public function create()
    {
        $cities = \App\Models\City::where('is_active', true)->orderBy('name')->get();
        $localities = \App\Models\Locality::where('is_active', true)->orderBy('name')->get();
        $amenities = \App\Models\Amenity::orderBy('name')->get();
        $landmarks = \App\Models\Landmark::where('is_active', true)->orderBy('name')->get();

        return view('admin.properties.create', compact('cities', 'localities', 'amenities', 'landmarks'));
    }

    /**
     * Store new property (admin) — admin user is the owner
     */
    public function store(Request $request)
{
    try {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'property_type' => 'required|in:pg,hostel,coliving,flatmate',
            'gender' => 'required|in:male,female,unisex',
            'city_id' => 'required|exists:cities,id',
            'locality_id' => 'required_without:locality_name|nullable|integer|exists:localities,id',
            'locality_name' => 'required_without:locality_id|nullable|string|max:120',
            'address_line' => 'required|string|max:500',
            'pincode' => 'nullable|string|max:10',
            'landmark' => 'nullable|string|max:200',
            'nearby_police_station' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'google_map_link' => 'nullable|url|max:1000',
            'rent_min' => 'required|integer|min:0',
            'rent_max' => 'required|integer|min:0',
            'security_deposit' => 'nullable|integer|min:0',
            'total_rooms' => 'nullable|integer|min:0',
            'available_rooms' => 'nullable|integer|min:0',
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
            'description' => 'nullable|string',
            'rules' => 'nullable|string',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
            'cover_image' => 'nullable|image|max:4096',
            'images.*' => 'nullable|image|max:4096',
            'is_verified' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);
        
// Handle nearby university
        $data['nearby_university_id'] = $request->nearby_university_id;

        // Handle manual university entry
        if (empty($data['nearby_university_id']) && !empty($request->university_name)) {
            $uniName = trim($request->university_name);
            $uniAbbr = $request->university_abbreviation ?? strtoupper(substr($uniName, 0, 3));
            
            $uniId = \DB::table('universities')->insertGetId([
                'name' => $uniName,
                'abbreviation' => $uniAbbr,
                'slug' => \Illuminate\Support\Str::slug($uniName) . '-' . \Illuminate\Support\Str::random(4),
                'city' => 'Delhi',
                'type' => 'university',
                'latitude' => 28.6139,
                'longitude' => 77.2090,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $data['nearby_university_id'] = $uniId;
        }
        // Admin user becomes the owner
        $data['owner_id'] = auth()->id();

        // Handle manual locality entry
        if (empty($data['locality_id']) && !empty($data['locality_name'])) {
            $locality = \App\Models\Locality::firstOrCreate(
                [
                    'city_id' => $data['city_id'],
                    'name' => trim($data['locality_name']),
                ],
                [
                    'slug' => \Illuminate\Support\Str::slug($data['locality_name']) . '-' . \Illuminate\Support\Str::random(4),
                    'is_active' => true,
                ]
            );
            $data['locality_id'] = $locality->id;
        }

        // Auto-generate unique slug
        $baseSlug = \Illuminate\Support\Str::slug($data['name']);
        $slug = $baseSlug;
        $i = 1;
        // withTrashed() zaroori hai — soft-deleted property ka slug bhi DB
        // ke unique index me abhi tak baitha hota hai, sirf yahan check na
        // karne se dobara wahi slug try hota reh jata aur INSERT fail hota.
        while (\App\Models\Property::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $data['slug'] = $slug;

        // Cover image
        if ($request->hasFile('cover_image')) {
            try {
                $data['cover_image'] = $request->file('cover_image')->store('properties/covers', 'public');
            } catch (\Exception $e) {
                \Log::warning('Cover image upload failed', ['error' => $e->getMessage()]);
                return redirect()->back()->withInput()
                    ->with('error', 'Image upload failed. Please try a smaller image.');
            }
        }

        // Booleans
        $data['is_verified'] = $request->boolean('is_verified', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['food_included'] = $request->boolean('food_included');
        $data['pet_allowed'] = $request->boolean('pet_allowed');
        $data['guest_entry_allowed'] = $request->boolean('guest_entry_allowed');
        $data['food_timing'] = $this->buildFoodTiming($request);

        $amenityIds = $data['amenities'] ?? [];
        unset($data['amenities'], $data['locality_name']);

        $property = \App\Models\Property::create($data);

        if ($amenityIds) {
            $property->amenities()->sync($amenityIds);
        }

        $this->syncLandmarks($request, $property);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                try {
                    $path = $img->store('properties/gallery', 'public');
                    \App\Models\PropertyImage::create([
                        'property_id' => $property->id,
                        'image_path' => $path,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Gallery image upload failed', ['property_id' => $property->id]);
                }
            }
        }

        return redirect()->route('admin.properties.index')
            ->with('success', '✅ Property created: ' . $property->name);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withInput()->withErrors($e->errors());

    } catch (\Illuminate\Database\QueryException $e) {
        \Log::error('Admin property creation DB error', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);
        if ($field = $this->missingRequiredField($e)) {
            return redirect()->back()->withInput()
                ->withErrors([$field => 'The ' . str_replace('_', ' ', $field) . ' field is required.']);
        }
        if ($col = $this->unknownColumn($e)) {
            return redirect()->back()->withInput()
                ->with('error', "Server needs an update — '{$col}' column is missing on this server's database. Run pending migrations (php artisan migrate), then try again.");
        }
        return redirect()->back()->withInput()
            ->with('error', 'Unable to create property (DB error): ' . \Illuminate\Support\Str::limit($e->getMessage(), 200));

    } catch (\Exception $e) {
        \Log::error('Admin property creation failed', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()->withInput()
            ->with('error', 'Unable to create property. Please try again or contact support.');
    }
}

/**
 * Turn a raw "Column 'x' cannot be null" / "doesn't have a default
 * value" DB error into the column name, so the caller can show it as
 * a normal per-field validation message instead of a generic error.
 */
private function missingRequiredField(\Illuminate\Database\QueryException $e): ?string
{
    if (preg_match("/Column '([a-zA-Z0-9_]+)' cannot be null/", $e->getMessage(), $m)) return $m[1];
    if (preg_match("/Field '([a-zA-Z0-9_]+)' doesn't have a default value/", $e->getMessage(), $m)) return $m[1];
    return null;
}

/**
 * "Unknown column 'x' in 'field list'" — the app code expects a column
 * that doesn't exist on this DB yet, almost always because a migration
 * hasn't been run on this server.
 */
private function unknownColumn(\Illuminate\Database\QueryException $e): ?string
{
    if (preg_match("/Unknown column '([a-zA-Z0-9_.]+)'/", $e->getMessage(), $m)) return $m[1];
    return null;
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

    public function toggle(Property $property)
    {
        $property->is_active = !$property->is_active;
        $property->save();
        $msg = $property->is_active ? 'Property enabled — visible on site.' : 'Property disabled — hidden from site.';
        return back()->with('success', $msg);
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return back()->with('success', 'Property removed.');
    }
    
    /**
     * Show assign owner form (modal data)
     */
    // public function showAssignForm(Property $property)
    // {
    //     $owners = \App\Models\User::where('role', 'owner')
    //         ->where('is_active', true)
    //         ->orderBy('name')
    //         ->get(['id', 'name', 'email', 'phone']);

    //     return response()->json([
    //         'property' => [
    //             'id' => $property->id,
    //             'name' => $property->name,
    //             'current_owner_id' => $property->owner_id,
    //             'current_owner' => $property->owner?->name,
    //         ],
    //         'owners' => $owners,
    //     ]);
    // }

    /**
     * Assign property to a different owner
     */
 /**
     * Assign property to a different owner
     */
    public function assignOwner(\Illuminate\Http\Request $request, \App\Models\Property $property)
    {
        $data = $request->validate([
            'owner_id' => 'nullable|exists:users,id',
            'nearby_university_id' => 'nullable|exists:universities,id',
            'university_name' => 'nullable|string|max:200',
            'university_abbreviation' => 'nullable|string|max:20',
        ]);

        $oldOwnerName = $property->owner?->name ?? 'Unassigned';

        // Allow unassigning (null)
        if (empty($data['owner_id'])) {
            $property->update(['owner_id' => null]);
            return back()->with('success', "✓ Property '{$property->name}' is now unassigned (was: {$oldOwnerName})");
        }

        $newOwner = \App\Models\User::where('id', $data['owner_id'])
            ->whereIn('role', ['owner', 'admin'])
            ->first();

        if (!$newOwner) {
            return back()->withErrors(['owner_id' => 'Invalid owner selected.']);
        }

        $property->update(['owner_id' => $newOwner->id]);

        return back()->with('success', "✓ Property '{$property->name}' assigned to {$newOwner->name} (was: {$oldOwnerName})");
    }

    /**
     * Filter properties (admin view): all / my / owner's
     */
    public function adminFilter(Request $request)
    {
        $query = Property::with(['owner', 'city', 'locality']);

        $filter = $request->get('filter', 'all');

        if ($filter === 'my') {
            $query->where('owner_id', auth()->id());
        } elseif ($filter === 'owners') {
            $query->where('owner_id', '!=', auth()->id())
                  ->whereHas('owner', fn($q) => $q->where('role', 'owner'));
        } elseif ($filter === 'unassigned') {
            $query->whereNull('owner_id');
        }

        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $properties = $query->latest()->paginate(20)->withQueryString();

        return view('admin.properties.index', compact('properties', 'filter'));
    }
    
}