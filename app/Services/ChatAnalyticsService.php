<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Cache;

class ChatAnalyticsService
{
    public function getStats(): array
    {
        return Cache::remember('chat_stats', 300, function () {
            return [
                'total_messages' => ChatMessage::count(),
                'total_users' => ChatMessage::distinct('session_id')->count('session_id'),
                'today_messages' => ChatMessage::whereDate('created_at', today())->count(),
                'pending_approvals' => ChatMessage::where('status', 'pending')->count(),
                'total_tokens' => ChatMessage::sum('token_usage'),
                'avg_response_time' => round(ChatMessage::avg('response_time_ms') ?? 0, 2),
                'top_model' => ChatMessage::selectRaw('model_name, count(*) as count')
                    ->groupBy('model_name')
                    ->orderByDesc('count')
                    ->first()?->model_name ?? 'N/A',
            ];
        });
    }

    public function getRecentActivity(int $limit = 10): array
    {
        return ChatMessage::latest()
            ->take($limit)
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'user_message' => $msg->user_message,
                'status' => $msg->status,
                'tokens' => $msg->token_usage,
                'time' => $msg->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function clearStatsCache(): void
    {
        Cache::forget('chat_stats');
    }
}