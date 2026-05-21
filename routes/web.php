<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::prefix('api/chat')->group(function () {
    Route::get('/message/{message}', [ChatController::class, 'chat']);
    Route::post('/send', [ChatController::class, 'sendMessage']);
    Route::get('/history', [ChatController::class, 'getHistory']);
    Route::delete('/history', [ChatController::class, 'clearHistory']);
    Route::get('/stats', [ChatController::class, 'getStats']);
    Route::get('/tools', [ChatController::class, 'getToolsList']);
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');