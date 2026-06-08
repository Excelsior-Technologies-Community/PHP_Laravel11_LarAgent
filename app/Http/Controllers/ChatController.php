<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat(Request $request, string $message)
    {
        $startTime = microtime(true);
        $sessionId = $request->session()->getId();

        try {
            // ✅ Tool detection & response
            $result = $this->processMessage($message);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $status = $this->isSensitiveAction($message) ? 'pending' : 'approved';

            ChatMessage::create([
                'session_id'      => $sessionId,
                'ip_address'      => $request->ip(),
                'user_message'    => $message,
                'ai_response'     => $result['message'],
                'status'          => $status,
                'is_approved'     => ($status === 'approved'),
                'token_usage'     => 0,
                'response_time_ms'=> $responseTime,
                'model_name'      => 'rule-based',
                'used_tool'       => $result['used_tool'],
                'tool_name'       => $result['tool_name'] ?? null,
            ]);

            return response()->json([
                'status'    => 'success',
                'message'   => $result['message'],
                'used_tool' => $result['used_tool'],
                'tool_name' => $result['tool_name'] ?? null,
                'meta'      => [
                    'response_time_ms' => $responseTime,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ChatController@chat error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function processMessage(string $message): array
    {
        $msg = strtolower(trim($message));

        // ⏰ Time tool
        if (str_contains($msg, 'time') || str_contains($msg, 'date')) {
            return [
                'message'   => '🕐 Current date & time: ' . now()->format('D, d M Y - h:i A'),
                'used_tool' => true,
                'tool_name' => 'Time',
            ];
        }

        // 🔢 Calculator tool
        if (preg_match('/[\d\s\+\-\*\/\%\(\)\.]+/', $msg) && preg_match('/[\+\-\*\/]/', $msg)) {
            preg_match('/[\d\s\+\-\*\/\%\(\)\.]+/', $message, $matches);
            $expr = trim($matches[0]);
            try {
                $result = $this->safeEval($expr);
                return [
                    'message'   => "🧮 $expr = **$result**",
                    'used_tool' => true,
                    'tool_name' => 'Calculator',
                ];
            } catch (\Exception $e) {
                // fall through
            }
        }

        // 🌤 Weather tool
        if (str_contains($msg, 'weather')) {
            preg_match('/weather\s+(?:in\s+)?([a-zA-Z\s]+)/i', $message, $m);
            $city = isset($m[1]) ? trim($m[1]) : 'your city';
            $temps = [18, 22, 25, 28, 30, 15, 20];
            $conditions = ['☀️ Sunny', '⛅ Partly Cloudy', '🌧 Rainy', '🌤 Clear'];
            return [
                'message'   => "🌤 Weather in **$city**: " . $conditions[array_rand($conditions)] . ", " . $temps[array_rand($temps)] . "°C",
                'used_tool' => true,
                'tool_name' => 'Weather',
            ];
        }

        // 🔠 Uppercase tool
        if (str_contains($msg, 'uppercase')) {
            $text = trim(str_ireplace('uppercase', '', $message));
            return [
                'message'   => '🔠 ' . strtoupper($text),
                'used_tool' => true,
                'tool_name' => 'Text',
            ];
        }

        // 🔡 Lowercase tool
        if (str_contains($msg, 'lowercase')) {
            $text = trim(str_ireplace('lowercase', '', $message));
            return [
                'message'   => '🔡 ' . strtolower($text),
                'used_tool' => true,
                'tool_name' => 'Text',
            ];
        }

        // 🎲 Random number
        if (str_contains($msg, 'random')) {
            preg_match('/(\d+)\s*(?:to|-)\s*(\d+)/', $msg, $m);
            $min = isset($m[1]) ? (int)$m[1] : 1;
            $max = isset($m[2]) ? (int)$m[2] : 100;
            return [
                'message'   => "🎲 Random number between $min-$max: **" . rand($min, $max) . "**",
                'used_tool' => true,
                'tool_name' => 'Random',
            ];
        }

        // 😄 Joke
        if (str_contains($msg, 'joke')) {
            $jokes = [
                "Why do programmers prefer dark mode? Because light attracts bugs! 🐛",
                "Why did the developer go broke? Because he used up all his cache! 💸",
                "A SQL query walks into a bar, walks up to two tables and asks: 'Can I join you?' 🍺",
                "Why do Java developers wear glasses? Because they don't C#! 👓",
                "How many programmers does it take to change a light bulb? None — it's a hardware problem! 💡",
            ];
            return [
                'message'   => "😄 " . $jokes[array_rand($jokes)],
                'used_tool' => true,
                'tool_name' => 'Joke',
            ];
        }

        // 💬 Quote
        if (str_contains($msg, 'quote')) {
            $quotes = [
                "\"The only way to do great work is to love what you do.\" — Steve Jobs",
                "\"Code is like humor. When you have to explain it, it's bad.\" — Cory House",
                "\"First, solve the problem. Then, write the code.\" — John Johnson",
                "\"Any fool can write code that a computer can understand. Good programmers write code that humans can understand.\" — Martin Fowler",
            ];
            return [
                'message'   => "💬 " . $quotes[array_rand($quotes)],
                'used_tool' => true,
                'tool_name' => 'Quote',
            ];
        }

        // 👋 Greeting
        if (in_array($msg, ['hi', 'hello', 'hey', 'helo', 'hii'])) {
            return [
                'message'   => "👋 Hello! I'm LarAgent. Ask me about time, weather, math, jokes, or quotes!",
                'used_tool' => false,
                'tool_name' => null,
            ];
        }

        // 🤖 Default AI response
        return [
            'message'   => "🤖 You said: \"$message\"\n\nI can help with: **time**, **weather**, **math** (e.g. 5+3), **random number**, **uppercase/lowercase text**, **jokes**, and **quotes**. Try one!",
            'used_tool' => false,
            'tool_name' => null,
        ];
    }

    private function safeEval(string $expr): float|int
    {
        // Only allow safe math characters
        if (!preg_match('/^[\d\s\+\-\*\/\%\(\)\.]+$/', $expr)) {
            throw new \Exception("Invalid expression");
        }
        return eval("return $expr;");
    }

    private function isSensitiveAction(string $message): bool
    {
        $keywords = ['delete', 'drop', 'admin', 'password', 'hack', 'inject'];
        foreach ($keywords as $word) {
            if (stripos($message, $word) !== false) return true;
        }
        return false;
    }

    // ─── History ────────────────────────────────────────────────
    public function getHistory(Request $request)
    {
        $sessionId = $request->session()->getId();
        $messages = ChatMessage::where('session_id', $sessionId)
            ->where('status', 'approved')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn($msg) => [
                'id'           => $msg->id,
                'user_message' => $msg->user_message,
                'ai_response'  => $msg->ai_response,
                'created_at'   => $msg->created_at->diffForHumans(),
            ]);

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    public function clearHistory(Request $request)
    {
        $deleted = ChatMessage::where('session_id', $request->session()->getId())->delete();
        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

    // ─── Stats ──────────────────────────────────────────────────
    public function getStats()
    {
        return response()->json([
            'success' => true,
            'stats'   => [
                'total_messages'   => ChatMessage::count(),
                'today_messages'   => ChatMessage::whereDate('created_at', today())->count(),
                'tools_used'       => ChatMessage::where('used_tool', true)->count(),
                'avg_response_time'=> round(ChatMessage::avg('response_time_ms') ?? 0, 2),
                'total_users'      => ChatMessage::distinct('session_id')->count(),
            ],
            'recent'  => ChatMessage::latest()->take(10)->get()->map(fn($m) => [
                'id'           => $m->id,
                'user_message' => $m->user_message,
                'ai_response'  => $m->ai_response,
                'used_tool'    => $m->used_tool,
                'tool_name'    => $m->tool_name,
                'time'         => $m->created_at->diffForHumans(),
                'status'       => $m->status,
            ]),
        ]);
    }

    // ─── Tools List ─────────────────────────────────────────────
    public function getToolsList()
    {
        return response()->json([
            ['name' => 'Time',      'description' => 'Get current date and time'],
            ['name' => 'Calculator','description' => 'Math: 5 + 3, 10 * 4'],
            ['name' => 'Weather',   'description' => 'Weather in London'],
            ['name' => 'Random',    'description' => 'Random number 1 to 100'],
            ['name' => 'Joke',      'description' => 'Tell me a joke'],
            ['name' => 'Quote',     'description' => 'Give me a quote'],
            ['name' => 'Text',      'description' => 'Uppercase / lowercase text'],
        ]);
    }

    // ─── Approve ─────────────────────────────────────────────────
    public function approveMessage($id)
    {
        $chat = ChatMessage::findOrFail($id);
        $chat->update(['status' => 'approved', 'is_approved' => true]);
        return response()->json(['success' => true]);
    }

    // ─── Send (POST version) ─────────────────────────────────────
    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);
        return $this->chat($request, $request->message);
    }
}