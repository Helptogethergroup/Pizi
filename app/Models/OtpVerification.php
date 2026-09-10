<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = [
        'identifier', 'identifier_type', 'otp', 'purpose',
        'attempts', 'verified', 'verified_at',
        'expires_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isMaxAttempts(): bool
    {
        return $this->attempts >= config('app.otp_max_attempts', 3);
    }

    public function scopeActive($query)
    {
        return $query->where('verified', false)
                     ->where('expires_at', '>', now());
    }

    public function scopeFor($query, string $identifier, string $purpose = 'login')
    {
        return $query->where('identifier', $identifier)
                     ->where('purpose', $purpose);
    }
}