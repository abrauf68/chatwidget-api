<?php

use App\Http\Controllers\Api\V1\AgentBroadcastAuthController;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatSessionController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\Widget\WidgetBroadcastAuthController;
use App\Http\Controllers\Api\V1\Widget\WidgetConfigController;
use App\Http\Controllers\Api\V1\Widget\WidgetMessageController;
use App\Http\Controllers\Api\V1\Widget\WidgetSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public, widget-facing (site_key + domain verified by middleware) ──
    Route::prefix('widget')->middleware('widget.site')->group(function () {
        Route::get('/config', [WidgetConfigController::class, 'show']);
        Route::post('/start-session', [WidgetSessionController::class, 'store']);
        Route::post('/send-message', [WidgetMessageController::class, 'store']);
        Route::post('/upload-attachment', [WidgetMessageController::class, 'uploadAttachment']);
        Route::get('/session/{token}', [WidgetSessionController::class, 'show']);
        Route::post('/broadcasting/auth', [WidgetBroadcastAuthController::class, 'authorizeChannel']);
    });

    // ── Auth ──
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ── Protected: agent dashboard (Sanctum) ──
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/broadcasting/auth', [AgentBroadcastAuthController::class, 'authorizeChannel']);

        Route::apiResource('sites', SiteController::class)->except(['show'])->parameters(['sites' => 'site']);
        Route::get('/sites/{site}', [SiteController::class, 'show']);
        Route::get('/sites/{site}/chats', [ChatSessionController::class, 'indexForSite']);

        Route::get('/chats/{chat}', [ChatSessionController::class, 'show']);
        Route::post('/chats/{chat}/reply', [ChatSessionController::class, 'reply']);
        Route::post('/chats/{chat}/attachments', [ChatSessionController::class, 'uploadAttachment']);
        Route::post('/chats/{chat}/claim', [ChatSessionController::class, 'claim']);
        Route::post('/chats/{chat}/transfer', [ChatSessionController::class, 'transfer']);
        Route::post('/chats/{chat}/close', [ChatSessionController::class, 'close']);

        Route::get('/agents', [AgentController::class, 'index']);
        Route::post('/agents', [AgentController::class, 'store']);
        Route::put('/agents/{agent}/sites', [AgentController::class, 'updateSites']);
    });
});
