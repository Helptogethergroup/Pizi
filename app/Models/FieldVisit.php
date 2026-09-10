<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldVisit extends Model
{
    protected $fillable = [
        'property_id', 'field_executive_id', 'assigned_by_id',
        'visit_type', 'status',
        'scheduled_at', 'started_at', 'completed_at',
        'address_verified', 'amenities_verified', 'rooms_verified', 'safety_verified',
        'check_in_lat', 'check_in_lng', 'check_out_lat', 'check_out_lng',
        'remarks', 'admin_remarks',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'address_verified' => 'boolean',
        'amenities_verified' => 'boolean',
        'rooms_verified' => 'boolean',
        'safety_verified' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function fieldExecutive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'field_executive_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(FieldVisitMedia::class);
    }

    public function getVerificationProgressAttribute(): int
    {
        $done = ($this->address_verified ? 1 : 0)
              + ($this->amenities_verified ? 1 : 0)
              + ($this->rooms_verified ? 1 : 0)
              + ($this->safety_verified ? 1 : 0);
        return (int) round(($done / 4) * 100);
    }
}