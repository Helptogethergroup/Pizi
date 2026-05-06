<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => 'nullable|exists:properties,id',
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:15',
            'email' => 'nullable|email|max:160',
            'preferred_locality' => 'nullable|string|max:120',
            'preferred_city' => 'nullable|string|max:120',
            'preferred_gender' => 'nullable|in:male,female,unisex',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'move_in_date' => 'nullable|date',
            'message' => 'nullable|string|max:1000',
        ]);

        // De-dupe: Same phone + same property within last 24 hours = one lead, not many.
        $duplicate = Lead::where('phone', $data['phone'])
            ->where('property_id', $data['property_id'] ?? null)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($duplicate) {
            return $this->respondSuccess($request, 'We already received your inquiry. Our team will call you shortly.');
        }

        $data['source'] = 'website';
        $data['status'] = 'new';

        // Round-robin assign to active tele-callers
        $telecaller = User::where('role', 'telecaller')
            ->where('is_active', true)
            ->withCount('assignedLeads')
            ->orderBy('assigned_leads_count')
            ->first();

        if ($telecaller) {
            $data['assigned_telecaller_id'] = $telecaller->id;
        }

        $lead = Lead::create($data);

        if ($lead->property_id) {
            Property::where('id', $lead->property_id)->increment('lead_count');
        }

        return $this->respondSuccess($request, 'Thanks! Our team will reach out within 30 minutes.');
    }

    private function respondSuccess(Request $request, string $message)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }
}