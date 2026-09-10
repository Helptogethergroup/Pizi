<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with('property', 'owner', 'beds');

        if ($ownerId = $request->get('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        if ($propertyId = $request->get('property_id')) {
            $query->where('property_id', $propertyId);
        }

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                  ->orWhereHas('property', fn($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $rooms = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total_rooms' => Room::count(),
            'total_beds' => Bed::count(),
            'occupied' => Bed::where('status', 'occupied')->count(),
            'vacant' => Bed::where('status', 'vacant')->count(),
        ];

        $owners = User::whereIn('role', ['owner', 'admin'])
            ->whereHas('properties.rooms')
            ->orderBy('name')
            ->get();

        $properties = Property::whereHas('rooms')->orderBy('name')->get();

        return view('admin.rooms.index', compact('rooms', 'stats', 'owners', 'properties'));
    }

    public function show(Room $room)
    {
        $room->load('property', 'owner', 'beds.tenant');
        return view('admin.rooms.show', compact('room'));
    }

    public function destroy(Room $room)
    {
        $room->beds()->delete();
        $room->delete();
        return redirect()->route('admin.rooms.index')->with('success', '✓ Room deleted.');
    }
}