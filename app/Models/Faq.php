<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';
    protected $fillable = ['question_en','question_hi','answer_en','answer_hi','category','views','active'];
    protected $casts = ['active' => 'boolean'];
}