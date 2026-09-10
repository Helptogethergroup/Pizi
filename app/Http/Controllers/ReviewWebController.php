<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewWebController extends Controller
{
    /**
     * Public "Write a Review" form submit.
     * Logged-in OR guest dono allowed. Review goes to 'pending'
     * — admin approve karega tabhi property page pe dikhega.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id'     => 'required|integer|exists:properties,id',
            'rating'          => 'required|integer|min:1|max:5',
            'comment'         => 'required|string|min:10|max:3000',
            'title'           => 'nullable|string|max:200',
            'reviewer_name'   => 'nullable|string|max:255',
            'reviewer_phone'  => 'nullable|string|max:15',
            'cleanliness'     => 'nullable|integer|min:1|max:5',
            'food'            => 'nullable|integer|min:1|max:5',
            'staff'           => 'nullable|integer|min:1|max:5',
            'value_for_money' => 'nullable|integer|min:1|max:5',
            'amenities'       => 'nullable|integer|min:1|max:5',
        ]);

        // Guest ke liye naam zaroori
        if (!auth()->check() && empty($data['reviewer_name'])) {
            return back()
                ->withErrors(['reviewer_name' => 'Please enter your name.'])
                ->withInput();
        }

        $review = Review::create([
            'property_id'        => $data['property_id'],
            'user_id'            => auth()->id(),
            'reviewer_name'      => auth()->check() ? (auth()->user()->name ?? $data['reviewer_name'] ?? null) : ($data['reviewer_name'] ?? null),
            'reviewer_phone'     => $data['reviewer_phone'] ?? null,
            'rating'             => $data['rating'],
            'cleanliness'        => $data['cleanliness'] ?? null,
            'food'               => $data['food'] ?? null,
            'staff'              => $data['staff'] ?? null,
            'value_for_money'    => $data['value_for_money'] ?? null,
            'amenities'          => $data['amenities'] ?? null,
            'title'              => $data['title'] ?? null,
            'comment'            => $data['comment'],
            'status'             => 'pending',
            'is_verified_tenant' => 0,
        ]);

        try {
            $owner = $review->property?->owner;
            $owner?->notify(new \App\Notifications\ReviewPosted($review));
        } catch (\Exception $e) {
            \Log::warning('Review notification failed: ' . $e->getMessage());
        }

        return back()->with('review_success', 'Thanks! Your review has been submitted and will appear after a quick review.');
    }

    /**
     * Helpful / Not-helpful counter (sirf approved reviews pe).
     */
    public function helpful(Request $request, Review $review)
    {
        $type = $request->input('type') === 'no' ? 'not_helpful_count' : 'helpful_count';
        $review->increment($type);

        if ($request->wantsJson()) {
            return response()->json([
                'helpful_count'     => $review->helpful_count,
                'not_helpful_count' => $review->not_helpful_count,
            ]);
        }

        return back();
    }
}
