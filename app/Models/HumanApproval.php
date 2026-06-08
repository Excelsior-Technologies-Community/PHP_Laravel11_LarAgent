<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HumanApproval extends Model
{
    protected $fillable = ['chat_message_id', 'status', 'admin_note'];

    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }
}