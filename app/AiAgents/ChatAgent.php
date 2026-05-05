<?php

namespace App\AiAgents;

use LarAgent\Agent;
use LarAgent\Attributes\Tool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;

class ChatAgent extends Agent
{
    // ✅ Groq working model
    protected $provider = "groq";
    protected $model = "llama-3.1-8b-instant";

    protected $history = "in_memory";

    public function instructions(): string
    {
        return "You are a helpful AI assistant. 
Give short, clear and useful answers. 
Use tools when required.";
    }

    public function prompt($message): string
    {
        return "User: {$message}\nAI:";
    }

    // =========================
    // TOOL 1: TIME
    // =========================
    #[Tool('Get current server time')]
    public function timeTool(array $input = [])
    {
        return now()->toDateTimeString();
    }

    // =========================
    // TOOL 2: UPPERCASE
    // =========================
    #[Tool('Convert text to uppercase')]
    public function toUpper(array $input = [])
    {
        $text = $input['text'] ?? '';
        return strtoupper($text);
    }

    // =========================
    // MAIN RESPONSE FUNCTION
    // =========================
  public function safeRespond($message)
{
    $cacheKey = 'chat_response_' . md5($message);

    return Cache::remember($cacheKey, 60, function () use ($message) {
        try {

            $lower = strtolower($message);

            // ✅ FORCE TOOL: TIME
            if (str_contains($lower, 'time')) {
                $response = $this->timeTool();
            }

            // ✅ FORCE TOOL: UPPERCASE
            elseif (str_contains($lower, 'uppercase')) {
                preg_match('/uppercase (.*)/i', $message, $matches);
                $text = $matches[1] ?? '';
                $response = $this->toUpper(['text' => $text]);
            }

            // 🤖 NORMAL AI RESPONSE
            else {
                $response = $this->respond($message);
            }

            // 💾 SAVE DB
            ChatMessage::create([
                'user_message' => $message,
                'ai_response' => is_array($response) ? json_encode($response) : $response,
                'provider' => $this->provider,
                'model' => $this->model,
            ]);

            return [
                'status' => 'success',
                'message' => $response,
                'meta' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                ],
                'time' => now()->toDateTimeString()
            ];

        } catch (\Throwable $e) {

            Log::error($e->getMessage());

            return [
                'status' => 'fallback',
                'message' => "AI error occurred",
                'meta' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    });
}
}