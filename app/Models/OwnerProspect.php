<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerProspect extends Model
{
    protected $fillable = [
        'telecaller_id', 'name', 'phone', 'source', 'notes',
        'call_status', 'called_at', 'call_attempts', 'rejection_reason',
        'registered_at', 'property_listed_at', 'paid_plan_at',
        'linked_owner_user_id',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'registered_at' => 'datetime',
        'property_listed_at' => 'datetime',
        'paid_plan_at' => 'datetime',
    ];

    public function telecaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'telecaller_id');
    }

    public function linkedOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_owner_user_id');
    }

    public function stageBadge(): string
    {
        return match (true) {
            $this->paid_plan_at !== null => 'bg-green-200 text-green-900',
            $this->property_listed_at !== null => 'bg-emerald-100 text-emerald-800',
            $this->registered_at !== null => 'bg-sky-100 text-sky-800',
            $this->call_status === 'not_interested' => 'bg-rose-100 text-rose-800',
            $this->call_status === 'no_answer' => 'bg-amber-100 text-amber-800',
            $this->call_status === 'attended' => 'bg-blue-100 text-blue-800',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function stageLabel(): string
    {
        return match (true) {
            $this->paid_plan_at !== null => '💳 Paid plan bought',
            $this->property_listed_at !== null => '🏠 Property listed',
            $this->registered_at !== null => '✅ Registered (free)',
            $this->call_status === 'not_interested' => '✗ Not interested',
            $this->call_status === 'no_answer' => '📵 No answer',
            $this->call_status === 'attended' => '📞 Called',
            default => '⏳ Pending call',
        };
    }
}
