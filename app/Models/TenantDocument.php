<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDocument extends Model
{
    protected $fillable = [
       'tenant_id', 'document_type', 'file_path', 
        'file_name', 'file_size', 'mime_type',
        'is_verified', 'verified_at', 'verified_by',
    ];

    protected $casts = [
         'verified_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getUrlAttribute(): string
    {
        return str_starts_with($this->file_path, 'http')
            ? $this->file_path
            : asset('storage/' . $this->file_path);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->document_type) {
            'aadhaar_front' => 'Aadhaar (Front)',
            'aadhaar_back' => 'Aadhaar (Back)',
            'pan' => 'PAN Card',
            'employment_id' => 'Employment ID',
            'student_id' => 'Student ID',
            'address_proof' => 'Address Proof',
            'photo' => 'Photo',
            default => 'Other',
        };
    }
}