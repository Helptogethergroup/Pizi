<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'property_id', 'owner_id',
        'room_number', 'floor', 'room_type', 'gender',
        'monthly_rent', 'security_deposit',
        'has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi',
        'notes', 'status',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'has_ac' => 'boolean',
        'has_attached_bathroom' => 'boolean',
        'has_balcony' => 'boolean',
        'has_geyser' => 'boolean',
        'has_wifi' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class)->orderBy('bed_number');
    }

    /**
     * Same "amenities" table Property uses — reused here via its
     * own pivot (room_amenities) so both share one source of truth.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'room_amenities');
    }

    public function getCapacityAttribute(): int
    {
        return match($this->room_type) {
            'single' => 1,
            'double' => 2,
            'triple' => 3,
            'quad' => 4,
            'quint' => 5,
            'dorm' => 8,
            default => 2,
        };
    }

    public function getOccupiedCountAttribute(): int
    {
        return $this->beds->where('status', 'occupied')->count();
    }

    public function getVacantCountAttribute(): int
    {
        return $this->beds->where('status', 'vacant')->count();
    }

    public function getOccupancyPercentAttribute(): int
    {
        $total = $this->beds->count();
        if ($total === 0) return 0;
        return (int) (($this->occupied_count / $total) * 100);
    }

    public function getAmenitiesListAttribute(): array
    {
        return $this->amenities->map(function ($a) {
            return trim(($a->icon ?? '') . ' ' . $a->name);
        })->toArray();
    }
}