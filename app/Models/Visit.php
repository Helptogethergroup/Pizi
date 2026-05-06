<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $fillable = [
        'lead_id', 'property_id', 'field_executive_id',
        'scheduled_at', 'checked_in_at', 'checked_out_at',
        'checkin_lat', 'checkin_lng',
        'outcome', 'token_amount', 'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'token_amount' => 'float',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function fieldExecutive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'field_executive_id');
    }
}
