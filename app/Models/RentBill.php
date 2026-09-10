<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentBill extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'tenant_id', 'property_id', 'owner_id',
        'bill_number', 'month',
        'rent_amount', 'electricity', 'water', 'maintenance',
        'food_charges', 'other_charges', 'other_charges_label',
        'late_fee', 'discount',
        'total_amount', 'paid_amount', 'due_amount',
        'due_date', 'status', 'last_reminder_sent_at', 'notes',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'electricity' => 'decimal:2',
        'water' => 'decimal:2',
        'maintenance' => 'decimal:2',
        'food_charges' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'due_date' => 'date',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function getMonthLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->month)->format('M Y');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }

    /**
     * Always-working payment link — independent of WhatsApp template/button
     * approval status. Used to show/copy/share the link from inside the app.
     */
    public function getPayUrlAttribute(): string
    {
        $base = rtrim(env('APP_PUBLIC_URL', config('app.url')), '/');
        return $base . '/pay/' . $this->bill_number;
    }

    public function recalculate(): void
    {
        $total = $this->rent_amount + $this->electricity + $this->water 
               + $this->maintenance + $this->food_charges + $this->other_charges 
               + $this->late_fee - $this->discount;
        
        $paid = $this->payments()->sum('amount');
        $due = max(0, $total - $paid);
        
        $status = match(true) {
            $due == 0 && $paid > 0 => 'paid',
            $paid > 0 && $due > 0 => 'partial',
            $due > 0 && $this->due_date->isPast() => 'overdue',
            default => 'pending',
        };
        
        $this->update([
            'total_amount' => $total,
            'paid_amount' => $paid,
            'due_amount' => $due,
            'status' => $status,
        ]);
    }
}