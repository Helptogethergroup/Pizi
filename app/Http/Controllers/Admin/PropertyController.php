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

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $q->where('is_active', true);
            } elseif ($request->status === 'disabled') {
                $q->where('is_active', false);
            }
        }

        $properties = $q->latest()->paginate(20)->withQueryString();

        // Stats for dashboard tiles
        $stats = [
            'total' => Property::count(),
            'active' => Property::where('is_active', true)->count(),
            'pending' => Property::where('is_verified', false)->count(),
            'featured' => Property::where('is_featured', true)->count(),
        ];

        return view('admin.properties', compact('properties', 'stats'));
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
}