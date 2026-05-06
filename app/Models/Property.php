<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'city_id', 'locality_id',
        'name', 'slug', 'description', 'rules',
        'gender', 'property_type',
        'rent_min', 'rent_max', 'security_deposit', 'food_included',
        'sharing_options',
        'address_line', 'landmark', 'pincode', 'latitude', 'longitude',
        'is_active', 'is_verified', 'is_featured',
        'total_rooms', 'available_rooms',
        'meta_title', 'meta_description', 'cover_image',
        'view_count', 'lead_count',
    ];

    protected $casts = [
        'sharing_options' => 'array',
        'food_included' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'rent_min' => 'float',
        'rent_max' => 'float',
        'security_deposit' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public static function booted()
    {
        static::creating(function (Property $property) {
            if (empty($property->slug)) {
                $property->slug = static::generateSlug($property->name);
            }
        });
    }

    public static function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('display_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function getRentRangeAttribute(): string
    {
        if ($this->rent_min == $this->rent_max) {
            return '₹' . number_format($this->rent_min);
        }
        return '₹' . number_format($this->rent_min) . ' - ₹' . number_format($this->rent_max);
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image && str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&q=80';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }


    public function landmarks()
    {
        return $this->belongsToMany(Landmark::class, 'property_landmarks')
            ->withPivot('distance_km')
            ->withTimestamps()
            ->orderBy('property_landmarks.distance_km');
    }
}
