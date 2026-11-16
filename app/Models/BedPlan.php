<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A price list the admin makes: price per bed per month, GST already inside. */
class BedPlan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price_per_bed' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
