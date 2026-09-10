<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Public: Submit review
     */
    public function store(Request $request, $propertyId)
    {
        $property = Property::findOrFail($propertyId);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'cleanliness' => 'nullable|integer|min:1|max:5',
            'food' => 'nullable|integer|min:1|max:5',
            'staff' => 'nullable|integer|min:1|max:5',
            'value_for_money' => 'nullable|integer|min:1|max:5',
            'amenities' => 'nullable|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|min:10|max:2000',
            'reviewer_name' => 'required_without:user_id|string|max:100',
            'reviewer_phone' => 'required_without:user_id|string|max:15',
        ]);

        $data = $validated;
        $data['property_id'] = $property->id;
        $data['status'] = 'pending'; // Admin will approve
        
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
            $data['reviewer_name'] = $data['reviewer_name'] ?? Auth::user()->name;
            $data['reviewer_phone'] = $data['reviewer_phone'] ?? Auth::user()->phone;
            
            // Check if verified tenant (booking history)
            $data['is_verified_tenant'] = \DB::table('tenants')
                ->where('user_id', Auth::id())
                ->where('property_id', $property->id)
                ->exists();
        }

        $review = Review::create($data);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Review submitted! It will appear after approval.',
                'data' => $review,
            ]);
        }

        return back()->with('success', 'Review submitted! It will appear after approval.');
    }

    /**
     * Owner: Respond to review
     */
    public function ownerRespond(Request $request, $reviewId)
    {
        $review = Review::with('property')->findOrFail($reviewId);
        
        // Check ownership
        if ($review->property->owner_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'owner_response' => 'required|string|max:1000',
        ]);

        $review->update([
            'owner_response' => $validated['owner_response'],
            'owner_responded_at' => now(),
        ]);

        return back()->with('success', 'Response posted!');
    }

    /**
     * Admin: Approve/Reject reviews
     */
    public function adminIndex(Request $request)
    {
        $query = Review::with(['property', 'user'])
            ->orderByDesc('created_at');

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $reviews = $query->paginate(20);

        return view('admin.reviews.index', compact('reviews'));
    }

    public function adminApprove($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'approved']);
        return back()->with('success', 'Review approved!');
    }

    public function adminReject(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $review->update([
            'status' => 'rejected',
            'admin_notes' => $request->reason ?? 'Rejected by admin',
        ]);
        return back()->with('success', 'Review rejected.');
    }

    public function adminSpam($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'spam']);
        return back()->with('success', 'Marked as spam.');
    }

    public function adminDelete($id)
    {
        Review::findOrFail($id)->delete();
        return back()->with('success', 'Review deleted!');
    }

    /**
     * Owner: View reviews on his properties
     */
    public function ownerIndex()
    {
        $reviews = Review::whereHas('property', function($q) {
                $q->where('owner_id', Auth::id());
            })
            ->with('property')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('owner.reviews.index', compact('reviews'));
    }
}