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
        $properties = Property::where('owner_id', auth()->id())
            ->with(['city', 'locality'])
            ->latest()->paginate(15);
        return view('owner.properties.index', compact('properties'));
    }

    public function create()
    {
        $cities = City::where('is_active', true)->orderBy('name')->get();
        $localities = Locality::where('is_active', true)->orderBy('name')->get();
        $amenities = Amenity::orderBy('name')->get();
        return view('owner.properties.create', compact('cities', 'localities', 'amenities'));
    }

    public function store(Request $request)
    {
        $data = $this->validateProperty($request);
        $data['owner_id'] = auth()->id();

        // Handle cover image
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')
                ->store('properties/covers', 'public');
        }

        $sharing = [];
        foreach (['single', 'double', 'triple'] as $type) {
            if ($request->filled("sharing_$type")) {
                $sharing[$type] = (float) $request->input("sharing_$type");
            }
        }
        $data['sharing_options'] = $sharing;

        $property = Property::create($data);

        if ($request->filled('amenities')) {
            $property->amenities()->sync($request->amenities);
        }

        // Additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $img) {
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $img->store('properties/gallery', 'public'),
                    'display_order' => $i,
                ]);
            }
        }

        return redirect()->route('owner.properties.index')
            ->with('success', 'Property listed successfully! It will go live after admin verification.');
    }

    public function edit(Property $property)
    {
        $this->authorizeOwner($property);
        $cities = City::where('is_active', true)->orderBy('name')->get();
        $localities = Locality::where('is_active', true)->orderBy('name')->get();
        $amenities = Amenity::orderBy('name')->get();
        return view('owner.properties.edit', compact('property', 'cities', 'localities', 'amenities'));
    }

    public function update(Request $request, Property $property)
    {
        $this->authorizeOwner($property);

        $data = $this->validateProperty($request);

        if ($request->hasFile('cover_image')) {
            if ($property->cover_image && !str_starts_with($property->cover_image, 'http')) {
                Storage::disk('public')->delete($property->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')
                ->store('properties/covers', 'public');
        }

        $sharing = [];
        foreach (['single', 'double', 'triple'] as $type) {
            if ($request->filled("sharing_$type")) {
                $sharing[$type] = (float) $request->input("sharing_$type");
            }
        }
        $data['sharing_options'] = $sharing;

        $property->update($data);

        if ($request->has('amenities')) {
            $property->amenities()->sync($request->amenities ?? []);
        }

        if ($request->hasFile('images')) {
            $start = $property->images()->max('display_order') ?? 0;
            foreach ($request->file('images') as $i => $img) {
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $img->store('properties/gallery', 'public'),
                    'display_order' => $start + 1 + $i,
                ]);
            }
        }

        return redirect()->route('owner.properties.index')
            ->with('success', 'Property updated.');
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
        $property->delete();
        return back()->with('success', 'Property removed.');
    }

    private function authorizeOwner(Property $property): void
    {
        if ($property->owner_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    private function validateProperty(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'rules' => 'nullable|string',
            'city_id' => 'required|exists:cities,id',
            'locality_id' => 'required|exists:localities,id',
            'gender' => 'required|in:male,female,unisex',
            'property_type' => 'required|in:pg,hostel,coliving,flatmate',
            'rent_min' => 'required|numeric|min:0',
            'rent_max' => 'required|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'food_included' => 'nullable|boolean',
            'address_line' => 'required|string|max:255',
            'landmark' => 'nullable|string|max:120',
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
