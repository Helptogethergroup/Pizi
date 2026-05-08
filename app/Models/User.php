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
        'name', 'email', 'phone', 'password', 'role', 'is_active', 'avatar',
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
        ];
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_telecaller_id');
    }

    public function fieldVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'field_executive_id');
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
}
