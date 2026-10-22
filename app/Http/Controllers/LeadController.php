<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Property;
use App\Models\User;
use App\Notifications\NewLeadMatched;
use App\Services\LeadMatchingService;
use App\Services\LeadQualityService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'source' => 'nullable|string|max:50',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // A phone number that cannot be real is sent back to the visitor, who may
        // just have made a typo. A real number is stored as 10 plain digits.
        $quality = app(LeadQualityService::class);
        $normalizedPhone = $quality->normalizePhone($data['phone']);
        if ($normalizedPhone === null || $quality->looksFakePhone($normalizedPhone)) {
            throw ValidationException::withMessages([
                'phone' => 'Please enter a valid 10-digit mobile number.',
            ]);
        }
        $data['phone'] = $normalizedPhone;

        // Auto-attach user_id if user is logged in (overrides client input)
       // Agar login nahi hai par phone kisi tenant user se match karta hai, to link kar do
        if (!Auth::check() && !empty($data['phone'])) {
            $last10 = substr(preg_replace('/[^0-9]/', '', $data['phone']), -10);
            $matchUser = User::whereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10])
                ->where('role', 'tenant')
                ->first();
            if ($matchUser) {
                $data['user_id'] = $matchUser->id;
            }
        }

        // De-dupe: the same person asking about the same PG within 30 days is one lead,
        // so one enquiry is never sold to two owners.
        $duplicate = $quality->recentDuplicate($data['phone'], $data['property_id'] ?? null, 30);

        if ($duplicate) {
            // Even if duplicate, link user_id if missing
            if (Auth::check() && empty($duplicate->user_id)) {
                $duplicate->update(['user_id' => Auth::id()]);
            }
            return $this->respondSuccess($request, 'We already received your inquiry. Our team will call you shortly.');
        }

        $allowedSources = [
    'website',
    'whatsapp',
    'meta_ads',
    'google_ads'
      ];

     $data['source'] = in_array($data['source'] ?? '', $allowedSources)
    ? $data['source']
    : 'website';

        // Every enquiry submitted from a property page is inherently
        // someone wanting to RENT that PG — never ambiguous.
        $data['inquiry_type'] = 'tenant';

        // The property itself already tells us the city — no need to ask
        // the visitor again. This alone fixes most "city unknown" leads,
        // since property-page enquiries are the biggest tenant-lead source.
        if (empty($data['preferred_city']) && !empty($data['property_id'])) {
            $propertyCityName = Property::with('city')->find($data['property_id'])?->city?->name;
            if ($propertyCityName) {
                $data['preferred_city'] = $propertyCityName;
            }
        }

        // Round-robin assign to active tele-callers who handle this lead's
        // type (tenant/owner) — falls back to any active telecaller if none
        // are specifically specialized, so a lead never goes unassigned.
        $telecaller = User::where('role', 'telecaller')
            ->where('is_active', true)
            ->whereIn('lead_specialization', ['both', $data['inquiry_type']])
            ->withCount('assignedLeads')
            ->orderBy('assigned_leads_count')
            ->first()
            ?? User::where('role', 'telecaller')
                ->where('is_active', true)
                ->withCount('assignedLeads')
                ->orderBy('assigned_leads_count')
                ->first();

        if ($telecaller) {
            $data['assigned_telecaller_id'] = $telecaller->id;
        }

        $junkReason = $quality->contentProblem($data['name'], $data['message'] ?? null);
        if ($junkReason) {
            Lead::create($data + [
                'status' => 'junk',
                'notes' => 'Auto-rejected: ' . $quality->describe($junkReason),
            ]);
            // the visitor sees the usual thank-you, so a bot learns nothing
            return $this->respondSuccess($request, 'Thanks! Our team will reach out within 30 minutes.');
        }

        // no telecaller is picked for a lead nobody will see
        $lead = Lead::create($data);

        if ($lead->property_id) {
            Property::where('id', $lead->property_id)->increment('lead_count');

            // Enquiry was on a SPECIFIC PG's page — notify that exact
            // property's owner directly on WhatsApp (not just the generic
            // "top-3 matched owners" scoring below, which may not even
            // include this owner if their score isn't highest).
            try {
                $property = Property::with('owner')->find($lead->property_id);
                if ($property?->owner?->phone) {
                    // Numbered params matching new_lead_alert_v2: {{1}} {{2}} {{3}} {{4}}
                    // No phone in the message text — owner sees it on the dashboard.
                    app(\App\Services\WhatsAppService::class)->sendTemplate(
                        $property->owner->phone,
                        'new_lead_alert',
                        [
                            $property->owner->name,
                            $property->name,
                            $lead->name,
                            $lead->budget_max ? ('₹' . number_format($lead->budget_max, 0)) : 'Not specified',
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Log::warning('New lead WhatsApp alert failed: ' . $e->getMessage());
            }
        }

        // Notify the auto-assigned telecaller
        if (!empty($data['assigned_telecaller_id'])) {
            try {
                $telecaller?->notify(new \App\Notifications\NewLeadAssigned($lead));
            } catch (\Exception $e) {
                \Log::warning('Lead assignment notification failed: ' . $e->getMessage());
            }
        }

        // Flag high-value leads (₹15,000+ budget) for admin attention
        if (($lead->budget_max ?? 0) >= 15000) {
            try {
                User::where('role', 'admin')->get()->each(
                    fn ($admin) => $admin->notify(new \App\Notifications\HighValueLead($lead))
                );
            } catch (\Exception $e) {
                \Log::warning('High-value lead notification failed: ' . $e->getMessage());
            }
        }

        // Notify top-3 matched owners
        try {
            $matcher = app(LeadMatchingService::class);
            $matchedOwners = User::where('role', 'owner')
                ->where('is_active', true)
                ->whereHas('properties', fn ($q) => $q->where('is_active', true))
                ->with('properties')
                ->get()
                ->map(function ($owner) use ($matcher, $lead) {
                    $bestScore = 0;
                    foreach ($owner->properties as $prop) {
                        $s = $matcher->score($lead, $prop);
                        if ($s > $bestScore) $bestScore = $s;
                    }
                    $owner->best_score = $bestScore;
                    return $owner;
                })
                ->filter(fn ($o) => $o->best_score >= 50)
                ->sortByDesc('best_score')
                ->take(3);

            foreach ($matchedOwners as $owner) {
                $owner->notify(new NewLeadMatched($lead, $owner->best_score));
            }
        } catch (\Exception $e) {
            \Log::warning('Lead notification failed: ' . $e->getMessage());
        }

        // Special handling for logged-in tenants
        if (Auth::check() && Auth::user()->role === 'tenant') {
            return $this->respondTenantSuccess($request, $lead);
        }

        return $this->respondSuccess($request, 'Thanks! Our team will reach out within 30 minutes.');
    }

    private function respondTenantSuccess(Request $request, Lead $lead)
    {
        $message = '🎉 Inquiry submitted! Your Step 2 is now complete. Our telecaller will call you shortly.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect' => route('tenant.onboarding'),
                'journey_advanced' => true,
            ]);
        }

        return redirect()->route('tenant.onboarding')->with('success', $message);
    }

    private function respondSuccess(Request $request, string $message)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }
}