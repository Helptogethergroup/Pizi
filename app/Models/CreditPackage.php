<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPackage extends Model
{
    protected $fillable = [
        'name', 'price_inr', 'credits', 'bonus_credits',
        'is_popular', 'is_active', 'display_order', 'description',
    ];

    protected $casts = [
        'price_inr' => 'integer',
        'credits' => 'integer',
        'bonus_credits' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getTotalCreditsAttribute(): int
    {
        return $this->credits + $this->bonus_credits;
    }

    public function getPricePerCreditAttribute(): float
    {
        return $this->total_credits > 0 ? round($this->price_inr / $this->total_credits, 2) : 0;
    }
}