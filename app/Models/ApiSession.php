<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSession extends Model
{
    protected $fillable = [
        'user_id', 'uuid', 'refresh_hash', 'previous_refresh_hash',
        'device_id', 'device_name', 'platform', 'app_version', 'ip', 'user_agent',
        'last_used_at', 'refresh_expires_at', 'revoked_at', 'revoked_reason',
    ];

    protected $hidden = ['refresh_hash', 'previous_refresh_hash'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->refresh_expires_at->isFuture();
    }
}
