<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AiAgents\ChatAgent;
use App\Models\ChatMessage;
use App\Services\ChatAnalyticsService;

class ChatController extends Controller
{
    protected $analytics;

    public function __construct(ChatAnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function chat(Request $request, $message)
    {
        try {
            $sessionId = $request->session()->getId();
            $agent = new ChatAgent($sessionId);
            
            $response = $agent->safeRespond($message, $request->ip());
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
            ]);

            $sessionId = $request->session()->getId();
            $agent = new ChatAgent($sessionId);
            
            $response = $agent->safeRespond($request->message, $request->ip());
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getHistory(Request $request)
    {
        try {
            $sessionId = $request->session()->getId();
            
            $messages = ChatMessage::where('session_id', $sessionId)
                ->orWhere(function($query) use ($request) {
                    $query->where('ip_address', $request->ip())
                          ->whereNull('session_id');
                })
                ->latest()
                ->take(50)
                ->get()
                ->map(fn($msg) => [
                    'id' => $msg->id,
                    'user_message' => $msg->user_message,
                    'ai_response' => $msg->getFormattedAiResponseAttribute(),
                    'used_tool' => $msg->used_tool,
                    'tool_name' => $msg->tool_name,
                    'created_at' => $msg->created_at->diffForHumans(),
                    'full_time' => $msg->created_at->format('H:i:s'),
                ]);
            
            return response()->json([
                'success' => true,
                'messages' => $messages,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'messages' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function clearHistory(Request $request)
    {
        try {
            $deleted = ChatMessage::where('session_id', $request->session()->getId())->delete();
            
            $this->analytics->clearStatsCache();
            
            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => 'Chat history cleared successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear history: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            return response()->json([
                'success' => true,
                'stats' => $this->analytics->getStats(),
                'recent' => $this->analytics->getRecentActivity(10),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'stats' => [
                    'total_messages' => 0,
                    'total_users' => 0,
                    'today_messages' => 0,
                    'tools_used' => 0,
                    'avg_response_time' => 0,
                    'top_provider' => 'N/A',
                ],
                'recent' => []
            ]);
        }
    }

    public function getToolsList()
    {
        $tools = [
            ['name' => '🕐 Time', 'description' => 'Get current date and time', 'example' => 'What time is it?'],
            ['name' => '📝 Text Transform', 'description' => 'Uppercase, lowercase, capitalize, or reverse text', 'example' => 'uppercase hello world'],
            ['name' => '🧮 Calculator', 'description' => 'Perform basic math operations', 'example' => '25 + 17'],
            ['name' => '🎲 Random Number', 'description' => 'Generate random numbers', 'example' => 'random number 1 to 100'],
            ['name' => '🌤️ Weather', 'description' => 'Get weather for a city', 'example' => 'weather in New York'],
            ['name' => '💭 Quote', 'description' => 'Get an inspirational quote', 'example' => 'Give me a quote'],
            ['name' => '😂 Joke', 'description' => 'Tell me a joke', 'example' => 'Tell me a joke'],
            ['name' => '👋 Greeting', 'description' => 'Say hello', 'example' => 'Hello'],
        ];
        
        return response()->json($tools);
    }
}