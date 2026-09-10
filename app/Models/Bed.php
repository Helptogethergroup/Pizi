<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_id', 'property_id', 'owner_id',
        'bed_number', 'bed_type',
        'monthly_rent', 'status',
        'tenant_id', 'occupied_since', 'notes',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'occupied_since' => 'date',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'occupied' => 'rose',
            'vacant' => 'emerald',
            'reserved' => 'amber',
            'maintenance' => 'ink',
            default => 'ink',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'occupied' => '🛏️ Occupied',
            'vacant' => '✅ Vacant',
            'reserved' => '⏳ Reserved',
            'maintenance' => '🔧 Maintenance',
            default => $this->status,
        };
    }
}