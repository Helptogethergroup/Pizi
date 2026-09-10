<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Property;
use Illuminate\Http\Request;

class OwnerReviewController extends Controller
{
    /**
     * Reviews on the logged-in owner's properties.
     *
     * NOTE: assumes properties.user_id = owner.
     * Agar tera owner column alag hai (e.g. owner_id) to neeche
     * 'user_id' ko apne column se replace kar dena.
     */
    public function index(Request $request)
    {
        $propertyIds = Property::where('owner_id', auth()->id())->pluck('id');

        $reviews = Review::whereIn('property_id', $propertyIds)
            ->with('property:id,name,slug')
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'avg'     => round((float) Review::whereIn('property_id', $propertyIds)->where('status', 'approved')->avg('rating'), 1),
            'total'   => Review::whereIn('property_id', $propertyIds)->count(),
            'pending' => Review::whereIn('property_id', $propertyIds)->where('status', 'pending')->count(),
            'no_response' => Review::whereIn('property_id', $propertyIds)->where('status', 'approved')->whereNull('owner_response')->count(),
        ];

        return view('owner.reviews.index', compact('reviews', 'stats'));
    }

    /**
     * Owner replies to a review (only on his own property).
     */
    public function respond(Request $request, Review $review)
    {
        $ownsIt = Property::where('id', $review->property_id)
            ->where('user_id', auth()->id())
            ->exists();

        abort_unless($ownsIt, 403);

        $request->validate(['owner_response' => 'required|string|max:2000']);

        $review->update([
            'owner_response'     => $request->owner_response,
            'owner_responded_at' => now(),
        ]);

        return back()->with('owner_success', 'Your response has been posted.');
    }
}
