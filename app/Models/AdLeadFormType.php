<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdLeadFormType extends Model
{
    protected $fillable = ['platform', 'form_id', 'label', 'inquiry_type'];

    /**
     * Look up how a given ad platform's form should be classified.
     * Returns 'tenant'/'owner' if mapped, or null if this form_id
     * hasn't been registered yet (caller should fall back to 'unknown').
     */
    public static function typeFor(string $platform, ?string $formId): ?string
    {
        if (empty($formId)) {
            return null;
        }

        return static::where('platform', $platform)
            ->where('form_id', $formId)
            ->value('inquiry_type');
    }
}
