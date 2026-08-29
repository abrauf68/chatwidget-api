<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Hit this straight from a browser after deploying — clears every Laravel
 * cache layer (config, route, view, application) plus OPcache, so you
 * don't need SSH/artisan access to pick up a fresh .env or code change.
 *
 * Usage: https://chat-api.club21mall.com/system/clear-cache
 * Add   ?optimize=1   to also re-cache config+routes afterwards (only do
 * this once .env is finalized — a cached config freezes today's .env
 * values, so future .env edits won't take effect until cleared again).
 */
Route::get('/system/clear-cache', function () {
    $ran = [];
    foreach (['cache:clear', 'config:clear', 'route:clear', 'view:clear'] as $command) {
        Artisan::call($command);
        $ran[] = $command;
    }

    if (request()->boolean('optimize')) {
        foreach (['config:cache', 'route:cache'] as $command) {
            Artisan::call($command);
            $ran[] = $command;
        }
    }

    if (function_exists('opcache_reset')) {
        opcache_reset();
        $ran[] = 'opcache_reset';
    }

    return response()->json(['cleared' => $ran, 'at' => now()->toDateTimeString()]);
});
