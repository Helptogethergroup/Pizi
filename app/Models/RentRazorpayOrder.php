<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentRazorpayOrder extends Model
{
    protected $fillable = [
        'rent_bill_id', 'tenant_id', 'amount',
        'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature',
        'status', 'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(RentBill::class, 'rent_bill_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}