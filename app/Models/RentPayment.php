<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentPayment extends Model
{
    protected $fillable = [
        'rent_bill_id', 'tenant_id', 'owner_id',
        'receipt_number', 'amount',
        'payment_method', 'transaction_ref',
        'paid_at', 'received_by_id', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(RentBill::class, 'rent_bill_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => '💵 Cash',
            'upi' => '📱 UPI',
            'bank_transfer' => '🏦 Bank Transfer',
            'razorpay' => '💳 Razorpay',
            'phonepe' => '📱 PhonePe',
            'paytm' => '📱 Paytm',
            'cheque' => '📝 Cheque',
            default => '💰 Other',
        };
    }
}