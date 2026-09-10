<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = ['session_id','user_id','language','customer_phone','customer_name','metadata','last_activity_at'];
    protected $casts = ['metadata' => 'json','last_activity_at' => 'datetime'];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}