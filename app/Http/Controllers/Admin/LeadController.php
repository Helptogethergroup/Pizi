<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $q = Lead::with(['property', 'telecaller']);

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                   ->orWhere('phone', 'like', $term)
                   ->orWhere('email', 'like', $term);
            });
        }

        $leads = $q->latest()->paginate(25)->withQueryString();
        $telecallers = User::where('role', 'telecaller')->where('is_active', true)->get();
        return view('admin.leads', compact('leads', 'telecallers'));
    }

    public function assign(Request $request, Lead $lead)
    {
        $request->validate([
            'telecaller_id' => 'required|exists:users,id',
        ]);
        $lead->update(['assigned_telecaller_id' => $request->telecaller_id]);
        return back()->with('success', 'Lead assigned.');
    }
}
