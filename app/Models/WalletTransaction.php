<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'user_id', 'type', 'amount', 'balance_after',
        'source', 'lead_id', 'reference', 'notes', 'actioned_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'admin_credit' => 'Admin added credits',
            'admin_debit'  => 'Admin removed credits',
            'purchase'     => 'Credits purchased',
            'lead_unlock'  => 'Lead unlocked',
            'refund'       => 'Refund',
            'bonus'        => 'Bonus credits',
            'expiry'       => 'Credits expired',
            default        => ucfirst($this->source),
        };
    }
}