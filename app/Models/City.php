<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'state', 'description',
        'meta_title', 'meta_description', 'is_active', 'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class)->orderBy('name');
    }

    public function properties(): HasManyThrough
    {
        return $this->hasManyThrough(Property::class, Locality::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }


    public function landmarks()
    {
        return $this->hasMany(Landmark::class)->where('is_active', true);
    }
}
