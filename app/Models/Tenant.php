<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Tenant extends Model
{
    protected $fillable = [
        'property_id', 'owner_id', 'user_id',
        'name', 'phone', 'email', 'dob', 'gender',
        'occupation', 'company_college',
        'address_line', 'city', 'state', 'pincode',
        'emergency_name', 'emergency_phone', 'emergency_relation',
        'room_number', 'bed_number',
        'monthly_rent', 'security_deposit',
        'move_in_date', 'move_out_date',
        'kyc_status', 'kyc_remarks', 'kyc_reminder_sent_at', 'onboarding_source',
        'aadhaar_number_masked', 'aadhaar_name', 'aadhaar_verified_at',
        'agreement_signed_at', 'agreement_ip',
        'status', 'notes',
        'notice_date', 'notice_reason',
    ];

    protected $casts = [
        'dob' => 'date',
        'move_in_date' => 'date',
        'move_out_date' => 'date',
        'notice_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'kyc_reminder_sent_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenantDocument::class);
    }

    public function getKycProgressAttribute(): int
    {
        $required = ['aadhaar_front', 'pan', 'photo'];
        $uploaded = $this->documents->pluck('document_type')->toArray();
        $done = count(array_intersect($required, $uploaded));
        return (int) (($done / count($required)) * 100);
    }
    
    public function bills()
    {
        return $this->hasMany(RentBill::class)->orderBy('month', 'desc');
    }

    public function payments()
    {
        return $this->hasMany(RentPayment::class)->orderBy('paid_at', 'desc');
    }

    public function getTotalDuesAttribute(): float
    {
        return $this->bills()->where('status', '!=', 'paid')->sum('due_amount');
    }

    public function getCurrentMonthBillAttribute()
    {
        return $this->bills()->where('month', now()->format('Y-m'))->first();
    }
    
    public function complaints()
    {
        return $this->hasMany(Complaint::class)->latest();
    }
    
    public function agreements()
    {
        return $this->hasMany(RentAgreement::class)->latest();
    }

    public function activeAgreement()
    {
        return $this->hasOne(RentAgreement::class)->where('status', 'active')->latest();
    }
    
    
}