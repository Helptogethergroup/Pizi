<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatTemplate extends Model
{
    protected $fillable = ['key','response_en','response_hi','category','priority','active'];
    protected $casts = ['active' => 'boolean'];
}