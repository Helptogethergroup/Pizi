<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $q = Property::with(['owner', 'city', 'locality']);

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where('name', 'like', $term);
        }

        if ($request->filled('verified')) {
            $q->where('is_verified', (bool) $request->verified);
        }

        $properties = $q->latest()->paginate(20)->withQueryString();
        return view('admin.properties', compact('properties'));
    }

    public function verify(Property $property)
    {
        $property->is_verified = !$property->is_verified;
        if ($property->is_verified) $property->is_active = true;
        $property->save();
        return back()->with('success', 'Verification status updated.');
    }

    public function feature(Property $property)
    {
        $property->is_featured = !$property->is_featured;
        $property->save();
        return back()->with('success', 'Featured status updated.');
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return back()->with('success', 'Property removed.');
    }
}
