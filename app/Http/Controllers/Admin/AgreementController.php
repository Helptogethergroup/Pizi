<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentAgreement;
use App\Models\User;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $query = RentAgreement::with('tenant', 'property', 'owner');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($ownerId = $request->get('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('agreement_number', 'like', "%{$search}%")
                  ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        $agreements = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => RentAgreement::count(),
            'active' => RentAgreement::where('status', 'active')->count(),
            'expiring' => RentAgreement::where('status', 'active')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>', now())->count(),
            'draft' => RentAgreement::where('status', 'draft')->count(),
        ];

        $owners = User::whereIn('role', ['owner', 'admin'])
            ->whereHas('rentAgreements')
            ->orderBy('name')
            ->get();

        return view('admin.agreements.index', compact('agreements', 'stats', 'owners'));
    }

    public function show(RentAgreement $agreement)
    {
        $agreement->load('tenant', 'property', 'owner', 'signatures');
        return view('admin.agreements.show', compact('agreement'));
    }

    public function preview(RentAgreement $agreement)
    {
        $agreement->load('tenant', 'property', 'owner', 'signatures');
        return view('owner.agreements.preview', compact('agreement'));
    }

    public function destroy(RentAgreement $agreement)
    {
        $agreement->signatures()->delete();
        $agreement->delete();
        return redirect()->route('admin.agreements.index')->with('success', '✓ Deleted.');
    }
}