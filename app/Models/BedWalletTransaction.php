<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BedWalletTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'gst_amount' => 'decimal:2',
    ];

    public static function sourceLabel(string $source): string
    {
        return [
            'topup' => 'Wallet top-up',
            'admin_credit' => 'Added by Pizi',
            'admin_debit' => 'Deducted by Pizi',
            'plan_renewal' => 'Monthly bed plan',
            'new_beds' => 'New beds (full month)',
            'refund' => 'Refund',
        ][$source] ?? ucfirst(str_replace('_', ' ', $source));
    }
}
