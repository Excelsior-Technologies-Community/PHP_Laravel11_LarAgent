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