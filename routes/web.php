<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AnalyticsController;

// ─── Home ────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('welcome');
})->name('home');

// ─── Chat API Routes ─────────────────────────────────────────────
Route::prefix('api/chat')->group(function () {
    Route::get('/message/{message}', [ChatController::class, 'chat']);
    Route::post('/send',             [ChatController::class, 'sendMessage']);
    Route::get('/history',           [ChatController::class, 'getHistory']);
    Route::delete('/history',        [ChatController::class, 'clearHistory']);
    Route::get('/stats',             [ChatController::class, 'getStats']);
    Route::get('/tools',             [ChatController::class, 'getToolsList']);
    Route::post('/approve/{id}',     [ChatController::class, 'approveMessage']);
});

// ─── Admin Routes ─────────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('/analytics',      [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/approvals',      [AnalyticsController::class, 'approvals'])->name('analytics.approvals');
    Route::post('/approve/{id}',  [AnalyticsController::class, 'approve'])->name('analytics.approve');
});

// ─── Wildcard (SPA fallback) - ALWAYS LAST ────────────────────────
// Note: Aa route badhaj undefined routes ne welcome view par redirect kare che
// Ene HAMESHA LAST ma rakhvo - upar na routes ne capture na kare
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');