<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PgManagerController extends Controller
{
    // Features an owner can grant to a PG Manager. "PG Managers" (creating other
    // managers) and Wallet/Buy Credits are intentionally NEVER offered here.
   const AVAILABLE_FEATURES = [
    'analytics'  => 'Analytics',
    'properties' => 'My Properties (view + edit)',
    'tenants'    => 'My Tenants',
    'rent'       => 'Rent Collection',
    'complaints' => 'Complaints',
    'rooms'      => 'Rooms & Beds',
    'agreements' => 'Agreements',
    'leads'      => 'Leads',
];

    public function index()
    {
        $managers = User::where('role', 'pg_manager')
            ->where('owner_id', auth()->id())
            ->get()
            ->map(function ($manager) {
                $ids = DB::table('property_managers')->where('manager_id', $manager->id)->pluck('property_id');
                $manager->property_ids = $ids;
                $manager->property_names = Property::whereIn('id', $ids)->pluck('name');
                $manager->feature_list = json_decode($manager->permissions ?? '[]', true) ?: [];
                return $manager;
            });

        $myProperties = Property::where('owner_id', auth()->id())->orderBy('name')->get();
        $availableFeatures = self::AVAILABLE_FEATURES;

        return view('owner.pg-managers.index', compact('managers', 'myProperties', 'availableFeatures'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'password' => 'required|string|min:6',
            'property_ids' => 'required|array|min:1',
            'property_ids.*' => 'exists:properties,id',
            'features' => 'nullable|array',
            'features.*' => 'in:' . implode(',', array_keys(self::AVAILABLE_FEATURES)),
        ]);

        // Ensure all selected properties actually belong to this owner
        $ownedPropertyIds = Property::where('owner_id', auth()->id())
            ->whereIn('id', $data['property_ids'])
            ->pluck('id');

        if ($ownedPropertyIds->isEmpty()) {
            return back()->with('error', 'Please select at least one valid property.');
        }

        $manager = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'pg_manager',
            'owner_id' => auth()->id(),
            'permissions' => json_encode($data['features'] ?? []),
            'is_active' => 1,
        ]);

        foreach ($ownedPropertyIds as $propertyId) {
            DB::table('property_managers')->insert([
                'manager_id' => $manager->id,
                'property_id' => $propertyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', "✓ PG Manager '{$manager->name}' created and assigned to " . count($ownedPropertyIds) . " propert(y/ies).");
    }

    public function updateProperties(Request $request, User $manager)
    {
        if ($manager->owner_id !== auth()->id() || $manager->role !== 'pg_manager') {
            abort(403);
        }

        $data = $request->validate([
            'property_ids' => 'array',
            'property_ids.*' => 'exists:properties,id',
            'features' => 'nullable|array',
            'features.*' => 'in:' . implode(',', array_keys(self::AVAILABLE_FEATURES)),
        ]);

        $ownedPropertyIds = Property::where('owner_id', auth()->id())
            ->whereIn('id', $data['property_ids'] ?? [])
            ->pluck('id');

        DB::table('property_managers')->where('manager_id', $manager->id)->delete();

        foreach ($ownedPropertyIds as $propertyId) {
            DB::table('property_managers')->insert([
                'manager_id' => $manager->id,
                'property_id' => $propertyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $manager->update(['permissions' => json_encode($data['features'] ?? [])]);

        return back()->with('success', 'Manager access updated.');
    }

    public function toggle(User $manager)
    {
        if ($manager->owner_id !== auth()->id() || $manager->role !== 'pg_manager') {
            abort(403);
        }

        $manager->is_active = !$manager->is_active;
        $manager->save();

        return back()->with('success', 'Manager status updated.');
    }

    public function destroy(User $manager)
    {
        if ($manager->owner_id !== auth()->id() || $manager->role !== 'pg_manager') {
            abort(403);
        }

        DB::table('property_managers')->where('manager_id', $manager->id)->delete();
        $manager->delete();

        return back()->with('success', 'PG Manager removed.');
    }
}