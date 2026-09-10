<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UniversityPropertyController extends Controller
{
    /**
     * Get all universities with property counts
     */
    public function index()
    {
        $universities = DB::table('universities')
            ->orderBy('city')
            ->orderBy('name')
            ->get();

        // Add property count for each university
        foreach ($universities as $uni) {
            $uni->property_count = $this->getPropertiesNearUniversity($uni->id, onlyCount: true);
        }

        return view('universities.index', compact('universities'));
    }

    /**
     * Get properties near a specific university
     */
    public function show($universityId, Request $request)
    {
        $university = DB::table('universities')->find($universityId);
        if (!$university) {
            abort(404, 'University not found');
        }

        // Get filters from request
        $minBudget = $request->input('min_budget', 0);
        $maxBudget = $request->input('max_budget', 100000);
        $gender = $request->input('gender', null);
        $amenities = $request->input('amenities', []);

        $properties = $this->getPropertiesNearUniversity(
            $universityId,
            minBudget: $minBudget,
            maxBudget: $maxBudget,
            gender: $gender,
            amenities: $amenities
        );

        $allUniversities = DB::table('universities')->orderBy('name')->get();

        return view('universities.show', compact('university', 'properties', 'allUniversities', 'minBudget', 'maxBudget', 'gender'));
    }

    /**
     * API: Filter properties by multiple universities
     */
    public function filterByUniversities(Request $request)
    {
        $request->validate([
            'universities' => 'array',
            'universities.*' => 'integer',
            'min_budget' => 'integer|min:0',
            'max_budget' => 'integer|min:1000',
            'gender' => 'nullable|in:male,female,unisex',
        ]);

        $universitiesIds = $request->input('universities', []);
        $minBudget = $request->input('min_budget', 0);
        $maxBudget = $request->input('max_budget', 100000);
        $gender = $request->input('gender', null);

        if (empty($universitiesIds)) {
            return response()->json(['error' => 'Select at least one university'], 400);
        }

        $allProperties = collect();

        foreach ($universitiesIds as $uniId) {
            $props = $this->getPropertiesNearUniversity(
                $uniId,
                minBudget: $minBudget,
                maxBudget: $maxBudget,
                gender: $gender
            );
            $allProperties = $allProperties->concat($props);
        }

        // Remove duplicates (property might be near multiple universities)
        $unique = $allProperties->unique('id')->values();

        return response()->json([
            'count' => $unique->count(),
            'properties' => $unique,
        ]);
    }

    /**
     * Core logic: Find properties near a university (by nearby_university_id)
     * Simple relationship-based approach
     */
    private function getPropertiesNearUniversity(
        $universityId,
        $minBudget = 0,
        $maxBudget = 100000,
        $gender = null,
        $amenities = [],
        $radiusKm = 3,
        $onlyCount = false
    ) {
        $university = DB::table('universities')->find($universityId);
        if (!$university) {
            return $onlyCount ? 0 : collect();
        }
        
        $properties = DB::table('properties')
            ->where('is_active', true)
            ->where('is_verified', true)
            ->where('nearby_university_id', $universityId)
            ->whereBetween('rent_min', [$minBudget, $maxBudget]);
        
        if ($gender && $gender !== 'unisex') {
            $properties->whereIn('gender', [$gender, 'unisex']);
        }
        
        if (!empty($amenities)) {
            foreach ($amenities as $amenity) {
                $properties->where('amenities', 'like', '%' . $amenity . '%');
            }
        }
        
        if ($onlyCount) {
            return $properties->count();
        }
        
        $results = $properties->orderBy('rent_min')->limit(50)->get();
        
        // Add details + calculate distance
        foreach ($results as $prop) {
            $prop->city_name = DB::table('cities')->where('id', $prop->city_id)->value('name') ?? '';
            $prop->locality_name = DB::table('localities')->where('id', $prop->locality_id)->value('name') ?? '';
            $prop->owner_name = DB::table('users')->where('id', $prop->owner_id)->value('name') ?? 'Owner';
            
            // ✅ Calculate actual distance (Haversine formula)
            if ($prop->latitude && $prop->longitude && $university->latitude && $university->longitude) {
                $lat1 = deg2rad($university->latitude);
                $lat2 = deg2rad($prop->latitude);
                $lon1 = deg2rad($university->longitude);
                $lon2 = deg2rad($prop->longitude);
                
                $dLat = $lat2 - $lat1;
                $dLon = $lon2 - $lon1;
                
                $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLon / 2) * sin($dLon / 2);
                $c = 2 * asin(sqrt($a));
                $km = 6371 * $c; // Earth radius in km
                
                $prop->distance_km = round($km, 1);
            } else {
                $prop->distance_km = null;
            }
        }
        
        return $results;
    }
}
