<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyNotice extends Model
{
    use SoftDeletes;

    public const KINDS = ['notice', 'urgent', 'menu', 'rule'];

    protected $fillable = ['owner_id', 'property_id', 'created_by', 'title', 'body', 'kind', 'pinned', 'expires_at'];

    protected $casts = ['pinned' => 'boolean', 'expires_at' => 'datetime'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** Notices that have not expired. */
    public function scopeLive($query)
    {
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Notices a given tenant may see: their PG's, plus the ones the owner posted for every PG. */
    public function scopeForTenant($query, Tenant $tenant)
    {
        return $query->where('owner_id', $tenant->owner_id)
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $tenant->property_id));
    }
}
