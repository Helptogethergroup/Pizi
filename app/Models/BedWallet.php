<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The Bed Wallet (rupees). Separate from the Lead Wallet. */
class BedWallet extends Model
{
    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
        'lifetime_added' => 'decimal:2',
        'lifetime_spent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BedWalletTransaction::class, 'wallet_id')->latest('id');
    }
}
