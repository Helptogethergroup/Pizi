<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'credit_package_id', 'amount_inr', 'credits_to_add',
        'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature',
        'status', 'payload', 'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount_inr' => 'integer',
        'credits_to_add' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class, 'credit_package_id');
    }
}