<?php

namespace App\Http\Controllers\FieldExec;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Visit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $q = Visit::where('field_executive_id', auth()->id())
            ->with(['lead', 'property.locality']);

        $filter = $request->get('filter', 'all');
        match ($filter) {
            'today' => $q->whereDate('scheduled_at', today()),
            'pending' => $q->where('outcome', 'pending'),
            'closed' => $q->where('outcome', 'closed'),
            'this_week' => $q->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()]),
            default => null,
        };

        $visits = $q->latest('scheduled_at')->paginate(15)->withQueryString();

        return view('fieldexec.visits', compact('visits', 'filter'));
    }

    public function show(Visit $visit)
    {
        $this->authorizeAccess($visit);
        $visit->load(['lead', 'property.images', 'property.locality', 'property.city', 'property.amenities']);
        return view('fieldexec.visit-detail', compact('visit'));
    }

   public function checkin(Request $request, Visit $visit, \App\Services\GeoService $geo)
    {
        $this->authorizeAccess($visit);

        $data = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'force' => 'nullable|boolean', // admin override
        ]);

        // Geo-fence check
        $property = $visit->property;
        $isAdmin = auth()->user()->isAdmin();
        $forced = $request->boolean('force') && $isAdmin;

        $distance = null;
        $allowed = true;
        $maxRadius = 100; // meters

        if ($property && $property->latitude && $property->longitude) {
            $distance = $geo->distanceMeters(
                $data['latitude'], $data['longitude'],
                $property->latitude, $property->longitude
            );

            $allowed = $distance <= $maxRadius || $forced;
        }

        if (!$allowed) {
            return back()->withErrors([
                'checkin' => "❌ You are {$geo->formatDistance($distance)} away from the property. Geo-fence requires you to be within {$maxRadius}m. Please reach the property first.",
            ])->with('checkin_distance', $distance);
        }

        $visit->update([
            'checked_in_at' => now(),
            'checkin_lat' => $data['latitude'],
            'checkin_lng' => $data['longitude'],
            'checkin_distance_m' => $distance,
        ]);

        $message = $forced
            ? "✓ Admin override: Checked in (was {$geo->formatDistance($distance)} away)."
            : "✓ Checked in! You're {$geo->formatDistance($distance ?? 0)} from the property.";

        return back()->with('success', $message);
    }

    public function complete(Request $request, Visit $visit)
    {
        $this->authorizeAccess($visit);

        $data = $request->validate([
            'outcome' => 'required|in:closed,rejected,revisit,no_show',
            'token_amount' => 'nullable|numeric|min:0',
            'receipt_image' => 'nullable|image|max:4096',
            'tenant_feedback' => 'nullable|string|max:1000',
            'rejection_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Receipt upload
        if ($request->hasFile('receipt_image')) {
            $data['receipt_image'] = $request->file('receipt_image')
                ->store('visits/receipts', 'public');
        }

        $data['checked_out_at'] = now();
        $visit->update($data);

        // Update lead status
        $leadStatus = match ($data['outcome']) {
            'closed' => 'closed_won',
            'rejected' => 'closed_lost',
            'revisit' => 'follow_up',
            'no_show' => 'follow_up',
        };

        if ($visit->lead) {
            $visit->lead->update(['status' => $leadStatus]);
        }

        return redirect()->route('fieldexec.dashboard')
            ->with('success', "✓ Visit marked as: {$data['outcome']}. Great job!");
    }

    private function authorizeAccess(Visit $visit): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if ($visit->field_executive_id !== $user->id) {
            abort(403, 'This visit is not assigned to you.');
        }
    }
}