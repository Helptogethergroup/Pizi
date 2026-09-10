<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    protected $fillable = [
        'ticket_number',
        'tenant_id', 'property_id', 'owner_id',
        'category', 'priority',
        'title', 'description',
        'status',
        'assigned_to_id', 'assigned_to_name', 'assigned_to_phone',
        'assigned_at', 'resolved_at', 'resolution_notes',
        'rating', 'feedback',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'rating' => 'integer',
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ComplaintMedia::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ComplaintComment::class)->orderBy('created_at');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'plumbing' => '🚿 Plumbing',
            'electrical' => '⚡ Electrical',
            'wifi' => '📶 WiFi/Internet',
            'housekeeping' => '🧹 Housekeeping',
            'food' => '🍱 Food',
            'furniture' => '🪑 Furniture',
            'security' => '🔒 Security',
            'ac' => '❄️ AC',
            'water' => '💧 Water',
            default => '🔧 Other',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'rose',
            'high' => 'orange',
            'medium' => 'amber',
            default => 'ink',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => '📂 Open',
            'assigned' => '👤 Assigned',
            'in_progress' => '⏳ In Progress',
            'resolved' => '✅ Resolved',
            'closed' => '🏁 Closed',
            'cancelled' => '❌ Cancelled',
            default => $this->status,
        };
    }

    public function getResolutionTimeAttribute(): ?string
    {
        if (!$this->resolved_at) return null;
        return $this->created_at->diffForHumans($this->resolved_at, ['parts' => 2]);
    }
}