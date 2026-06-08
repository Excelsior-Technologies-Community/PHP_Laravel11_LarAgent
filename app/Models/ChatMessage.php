<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'ip_address',
        'user_message',
        'ai_response',
        'provider',
        'model',
        'response_time_ms',
        'used_tool',
        'tool_name',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'used_tool' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeWithTools(Builder $query): Builder
    {
        return $query->where('used_tool', true);
    }

    public function getFormattedAiResponseAttribute(): string
    {
        $response = json_decode($this->ai_response, true) ?? $this->ai_response;
        
        if (is_array($response) && isset($response['message'])) {
            return $response['message'];
        }
        
        return is_string($response) ? $response : json_encode($response);
    }

    public function getUserMessagePreviewAttribute(): string
    {
        return strlen($this->user_message) > 50 
            ? substr($this->user_message, 0, 50) . '...' 
            : $this->user_message;
    }
    public function humanApprovals()
{
    return $this->hasMany(HumanApproval::class);
}
}