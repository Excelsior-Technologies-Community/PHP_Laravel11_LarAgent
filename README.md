# PHP_Laravel11_LarAgent

## Project Description

PHP_Laravel11_LarAgent is a Laravel 11 project that integrates LarAgent, a powerful AI assistant package for Laravel.
It allows developers to create custom AI agents that can respond to messages, perform tasks, and integrate AI features directly into Laravel applications.

With LarAgent, AI functionality can be added just like creating Laravel models or controllers.
It supports multiple AI providers like OpenAI, Gemini, Claude, Groq, Ollama, and more.


## Features

1. Multi-Provider Support – Works with OpenAI, Gemini, Claude, Groq, Ollama, etc.

2. Safe & Cached Responses – Handles errors, rate limits, and caches replies.

3. Tools Integration – Agents can have built-in helper tools like server time.

4. Configurable – Centralized AI settings in config/laragent.php.

5. JSON API Ready – Returns structured responses for front-end or API use.

6. Laravel Friendly – Agents behave like normal Laravel classes, easy to call from routes or controllers.



## Technologies Used

1. PHP 8.2 – Backend programming language

2. Laravel 11 – Modern PHP framework for web applications

3. MySQL – Database (optional, for persistence)

4. Composer – Dependency management

5. LarAgent – AI agent package for Laravel

6. OpenAI API – AI provider for generating responses

7. Blade – Laravel templating engine for views

8. JSON – API response format for chat responses

9. Cache – Laravel caching for AI response optimization

---



## Installation Steps


---


## STEP 1: Create Laravel 11 Project

### Open terminal / CMD and run:

```
composer create-project laravel/laravel PHP_Laravel11_LarAgent "11.*"

```

### Go inside project:

```
cd PHP_Laravel11_LarAgent

```

#### Explanation:

Creates a fresh Laravel 11 project and navigates into the project folder. This is the base for your LarAgent setup.



## STEP 2: Database Setup (Optional)

### Update database details:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel11_LarAgent
DB_USERNAME=root
DB_PASSWORD=

```

### Create database in MySQL / phpMyAdmin:

```
Database name: laravel11_LarAgent

```

#### Explanation:

Configures Laravel to connect with MySQL. You can create the database laravel11_LarAgent in phpMyAdmin.






## STEP 3: Install LarAgent Package

### LarAgent is a Laravel package that lets you define AI agents like Laravel models. Install it with Composer:

```
composer require maestroerror/laragent --ignore-platform-reqs

```

### After installing, publish the config:

```
php artisan vendor:publish --tag="laragent-config"

```

### That creates a config/laragent.php file you can edit

```
'providers' => [
    'default' => [
        'label' => 'openai',
        'api_key' => env('OPENAI_API_KEY'),
        'driver' => \LarAgent\Drivers\OpenAi\OpenAiDriver::class,
        'default_truncation_threshold' => 50000,
        'default_max_completion_tokens' => 10000,
        'default_temperature' => 1,
    ],

```


#### Explanation:

Installs LarAgent package and publishes its configuration file config/laragent.php where you define providers, drivers, and AI settings.






## STEP 4: Set Your OpenAI API Key

#### OpenAI or any compatible API is required for the agents to work.

### In your .env:

```
OPENAI_API_KEY=your_openai_api_key_here

```

#### Explanation:

The API key allows LarAgent to communicate with OpenAI or any compatible AI provider.





## STEP 5: Create Agent

### Run:

```
php artisan make:agent ChatAgent

```

### Open: app/AiAgents/ChatAgent.php

```
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

```

#### Explanation:

This is your custom AI agent class. Here you define AI instructions, prompts, and optional tools like time or string manipulation.




## STEP 6: Define Route

### routes/web.php:

```
<?php

use Illuminate\Support\Facades\Route;
use App\AiAgents\ChatAgent;

Route::get('/', function () {
    return view('welcome');
});

// Chat route with caching & safe response
Route::get('/chat/{message}', function ($message) {
    $agent = ChatAgent::for("default_chat");
    $response = $agent->safeRespond($message);

    return response()->json($response);
});

```

#### Explanation:

Creates a route to test your agent. Access /chat/{message} to get AI responses in JSON format.




## STEP 7: Test Project

### Run Server

```
php artisan serve

```

### Then open in browser:

```
http://127.0.0.1:8000/chat/Hello

```

#### Explanation:

Starts the Laravel server.

Visiting /chat/Hello will call your ChatAgent and return a structured AI response.


## Expected Output:


<img width="1919" height="907" alt="Screenshot 2026-03-03 181133" src="https://github.com/user-attachments/assets/9e2ad5b4-67f6-4562-b900-6202ff6561b0" />



---

# Project Folder Structure:

```
PHP_Laravel11_LarAgent/
├── app/
│   └── AiAgents/
│       └── ChatAgent.php
├── config/
│   └── laragent.php
├── routes/
│   └── web.php
├── resources/
│   └── views/
│       └── welcome.blade.php
├── .env
├── artisan
├── composer.json
└── public/

```
