<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
    'name', 'email', 'phone', 'password', 'role', 'is_active', 'is_test_account', 'avatar', 'signup_type', 'address',
    'owner_id', 'permissions', 'upi_id' ,'dashboard_guide_seen_at',
    'gst_number', 'billing_business_name', 'billing_address', 'billing_state', 'billing_pincode',
    'daily_call_target', 'lead_specialization',
];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_test_account' => 'boolean',
        ];
    }

    // public function properties(): HasMany
    // {
    //     return $this->hasMany(Property::class, 'owner_id');
    // }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_telecaller_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function fieldVisits()
     {
           return $this->hasMany(\App\Models\FieldVisit::class, 'field_executive_id');
     }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isTeleCaller(): bool
    {
        return $this->role === 'telecaller';
    }

    public function isFieldExecutive(): bool
    {
        return $this->role === 'field_executive';
    }

    /**
     * Whether this telecaller should receive leads of the given inquiry
     * type ('tenant' or 'owner'). Non-telecallers never match.
     */
    public function handlesLeadType(string $inquiryType): bool
    {
        if (!$this->isTeleCaller()) {
            return false;
        }

        return in_array($this->lead_specialization, ['both', $inquiryType], true);
    }


    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function unlockedLeads()
    {
        return $this->hasMany(LeadUnlock::class);
    }

    /**
     * Get wallet balance, auto-create wallet if missing.
     */
    public function getWalletBalanceAttribute(): int
    {
        $wallet = $this->wallet ?? $this->wallet()->create(['balance' => 0]);
        return $wallet->balance;
    }

    public function isSeoManager(): bool
    {
        return $this->role === 'seo_manager';
    }
    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'owner_id');
    }

    public function rentBills()
    {
        return $this->hasMany(RentBill::class, 'owner_id');
    }

    public function rentPayments()
    {
        return $this->hasMany(RentPayment::class, 'owner_id');
    }

    public function complaintsAsOwner()
    {
        return $this->hasMany(Complaint::class, 'owner_id');
    }
    
    public function rentAgreements()
    {
        return $this->hasMany(RentAgreement::class, 'owner_id');
    }
    public function isTenant()
{
    return $this->role === 'tenant';
}


public function isTenantOnboarded(): bool
{
    return $this->journey_stage >= 5;
}

public function getJourneyProgressAttribute(): int
{
    return round(($this->journey_stage / 5) * 100);
}

public function getManagedPropertyIds()
{
    if ($this->role === 'pg_manager') {
        return \DB::table('property_managers')->where('manager_id', $this->id)->pluck('property_id');
    }
    return \App\Models\Property::where('owner_id', $this->id)->pluck('id');
}


public function hasFeature($key)
{
    // Owners/admins always have full access
    if ($this->role !== 'pg_manager') {
        return true;
    }

    $allowed = json_decode($this->permissions ?? '[]', true) ?: [];
    return in_array($key, $allowed);
}

}