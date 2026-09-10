<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender', 'message', 'tool_calls'];
    protected $casts = ['tool_calls' => 'json'];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'conversation_id');
    }
}
