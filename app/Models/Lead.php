<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

protected $fillable = [
        'property_id', 'assigned_telecaller_id', 'assigned_field_executive_id',
        'created_by_user_id',
        'name', 'phone', 'email',
        'preferred_locality', 'preferred_city', 'preferred_gender',
        'budget_min', 'budget_max', 'move_in_date',
        'message', 'source', 'status',
        'telecaller_notes', 'last_contacted_at', 'next_follow_up_at',
        'lead_type', 'is_locked', 'locked_by_user_id',
    ];
    

    protected $casts = [
        'move_in_date' => 'date',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'budget_min' => 'float',
        'budget_max' => 'float',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function telecaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_telecaller_id');
    }

    public function fieldExecutive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_field_executive_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'new' => 'bg-sky-100 text-sky-800',
            'contacted' => 'bg-blue-100 text-blue-800',
            'interested' => 'bg-emerald-100 text-emerald-800',
            'follow_up' => 'bg-amber-100 text-amber-800',
            'visit_scheduled' => 'bg-violet-100 text-violet-800',
            'visit_done' => 'bg-indigo-100 text-indigo-800',
            'closed_won' => 'bg-green-200 text-green-900',
            'closed_lost' => 'bg-rose-100 text-rose-800',
            'junk' => 'bg-slate-200 text-slate-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }


    public function unlocks()
    {
        return $this->hasMany(LeadUnlock::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    /**
     * Check if this lead is unlocked by a specific owner.
     */
    public function isUnlockedBy(int $userId): bool
    {
        return $this->unlocks()->where('user_id', $userId)->exists();
    }

    /**
     * Mask phone for display (show first 2 + last 2 digits only).
     */
    public function getMaskedPhoneAttribute(): string
    {
        $phone = $this->phone;
        if (strlen($phone) < 6) return str_repeat('X', strlen($phone));
        return substr($phone, 0, 2) . str_repeat('X', strlen($phone) - 4) . substr($phone, -2);
    }

    /**
     * Mask email for display.
     */
    public function getMaskedEmailAttribute(): ?string
    {
        if (!$this->email) return null;
        [$user, $domain] = explode('@', $this->email);
        $maskedUser = strlen($user) > 2
            ? substr($user, 0, 2) . str_repeat('X', strlen($user) - 2)
            : $user;
        return $maskedUser . '@' . $domain;
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'website' => 'Website',
            'whatsapp' => 'WhatsApp',
            'meta_ads' => 'Meta Ads',
            'google_ads' => 'Google Ads',
            'referral' => 'Referral',
            'walk_in' => 'Walk-in',
            'offline_campaign' => 'Offline Campaign',
            'tele_inbound' => 'Inbound Call',
            'manual' => 'Manually Added',
            default => ucfirst(str_replace('_', ' ', $this->source ?? 'unknown')),
        };
    }
}
