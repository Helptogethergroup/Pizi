<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A top-up the owner can buy: pays amount_inr, the Bed Wallet gets amount_inr + bonus_inr. */
class BedTopupPackage extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function getWalletCreditAttribute(): int
    {
        return (int) $this->amount_inr + (int) $this->bonus_inr;
    }
}
