<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Lead;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class ManualLeadController extends Controller
{
    /**
     * Show the manual lead entry form.
     * Accessible by admin, telecaller, field_executive.
     */
    public function create()
    {
        $this->authorizeRoles(['admin', 'telecaller', 'field_executive']);

        $cities = City::where('is_active', true)->orderBy('name')->get();
        $properties = Property::active()->orderBy('name')->take(500)->get(['id', 'name']);

        return view('leads.manual-create', compact('cities', 'properties'));
    }

    /**
     * Store the manual lead.
     */
    public function store(Request $request)
    {
        $this->authorizeRoles(['admin', 'telecaller', 'field_executive']);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:15|regex:/^[0-9]{10,15}$/',
            'email' => 'nullable|email|max:160',
            'property_id' => 'nullable|exists:properties,id',
            'preferred_city' => 'nullable|string|max:120',
            'preferred_locality' => 'nullable|string|max:120',
            'preferred_gender' => 'nullable|in:male,female,unisex',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'move_in_date' => 'nullable|date',
            'source' => 'required|in:walk_in,referral,offline_campaign,tele_inbound,manual',
            'message' => 'nullable|string|max:1000',
            'mark_as_verified' => 'nullable|boolean',
        ], [
            'phone.regex' => 'Phone must be 10-15 digits, no spaces or symbols.',
        ]);

        // Duplicate check — same phone within 24 hours
        $duplicate = Lead::where('phone', $data['phone'])
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->first();

        $isDuplicate = false;
        if ($duplicate && !$request->boolean('confirm_duplicate')) {
            return back()
                ->withInput()
                ->with('duplicate', $duplicate)
                ->withErrors([
                    'phone' => "A lead with this phone exists (added {$duplicate->created_at->diffForHumans()} by {$duplicate->createdBy?->name}). Submit again with 'Confirm anyway' to override.",
                ]);
        }

        $user = auth()->user();

        // Lead type — telecaller can mark as verified, others = manual
        $leadType = $user->isTeleCaller() && $request->boolean('mark_as_verified') ? 'verified' : 'manual';

        $data['created_by_user_id'] = $user->id;
        $data['lead_type'] = $leadType;
        $data['status'] = $request->boolean('mark_as_verified') ? 'interested' : 'new';

        // Round-robin assign to least busy telecaller
        // (Skip if creator IS a telecaller — assign to themselves)
        if ($user->isTeleCaller()) {
            $data['assigned_telecaller_id'] = $user->id;
        } else {
            $telecaller = User::where('role', 'telecaller')
                ->where('is_active', true)
                ->withCount('assignedLeads')
                ->orderBy('assigned_leads_count')
                ->first();

            if ($telecaller) {
                $data['assigned_telecaller_id'] = $telecaller->id;
            }
        }

        $lead = Lead::create($data);

        // Increment property lead_count
        if ($lead->property_id) {
            Property::where('id', $lead->property_id)->increment('lead_count');
        }

        // Smart redirect — telecaller goes to leads list, admin to admin leads
        $route = match ($user->role) {
            'admin' => route('admin.leads.index'),
            'telecaller' => route('telecaller.leads.index'),
            default => route('home'),
        };

        return redirect($route)->with('success', "✓ Lead created: {$lead->name} ({$lead->phone}). Lead type: {$leadType}.");
    }

    private function authorizeRoles(array $roles): void
    {
        if (!in_array(auth()->user()->role, $roles)) {
            abort(403);
        }
    }
}