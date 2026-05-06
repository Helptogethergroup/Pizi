<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Landmark extends Model
{
    protected $fillable = [
        'city_id', 'locality_id', 'name', 'slug', 'type',
        'latitude', 'longitude', 'description',
        'meta_title', 'meta_description',
        'is_active', 'display_order', 'view_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public static function booted()
    {
        static::creating(function (Landmark $l) {
            if (empty($l->slug)) {
                $l->slug = Str::slug($l->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_landmarks')
            ->withPivot('distance_km')
            ->withTimestamps()
            ->orderBy('property_landmarks.distance_km');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'college' => 'College',
            'university' => 'University',
            'hospital' => 'Hospital',
            'metro' => 'Metro Station',
            'office' => 'IT Park / Office',
            'mall' => 'Shopping Mall',
            'airport' => 'Airport',
            'railway' => 'Railway Station',
            default => ucfirst($this->type),
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'college', 'university' => '🎓',
            'hospital' => '🏥',
            'metro' => '🚇',
            'office' => '🏢',
            'mall' => '🛍️',
            'airport' => '✈️',
            'railway' => '🚆',
            default => '📍',
        };
    }
}