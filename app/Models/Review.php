<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'owner_id', 'reviewer_name', 'reviewer_phone',
        'rating', 'cleanliness', 'food', 'staff', 'value_for_money', 'amenities',
        'title', 'comment',
        'owner_response', 'owner_responded_at',
        'status', 'is_verified_tenant', 'admin_notes',
        'helpful_count', 'not_helpful_count',
    ];

    protected $casts = [
        'owner_responded_at' => 'datetime',
        'is_verified_tenant' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    // Calculate property rating after save
    protected static function booted()
    {
        static::saved(function ($review) {
            self::updatePropertyRating($review->property_id);
        });

        static::deleted(function ($review) {
            self::updatePropertyRating($review->property_id);
        });
    }

    public static function updatePropertyRating($propertyId)
    {
        $stats = self::where('property_id', $propertyId)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        Property::where('id', $propertyId)->update([
            'rating_avg' => round($stats->avg_rating ?? 0, 2),
            'rating_count' => $stats->total ?? 0,
        ]);
    }
}