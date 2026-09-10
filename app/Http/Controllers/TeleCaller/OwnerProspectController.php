<?php

namespace App\Http\Controllers\TeleCaller;

use App\Http\Controllers\Controller;
use App\Models\OwnerProspect;
use App\Models\User;
use Illuminate\Http\Request;

class OwnerProspectController extends Controller
{
    public function index(Request $request)
    {
        $q = OwnerProspect::query();

        if (!auth()->user()->isAdmin()) {
            $q->where('telecaller_id', auth()->id());
        }

        if ($request->filled('stage')) {
            $q->where(function ($x) use ($request) {
                match ($request->stage) {
                    'pending' => $x->whereNull('call_status'),
                    'called' => $x->whereNotNull('call_status')->whereNull('registered_at'),
                    'registered' => $x->whereNotNull('registered_at')->whereNull('property_listed_at'),
                    'listed' => $x->whereNotNull('property_listed_at')->whereNull('paid_plan_at'),
                    'paid' => $x->whereNotNull('paid_plan_at'),
                    'not_interested' => $x->where('call_status', 'not_interested'),
                    default => $x,
                };
            });
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(fn ($x) => $x->where('name', 'like', $term)->orWhere('phone', 'like', $term));
        }

        $prospects = $q->latest()->paginate(20)->withQueryString();

        return view('telecaller.owner-prospects.index', compact('prospects'));
    }

    public function create()
    {
        return view('telecaller.owner-prospects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'source' => 'nullable|in:cold_call,referral,market_visit,other',
            'notes' => 'nullable|string|max:2000',
        ]);

        $data['telecaller_id'] = auth()->id();

        $prospect = OwnerProspect::create($data);

        return redirect()->route('telecaller.owner-prospects.show', $prospect)
            ->with('success', '✓ Owner prospect added.');
    }

    public function show(OwnerProspect $ownerProspect)
    {
        $this->authorizeAccess($ownerProspect);

        // Helpful hint only — does NOT auto-mark anything. Shows whether an
        // owner account with this phone already exists on Pizi, so the
        // telecaller can decide whether to manually mark it registered.
        $last10 = substr(preg_replace('/[^0-9]/', '', $ownerProspect->phone), -10);
        $matchedOwner = $last10
            ? User::whereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10])
                ->where('role', 'owner')->first()
            : null;

        $propertyCount = $matchedOwner
            ? \App\Models\Property::where('owner_id', $matchedOwner->id)->count()
            : 0;

        $hasPaidPlan = $matchedOwner
            ? \App\Models\Payment::where('user_id', $matchedOwner->id)->where('status', 'paid')->exists()
            : false;

        return view('telecaller.owner-prospects.show', compact('ownerProspect', 'matchedOwner', 'propertyCount', 'hasPaidPlan'));
    }

    public function markCalled(Request $request, OwnerProspect $ownerProspect)
    {
        $this->authorizeAccess($ownerProspect);

        $data = $request->validate([
            'call_status' => 'required|in:attended,no_answer,not_interested',
            'notes' => 'nullable|string|max:2000',
            'rejection_reason' => 'required_if:call_status,not_interested|nullable|string|max:50',
        ]);

        $update = [
            'call_status' => $data['call_status'],
            'called_at' => now(),
            'notes' => $data['notes'] ?? $ownerProspect->notes,
            'rejection_reason' => $data['call_status'] === 'not_interested' ? $data['rejection_reason'] : $ownerProspect->rejection_reason,
        ];

        if ($data['call_status'] === 'no_answer') {
            $update['call_attempts'] = $ownerProspect->call_attempts + 1;
        }

        $ownerProspect->update($update);

        return back()->with('success', '✓ Call outcome saved.');
    }

    /**
     * Telecaller manually marks a conversion milestone — no automatic
     * detection, per how the owner would report it back verbally/on WhatsApp.
     */
    public function markStage(Request $request, OwnerProspect $ownerProspect)
    {
        $this->authorizeAccess($ownerProspect);

        $request->validate(['stage' => 'required|in:registered,property_listed,paid_plan,undo_registered,undo_property_listed,undo_paid_plan']);

        $map = [
            'registered' => ['registered_at' => now()],
            'property_listed' => ['property_listed_at' => now()],
            'paid_plan' => ['paid_plan_at' => now()],
            'undo_registered' => ['registered_at' => null],
            'undo_property_listed' => ['property_listed_at' => null],
            'undo_paid_plan' => ['paid_plan_at' => null],
        ];

        $ownerProspect->update($map[$request->stage]);

        return back()->with('success', '✓ Updated.');
    }

    private function authorizeAccess(OwnerProspect $prospect): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }
        if ($prospect->telecaller_id !== auth()->id()) {
            abort(403);
        }
    }
}
