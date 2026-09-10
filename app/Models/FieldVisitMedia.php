<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldVisitMedia extends Model
{
    protected $fillable = [
        'field_visit_id', 'media_type', 'file_path', 'caption',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(FieldVisit::class, 'field_visit_id');
    }

    public function getUrlAttribute(): string
    {
        return str_starts_with($this->file_path, 'http')
            ? $this->file_path
            : asset('storage/' . $this->file_path);
    }
}