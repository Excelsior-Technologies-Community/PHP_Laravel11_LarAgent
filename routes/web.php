<?php

use Illuminate\Support\Facades\Route;
use App\AiAgents\ChatAgent;
use App\Http\Controllers\ChatController;
use App\Models\ChatMessage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chat/{message}', [ChatController::class, 'chat']);

Route::get('/chat-history', function () {
    return ChatMessage::latest()->take(20)->get();
});