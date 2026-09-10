<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementSignature extends Model
{
    protected $fillable = [
        'agreement_id', 'signer_type', 'signer_name', 'signer_id',
        'signature_data', 'ip_address', 'user_agent', 'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentAgreement::class, 'agreement_id');
    }
}