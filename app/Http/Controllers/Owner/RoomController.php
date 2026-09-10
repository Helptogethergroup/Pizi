<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $this->checkAccess();

        $managedIds = auth()->user()->getManagedPropertyIds();

        $properties = Property::whereIn('id', $managedIds)
            ->withCount(['rooms', 'beds'])
            ->orderBy('name')
            ->get();

        $selectedPropertyId = $request->get('property_id', $properties->first()?->id);

        $rooms = collect();
        $selectedProperty = null;
        $stats = ['total_rooms' => 0, 'total_beds' => 0, 'occupied' => 0, 'vacant' => 0];

        if ($selectedPropertyId) {
            $selectedProperty = Property::where('id', $selectedPropertyId)
                ->whereIn('id', $managedIds)
                ->first();

            if ($selectedProperty) {
                $rooms = Room::where('property_id', $selectedProperty->id)
                    ->with(['beds.tenant', 'amenities'])
                    ->orderBy('floor')
                    ->orderBy('room_number')
                    ->get();

                $stats = [
                    'total_rooms' => $rooms->count(),
                    'total_beds' => $rooms->sum(fn($r) => $r->beds->count()),
                    'occupied' => $rooms->sum(fn($r) => $r->beds->where('status', 'occupied')->count()),
                    'vacant' => $rooms->sum(fn($r) => $r->beds->where('status', 'vacant')->count()),
                ];
            }
        }

        return view('owner.rooms.index', compact('properties', 'selectedProperty', 'rooms', 'stats'));
    }

    public function create(Request $request)
    {
        $this->checkAccess();

        $properties = Property::whereIn('id', auth()->user()->getManagedPropertyIds())->orderBy('name')->get();
        $selectedPropertyId = $request->get('property_id');
        $amenities = \App\Models\Amenity::orderBy('name')->get();

        return view('owner.rooms.create', compact('properties', 'selectedPropertyId', 'amenities'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:50',
            'room_type' => 'required|in:single,double,triple,quad,quint,dorm',
            'gender' => 'required|in:male,female,unisex',
            'monthly_rent' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'has_ac' => 'nullable|boolean',
            'has_attached_bathroom' => 'nullable|boolean',
            'has_balcony' => 'nullable|boolean',
            'has_geyser' => 'nullable|boolean',
            'has_wifi' => 'nullable|boolean',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
            'notes' => 'nullable|string',
            'auto_create_beds' => 'nullable|boolean',
        ]);

        $property = Property::where('id', $data['property_id'])
            ->whereIn('id', auth()->user()->getManagedPropertyIds())
            ->firstOrFail();

        // Check duplicate
        $exists = Room::where('property_id', $property->id)
            ->where('room_number', $data['room_number'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['room_number' => 'Room already exists.'])->withInput();
        }

        // Room/Bed records belong to the actual property owner, not the manager
        $data['owner_id'] = $this->effectiveOwnerId();
        foreach (['has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi'] as $f) {
            $data[$f] = $request->boolean($f);
        }
        $data['status'] = 'active';

        $autoCreate = $request->boolean('auto_create_beds');
        unset($data['auto_create_beds']);

        $amenityIds = $data['amenities'] ?? [];
        unset($data['amenities']);

        $room = Room::create($data);
        $room->amenities()->sync($amenityIds);

        // Auto create beds based on room type
        if ($autoCreate) {
            $capacity = $room->capacity;
            $bedRent = $room->monthly_rent > 0 ? round($room->monthly_rent / $capacity) : 0;

            for ($i = 1; $i <= $capacity; $i++) {
                Bed::create([
                    'room_id' => $room->id,
                    'property_id' => $property->id,
                    'owner_id' => $this->effectiveOwnerId(),
                    'bed_number' => chr(64 + $i), // A, B, C, D...
                    'bed_type' => 'single',
                    'monthly_rent' => $bedRent,
                    'status' => 'vacant',
                ]);
            }
        }

        return redirect()->route('owner.rooms.show', $room)
            ->with('success', '✓ Room added' . ($autoCreate ? ' with ' . $room->capacity . ' beds' : '') . '.');
    }

    public function show(Room $room)
    {
        $this->authorize_owner($room);
        $room->load('beds.tenant', 'property');

        $activeTenants = Tenant::where('owner_id', $this->effectiveOwnerId())
            ->where('property_id', $room->property_id)
            ->where('status', 'active')
            ->whereNotIn('id', $room->beds->pluck('tenant_id')->filter())
            ->orderBy('name')
            ->get();

        return view('owner.rooms.show', compact('room', 'activeTenants'));
    }

    public function edit(Room $room)
    {
        $this->authorize_owner($room);
        $properties = Property::whereIn('id', auth()->user()->getManagedPropertyIds())->orderBy('name')->get();
        $amenities = \App\Models\Amenity::orderBy('name')->get();
        $room->load('amenities');
        return view('owner.rooms.edit', compact('room', 'properties', 'amenities'));
    }

    public function update(Request $request, Room $room)
    {
        $this->authorize_owner($room);

        $data = $request->validate([
            'room_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:50',
            'room_type' => 'required|in:single,double,triple,quad,quint,dorm',
            'gender' => 'required|in:male,female,unisex',
            'monthly_rent' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'has_ac' => 'nullable|boolean',
            'has_attached_bathroom' => 'nullable|boolean',
            'has_balcony' => 'nullable|boolean',
            'has_geyser' => 'nullable|boolean',
            'has_wifi' => 'nullable|boolean',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,maintenance,closed',
        ]);

        foreach (['has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi'] as $f) {
            $data[$f] = $request->boolean($f);
        }

        $amenityIds = $data['amenities'] ?? [];
        unset($data['amenities']);

        $room->update($data);
        $room->amenities()->sync($amenityIds);

        return redirect()->route('owner.rooms.show', $room)->with('success', '✓ Room updated.');
    }

    public function destroy(Room $room)
    {
        $this->authorize_owner($room);

        if ($room->beds->where('status', 'occupied')->count() > 0) {
            return back()->withErrors(['delete' => 'Cannot delete: room has occupied beds.']);
        }

        $room->beds()->delete();
        $room->delete();
        return redirect()->route('owner.rooms.index')->with('success', '✓ Room deleted.');
    }

    /**
     * Bulk delete — select multiple rooms and remove them in one go.
     * Rooms with any occupied bed are skipped (same rule as single delete)
     * so an owner can't accidentally remove a room a tenant is living in.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['room_ids' => 'required|array', 'room_ids.*' => 'integer']);

        $managedIds = auth()->user()->getManagedPropertyIds();
        $rooms = Room::whereIn('id', $request->room_ids)
            ->whereIn('property_id', $managedIds)
            ->with('beds')
            ->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($rooms as $room) {
            if ($room->beds->where('status', 'occupied')->count() > 0) {
                $skipped++;
                continue;
            }
            $room->beds()->delete();
            $room->delete();
            $deleted++;
        }

        $message = "✓ {$deleted} room(s) deleted.";
        if ($skipped > 0) {
            $message .= " {$skipped} room(s) skipped — they have occupied beds.";
        }

        return back()->with($skipped > 0 ? 'warning' : 'success', $message);
    }

    // BED METHODS
    public function storeBed(Request $request, Room $room)
    {
        $this->authorize_owner($room);

        $data = $request->validate([
            'bed_number' => 'required|string|max:20',
            'bed_type' => 'required|in:single,bunk_top,bunk_bottom,double',
            'monthly_rent' => 'nullable|numeric|min:0',
        ]);

        $exists = Bed::where('room_id', $room->id)
            ->where('bed_number', $data['bed_number'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['bed_number' => 'Bed already exists.']);
        }

        $data['room_id'] = $room->id;
        $data['property_id'] = $room->property_id;
        $data['owner_id'] = $this->effectiveOwnerId();
        $data['status'] = 'vacant';

        Bed::create($data);
        return back()->with('success', '✓ Bed added.');
    }

    public function assignBed(Request $request, Bed $bed)
    {
        $room = $bed->room;
        $this->authorize_owner($room);

        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $tenant = Tenant::where('id', $data['tenant_id'])
            ->where('owner_id', $this->effectiveOwnerId())
            ->firstOrFail();

        $bed->update([
            'tenant_id' => $tenant->id,
            'status' => 'occupied',
            'occupied_since' => now(),
        ]);

        // Update tenant
        $tenant->update([
            'room_number' => $room->room_number,
            'bed_number' => $bed->bed_number,
            'property_id' => $room->property_id,
        ]);

        return back()->with('success', '✓ Bed assigned to ' . $tenant->name);
    }

    public function unassignBed(Bed $bed)
    {
        $room = $bed->room;
        $this->authorize_owner($room);

        $bed->update([
            'tenant_id' => null,
            'status' => 'vacant',
            'occupied_since' => null,
        ]);

        return back()->with('success', '✓ Bed unassigned.');
    }

    public function changeBedStatus(Request $request, Bed $bed)
    {
        $room = $bed->room;
        $this->authorize_owner($room);

        $request->validate([
            'status' => 'required|in:vacant,reserved,maintenance',
        ]);

        $update = ['status' => $request->status];
        if ($request->status === 'vacant') {
            $update['tenant_id'] = null;
            $update['occupied_since'] = null;
        }

        $bed->update($update);
        return back()->with('success', '✓ Bed status updated.');
    }

    public function destroyBed(Bed $bed)
    {
        $room = $bed->room;
        $this->authorize_owner($room);

        if ($bed->status === 'occupied') {
            return back()->withErrors(['delete' => 'Cannot delete occupied bed.']);
        }

        $bed->delete();
        return back()->with('success', '✓ Bed removed.');
    }

    /**
     * Trash — soft-deleted rooms (owner can restore within 30 days)
     */
    public function trash(Request $request)
    {
        $this->checkAccess();

        $managedIds = auth()->user()->getManagedPropertyIds();
        $selectedPropertyId = $request->get('property_id');

        $query = Room::onlyTrashed()->whereIn('property_id', $managedIds);
        if ($selectedPropertyId) {
            $query->where('property_id', $selectedPropertyId);
        }

        $trashedRooms = $query->with('property')->orderByDesc('deleted_at')->get();
        $properties = Property::whereIn('id', $managedIds)->orderBy('name')->get();

        return view('owner.rooms.trash', compact('trashedRooms', 'properties', 'selectedPropertyId'));
    }

    /**
     * Restore a soft-deleted room + its beds
     */
    public function restore($roomId)
    {
        $this->checkAccess();

        $room = Room::onlyTrashed()->findOrFail($roomId);

        if (!auth()->user()->getManagedPropertyIds()->contains($room->property_id)) {
            abort(403);
        }

        $room->restore();
        Bed::onlyTrashed()->where('room_id', $room->id)->restore();

        return redirect()->route('owner.rooms.show', $room)->with('success', '✓ Room restored.');
    }

    /**
     * Permanently delete a room from trash (cannot be undone)
     */
    public function forceDelete($roomId)
    {
        $this->checkAccess();

        $room = Room::onlyTrashed()->findOrFail($roomId);

        if (!auth()->user()->getManagedPropertyIds()->contains($room->property_id)) {
            abort(403);
        }

        Bed::onlyTrashed()->where('room_id', $room->id)->forceDelete();
        $room->forceDelete();

        return redirect()->route('owner.rooms.trash')->with('success', '✓ Room permanently deleted.');
    }

    /**
     * A room's/bed's owner_id must always be the real property owner —
     * never the PG Manager's own user id.
     */
    private function effectiveOwnerId()
    {
        $user = auth()->user();
        return $user->role === 'pg_manager' ? $user->owner_id : $user->id;
    }

    /**
     * Blocks access entirely if this PG Manager wasn't granted the
     * "Rooms & Beds" feature by their owner.
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasFeature('rooms')) {
            abort(403, 'You do not have access to Rooms & Beds.');
        }
    }

    private function authorize_owner(Room $room): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $this->checkAccess();

        if (!auth()->user()->getManagedPropertyIds()->contains($room->property_id)) {
            abort(403);
        }
    }
}