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
                'today_messages' => ChatMessage::today()->count(),
                'tools_used' => ChatMessage::withTools()->count(),
                'avg_response_time' => round(ChatMessage::avg('response_time_ms') ?? 0, 2),
                'top_provider' => ChatMessage::selectRaw('provider, count(*) as count')
                    ->groupBy('provider')
                    ->orderByDesc('count')
                    ->first()?->provider ?? 'N/A',
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
                'user_message' => $msg->getUserMessagePreviewAttribute(),
                'ai_response' => $msg->getFormattedAiResponseAttribute(),
                'used_tool' => $msg->used_tool,
                'tool_name' => $msg->tool_name,
                'time' => $msg->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function clearStatsCache(): void
    {
        Cache::forget('chat_stats');
    }
}