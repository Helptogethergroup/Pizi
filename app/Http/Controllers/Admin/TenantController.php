<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with('property', 'owner', 'documents');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($kyc = $request->get('kyc')) {
            $query->where('kyc_status', $kyc);
        }

        if ($ownerId = $request->get('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%");
            });
        }

        $tenants = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => Tenant::count(),
            'active' => Tenant::where('status', 'active')->count(),
            'pending_kyc' => Tenant::whereIn('kyc_status', ['pending', 'submitted'])->count(),
            'approved_kyc' => Tenant::where('kyc_status', 'approved')->count(),
        ];

        $owners = User::whereIn('role', ['owner', 'admin'])
            ->whereHas('properties', function($q) {
                $q->whereHas('tenants');
            })
            ->orderBy('name')
            ->get();

        return view('admin.tenants.index', compact('tenants', 'stats', 'owners'));
    }

public function show(Tenant $tenant)
    {
        $tenant->load('property', 'owner', 'documents', 'bills', 'complaints');
        $properties = \App\Models\Property::orderBy('name')->get(['id', 'name', 'owner_id']);
        return view('admin.tenants.show', compact('tenant', 'properties'));
    }

    // public function approveKyc(Request $request, Tenant $tenant)
    // {
    //     $data = ['kyc_status' => 'approved'];

    //     // Agar property assign nahi hai, approve ke saath assign karo (step 8 ke liye zaroori)
    //     if (empty($tenant->property_id) && $request->filled('property_id')) {
    //         $property = \App\Models\Property::find($request->property_id);
    //         if ($property) {
    //             $data['property_id'] = $property->id;
    //             $ownerId = $property->user_id ?? null;
    //             if ($ownerId && empty($tenant->owner_id)) {
    //                 $data['owner_id'] = $ownerId;
    //             }
    //         }
    //     }
    //     if ($request->filled('room_number')) $data['room_number'] = $request->room_number;
    //     if ($request->filled('bed_number'))  $data['bed_number']  = $request->bed_number;

    //     $tenant->update($data);

    //     return back()->with('success', '✓ KYC approved' . (isset($data['property_id']) ? ' + room assigned.' : '.'));
    // }

  public function approveKyc(Request $request, Tenant $tenant)
    {
        $data = ['kyc_status' => 'approved'];

        // Agar property abhi tak assign nahi, to approve ke saath assign karo
        if (empty($tenant->property_id) && $request->filled('property_id')) {
            $data['property_id'] = $request->input('property_id');
        }
        if ($request->filled('room_number')) {
            $data['room_number'] = $request->input('room_number');
        }
        if ($request->filled('bed_number')) {
            $data['bed_number'] = $request->input('bed_number');
        }
        if ($request->filled('owner_id')) {
            $data['owner_id'] = $request->input('owner_id');
        }

        $tenant->update($data);

        return back()->with('success', '✓ KYC approved' . (isset($data['property_id']) ? ' + room assigned.' : '.'));
    }

    public function rejectKyc(Request $request, Tenant $tenant)
    {
        $tenant->update([
            'kyc_status' => 'rejected',
            'kyc_remarks' => $request->input('remarks'),
        ]);
        return back()->with('success', '✓ KYC rejected.');
    }

    public function changeStatus(Request $request, Tenant $tenant)
    {
        $request->validate(['status' => 'required|in:active,notice_period,left,blacklisted']);
        $update = ['status' => $request->status];
        if ($request->status === 'left') {
            $update['move_out_date'] = now();
        }
        $tenant->update($update);
        return back()->with('success', '✓ Status updated.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')->with('success', '✓ Tenant deleted.');
    }
}