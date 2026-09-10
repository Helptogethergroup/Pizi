<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentAgreement extends Model
{
    protected $fillable = [
        'agreement_number',
        'tenant_id', 'property_id', 'owner_id',
        'room_number', 'bed_number',
        'monthly_rent', 'security_deposit', 'maintenance_fee',
        'electricity_included', 'water_included', 'food_included',
        'start_date', 'end_date',
        'lock_in_months', 'notice_period_days', 'rent_due_day',
        'terms_template', 'additional_terms', 'house_rules',
        'status', 'signed_at', 'terminated_at', 'termination_reason',
        'parent_agreement_id', 'renewal_reminded_at',
        'notes',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'maintenance_fee' => 'decimal:2',
        'electricity_included' => 'boolean',
        'water_included' => 'boolean',
        'food_included' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'terminated_at' => 'datetime',
        'renewal_reminded_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(AgreementSignature::class, 'agreement_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RentAgreement::class, 'parent_agreement_id');
    }

    public function getDurationMonthsAttribute(): int
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->end_date->isPast()) return 0;
        return (int) now()->diffInDays($this->end_date);
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->status === 'active' && $this->days_remaining <= 30 && $this->days_remaining > 0;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date->isPast() && in_array($this->status, ['active', 'signed_owner']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => '📝 Draft',
            'sent' => '📤 Sent to Tenant',
            'signed_tenant' => '✍️ Tenant Signed',
            'signed_owner' => '✓ Owner Signed',
            'active' => '✅ Active',
            'expired' => '⏰ Expired',
            'terminated' => '❌ Terminated',
            'renewed' => '🔄 Renewed',
            default => $this->status,
        };
    }

    public function tenantSigned(): bool
    {
        return $this->signatures->where('signer_type', 'tenant')->count() > 0;
    }

    public function ownerSigned(): bool
    {
        return $this->signatures->where('signer_type', 'owner')->count() > 0;
    }
}