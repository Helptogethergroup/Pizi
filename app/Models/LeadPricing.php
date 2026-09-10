<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadPricing extends Model
{
    protected $table = 'lead_pricing';

    protected $fillable = ['lead_type', 'credit_cost', 'is_active'];

    protected $casts = [
        'credit_cost' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function costFor(string $leadType): int
    {
        $row = static::where('lead_type', $leadType)
            ->where('is_active', true)
            ->first();
        return $row ? $row->credit_cost : 0;
    }
}