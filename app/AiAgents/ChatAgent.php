<?php

namespace App\AiAgents;

use LarAgent\Agent;
use LarAgent\Attributes\Tool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;

class ChatAgent extends Agent
{
    protected $provider = "groq";
    protected $model = "llama-3.1-8b-instant";
    protected $history = "in_memory";
    
    private $sessionId;
    private $startTime;

    public function __construct($sessionId = null)
    {
        parent::__construct();
        $this->sessionId = $sessionId ?? session()->getId();
    }

    public function instructions(): string
    {
        return "You are a helpful AI assistant named LarAgent. 
        Give short, clear and useful answers. 
        Be friendly and conversational.
        Use tools when appropriate.";
    }

    public function prompt($message): string
    {
        return "User: {$message}\nAI:";
    }

    public function safeRespond($message, $ipAddress = null)
    {
        $this->startTime = microtime(true);
        
        try {
            $lower = strtolower($message);
            $response = null;
            $usedTool = false;
            $toolUsed = null;

            if (str_contains($lower, 'time') || str_contains($lower, 'clock') || str_contains($lower, 'date')) {
                $response = now()->format('l, F j, Y g:i A');
                $usedTool = true;
                $toolUsed = 'getCurrentTime';
            } 
            elseif (str_contains($lower, 'uppercase') || str_contains($lower, 'lowercase') || 
                    str_contains($lower, 'capitalize') || str_contains($lower, 'reverse')) {
                preg_match('/(uppercase|lowercase|capitalize|reverse)\s+(.*)/i', $message, $matches);
                $action = strtolower($matches ?? 'uppercase');
                $text = $matches ?? '';
                
                if (empty($text)) {
                    $response = "Please provide text to {$action}. Example: '{$action} hello world'";
                } else {
                    $response = match($action) {
                        'uppercase' => strtoupper($text),
                        'lowercase' => strtolower($text),
                        'capitalize' => ucwords($text),
                        'reverse' => strrev($text),
                        default => $text,
                    };
                }
                $usedTool = true;
                $toolUsed = 'transformText';
            } 
            elseif (preg_match('/(\d+)\s*([\+\-\*\/x])\s*(\d+)/', $message, $mathMatches)) {
                $a = floatval($mathMatches);
                $op = $mathMatches;
                $b = floatval($mathMatches);
                
                $response = match($op) {
                    '+' => $a + $b,
                    '-' => $a - $b,
                    '*' => $a * $b,
                    'x' => $a * $b,
                    '/' => $b != 0 ? $a / $b : 'Division by zero error',
                    default => 'Invalid operation',
                };
                $usedTool = true;
                $toolUsed = 'calculate';
            } 
            elseif (str_contains($lower, 'random') && str_contains($lower, 'number')) {
                preg_match('/random\s+number\s+(\d+)\s+to\s+(\d+)/i', $message, $randMatches);
                $min = isset($randMatches) ? intval($randMatches) : 1;
                $max = isset($randMatches) ? intval($randMatches) : 100;
                $randomNum = rand($min, $max);
                $response = "Random number between {$min} and {$max}: {$randomNum}";
                $usedTool = true;
                $toolUsed = 'randomNumber';
            } 
            elseif (str_contains($lower, 'weather')) {
                preg_match('/weather in (\w+)/i', $message, $weatherMatches);
                $city = isset($weatherMatches) ? ucfirst(strtolower($weatherMatches)) : 'Unknown';
                
                $weatherData = [
                    'New York' => ['temp' => '22°C', 'condition' => 'Sunny', 'humidity' => '45%'],
                    'London' => ['temp' => '15°C', 'condition' => 'Cloudy', 'humidity' => '70%'],
                    'Tokyo' => ['temp' => '28°C', 'condition' => 'Clear', 'humidity' => '60%'],
                    'Paris' => ['temp' => '18°C', 'condition' => 'Rainy', 'humidity' => '80%'],
                    'Sydney' => ['temp' => '25°C', 'condition' => 'Partly Cloudy', 'humidity' => '55%'],
                ];
                
                $weather = $weatherData[$city] ?? ['temp' => 'N/A', 'condition' => 'Unknown', 'humidity' => 'N/A'];
                $response = "Weather in {$city}: {$weather['temp']}, {$weather['condition']}, Humidity: {$weather['humidity']}";
                $usedTool = true;
                $toolUsed = 'getWeather';
            } 
            elseif (str_contains($lower, 'quote') || str_contains($lower, 'inspire')) {
                $quotes = [
                    "The only limit to our realization of tomorrow is our doubts of today. - Franklin D. Roosevelt",
                    "Do what you can, with what you have, where you are. - Theodore Roosevelt",
                    "Success is not final, failure is not fatal: it is the courage to continue that counts. - Winston Churchill",
                    "The future belongs to those who believe in the beauty of their dreams. - Eleanor Roosevelt",
                    "It does not matter how slowly you go as long as you do not stop. - Confucius",
                ];
                $response = $quotes[array_rand($quotes)];
                $usedTool = true;
                $toolUsed = 'getQuote';
            } 
            elseif (str_contains($lower, 'joke') || str_contains($lower, 'funny')) {
                $jokes = [
                    "Why don't scientists trust atoms? Because they make up everything!",
                    "What do you call a fake noodle? An impasta!",
                    "Why did the scarecrow win an award? He was outstanding in his field!",
                    "What do you call a bear with no teeth? A gummy bear!",
                    "Why don't eggs tell jokes? They'd crack each other up!",
                ];
                $response = $jokes[array_rand($jokes)];
                $usedTool = true;
                $toolUsed = 'getJoke';
            } 
            elseif (str_contains($lower, 'hello') || str_contains($lower, 'hi ') || $lower === 'hi') {
                $greetings = [
                    "Hello! 👋 How can I help you today?",
                    "Hi there! 😊 What can I do for you?",
                    "Hey! 👋 Ready to assist you!",
                    "Greetings! 🌟 How may I help?",
                ];
                $response = $greetings[array_rand($greetings)];
            } 
            else {
                try {
                    $response = $this->respond($message);
                } catch (\Exception $e) {
                    $response = "I'm here to help! You can ask me about time, weather, math, or try: 'uppercase hello', 'tell me a joke', or 'give me a quote'";
                }
            }
            
            $responseTime = (microtime(true) - $this->startTime) * 1000;
            
            $status = (stripos($message, 'delete') !== false || stripos($message, 'drop') !== false) ? 'pending' : 'approved';

            try {
                $chat = ChatMessage::create([
                    'session_id' => $this->sessionId,
                    'ip_address' => $ipAddress ?? request()->ip(),
                    'user_message' => $message,
                    'ai_response' => is_array($response) ? json_encode($response) : $response,
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'response_time_ms' => round($responseTime, 2),
                    'used_tool' => $usedTool,
                    'tool_name' => $toolUsed,
                    'status' => $status,
                    'is_approved' => ($status === 'approved'),
                    'token_usage' => 50,
                    'model_name' => $this->model,
                    'metadata' => [
                        'user_agent' => request()->userAgent(),
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to save message: ' . $e->getMessage());
            }
            
            Cache::forget('chat_stats');
            
            return [
                'status' => 'success',
                'message' => $response,
                'used_tool' => $usedTool,
                'tool_name' => $toolUsed,
                'meta' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'response_time_ms' => round($responseTime, 2),
                    'approval_status' => $status
                ],
                'time' => now()->toDateTimeString()
            ];
            
        } catch (\Throwable $e) {
            Log::error('ChatAgent Error: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => "I encountered an error. Please try again.",
                'meta' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                ]
            ];
        }
    }
}