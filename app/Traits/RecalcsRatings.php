<?php

namespace App\Traits;

use App\Models\Review;
use App\Models\Property;

trait RecalcsRatings
{
    /**
     * Recompute rating_avg + rating_count on properties table
     * from APPROVED reviews only. Call after approve / reject / delete.
     */
    protected function recalcPropertyRating(int $propertyId): void
    {
        $stats = Review::where('property_id', $propertyId)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->first();

        Property::where('id', $propertyId)->update([
            'rating_avg'   => round((float) ($stats->avg_rating ?? 0), 2),
            'rating_count' => (int) ($stats->cnt ?? 0),
        ]);
    }
}
