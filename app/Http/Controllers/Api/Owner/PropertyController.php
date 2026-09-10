<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = DB::table('properties as p')
            ->leftJoin('cities as c', 'p.city_id', '=', 'c.id')
            ->leftJoin('localities as l', 'p.locality_id', '=', 'l.id')
            ->select('p.*', 'c.name as city_name', 'l.name as locality_name')
            ->whereNull('p.deleted_at');

        if ($user->role !== 'admin') {
            $query->where('p.owner_id', $user->id);
        }

        $properties = $query->orderBy('p.created_at', 'desc')->get();
        $ids = $properties->pluck('id')->toArray();

        $images = DB::table('property_images')->whereIn('property_id', $ids)->orderBy('display_order')->get()->groupBy('property_id');
        $properties = $properties->map(function ($p) use ($images) {
            $p->images = $images->get($p->id, collect([]));
            return $p;
        });

        return response()->json(['success' => true, 'data' => $properties]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'locality_id' => 'required|integer|exists:localities,id',
            'address_line' => 'required|string',
            'property_type' => 'required|in:pg,hostel,coliving,flatmate',
            'gender' => 'required|in:male,female,unisex',
            'rent_min' => 'required|numeric|min:0',
            'rent_max' => 'required|numeric|min:0',
            'security_deposit' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|nullable|string',
            'rules' => 'sometimes|nullable|string',
            'pincode' => 'sometimes|nullable|string',
            'amenity_ids' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $slug = Str::slug($request->name) . '-' . uniqid();
        $id = DB::table('properties')->insertGetId([
            'owner_id' => $user->id,
            'city_id' => $request->city_id,
            'locality_id' => $request->locality_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'rules' => $request->rules,
            'gender' => $request->gender,
            'property_type' => $request->property_type,
            'rent_min' => $request->rent_min,
            'rent_max' => $request->rent_max,
            'security_deposit' => $request->security_deposit ?? 0,
            'address_line' => $request->address_line,
            'pincode' => $request->pincode,
            'is_active' => true,
            'is_verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->amenity_ids) {
            foreach ($request->amenity_ids as $amenityId) {
                DB::table('property_amenities')->insert(['property_id' => $id, 'amenity_id' => $amenityId]);
            }
        }

        return response()->json(['success' => true, 'data' => ['id' => $id, 'slug' => $slug]]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = DB::table('properties as p')
            ->leftJoin('cities as c', 'p.city_id', '=', 'c.id')
            ->leftJoin('localities as l', 'p.locality_id', '=', 'l.id')
            ->where('p.id', $id)
            ->select('p.*', 'c.name as city_name', 'l.name as locality_name');

        if ($user->role !== 'admin') {
            $query->where('p.owner_id', $user->id);
        }

        $property = $query->first();
        if (!$property) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $property->images = DB::table('property_images')->where('property_id', $id)->orderBy('display_order')->get();
        $property->amenities = DB::table('property_amenities as pa')
            ->join('amenities as a', 'pa.amenity_id', '=', 'a.id')
            ->where('pa.property_id', $id)
            ->get();

        return response()->json(['success' => true, 'data' => $property]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $property = DB::table('properties')->where('id', $id)->first();
        if (!$property) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        if ($user->role !== 'admin' && $property->owner_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $data = $request->only(['name', 'description', 'rules', 'gender', 'property_type', 'rent_min', 'rent_max', 'security_deposit', 'address_line', 'pincode', 'food_included', 'is_active']);
        $data['updated_at'] = now();
        DB::table('properties')->where('id', $id)->update($data);

        if ($request->amenity_ids !== null) {
            DB::table('property_amenities')->where('property_id', $id)->delete();
            foreach ($request->amenity_ids as $amenityId) {
                DB::table('property_amenities')->insert(['property_id' => $id, 'amenity_id' => $amenityId]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $property = DB::table('properties')->where('id', $id)->first();
        if (!$property) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        if ($user->role !== 'admin' && $property->owner_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        DB::table('properties')->where('id', $id)->update(['deleted_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function uploadImages(Request $request, $id)
    {
        $user = $request->user();
        $property = DB::table('properties')->where('id', $id)->first();
        if (!$property) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        if ($user->role !== 'admin' && $property->owner_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $uploaded = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('properties/gallery', 'public');
                $imgId = DB::table('property_images')->insertGetId([
                    'property_id' => $id,
                    'image_path' => $path,
                    'display_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $uploaded[] = ['id' => $imgId, 'url' => url('storage/' . $path)];
            }
        }

        return response()->json(['success' => true, 'data' => $uploaded]);
    }

    public function deleteImage(Request $request, $imageId)
    {
        $img = DB::table('property_images')->where('id', $imageId)->first();
        if (!$img) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $property = DB::table('properties')->where('id', $img->property_id)->first();
        $user = $request->user();
        if ($user->role !== 'admin' && $property->owner_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        Storage::disk('public')->delete($img->image_path);
        DB::table('property_images')->where('id', $imageId)->delete();
        return response()->json(['success' => true]);
    }
}
