<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $fillable = [
        'lead_id', 'property_id', 'field_executive_id',
        'scheduled_at', 'checked_in_at', 'checked_out_at',
        'checkin_lat', 'checkin_lng', 'checkin_distance_m',
        'outcome', 'token_amount', 'receipt_image',
        'notes', 'tenant_feedback', 'rejection_reason',
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


    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_image) return null;
        if (str_starts_with($this->receipt_image, 'http')) return $this->receipt_image;
        return asset('storage/' . $this->receipt_image);
    }

    public function getOutcomeBadgeAttribute(): string
    {
        return match ($this->outcome) {
            'pending' => 'bg-amber-100 text-amber-800',
            'closed' => 'bg-emerald-500 text-white',
            'rejected' => 'bg-rose-100 text-rose-800',
            'revisit' => 'bg-violet-100 text-violet-800',
            'no_show' => 'bg-slate-200 text-slate-700',
            default => 'bg-slate-100',
        };
    }
}
