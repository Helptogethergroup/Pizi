<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'lifetime_added', 'lifetime_spent'];

    protected $casts = [
        'balance' => 'integer',
        'lifetime_added' => 'integer',
        'lifetime_spent' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function hasEnough(int $credits): bool
    {
        return $this->balance >= $credits;
    }
}