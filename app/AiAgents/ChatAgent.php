<?php

namespace App\AiAgents;

use LarAgent\Agent;
use LarAgent\Attributes\Tool;
use Illuminate\Support\Facades\Cache;
use OpenAI\Exceptions\RateLimitException;

class ChatAgent extends Agent
{
    protected $model = "gpt-3.5-turbo";
    protected $history = "in_memory";
    protected $provider = "default";

    public function instructions(): string
    {
        return "You are a helpful AI assistant in a Laravel application.";
    }

    public function prompt($message): string
    {
        return "User says: " . $message;
    }

    #[Tool('Current server time')]
    public function timeTool()
    {
        return now()->toDateTimeString();
    }

    /**
     * Safe respond method with caching & fallback
     */
    public function safeRespond($message)
    {
        $cacheKey = 'chat_response_' . md5($message);

        return Cache::remember($cacheKey, 60, function () use ($message) {
            try {
                // Attempt real OpenAI response
                return $this->respond($message);
            } catch (RateLimitException $e) {
                // If rate limit hit, return fallback
                return [
                    'message' => " OpenAI rate limit reached. Showing test response instead.",
                    'meta' => ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'],
                    'timeTool' => now()->toDateTimeString()
                ];
            } catch (\Exception $e) {
                // Any other error
                return [
                    'message' => " Unable to process request. Showing test response.",
                    'meta' => ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'],
                    'timeTool' => now()->toDateTimeString()
                ];
            }
        });
    }
}