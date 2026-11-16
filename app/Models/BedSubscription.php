<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An owner who is on the Bed Plan. active = paid up to cycle_end, expired = restricted until recharged, disabled = switched off by admin. */
class BedSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'custom_price_per_bed' => 'decimal:2',
        'started_at' => 'datetime',
        'cycle_start' => 'datetime',
        'cycle_end' => 'datetime',
        'last_renewed_at' => 'datetime',
        'paid_beds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BedPlan::class, 'bed_plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }
}
