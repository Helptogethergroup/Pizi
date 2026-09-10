<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'owner_id', 'payment_id', 'credit_package_id',
        'title', 'total_amount', 'gst_rate', 'base_amount', 'gst_amount',
        'type', 'pdf_path', 'sent_via_whatsapp', 'whatsapp_sent_at',
        'created_by', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'sent_via_whatsapp' => 'boolean',
        'whatsapp_sent_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class, 'credit_package_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Next sequential invoice number, e.g. PIZI-2026-000123.
     */
    public static function nextInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $count = static::whereYear('created_at', now()->year)->count() + 1;
        return sprintf('PIZI-%s-%06d', $year, $count);
    }
}
