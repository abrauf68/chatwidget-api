<?php

use App\Http\Middleware\VerifyWidgetSite;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'widget.site' => VerifyWidgetSite::class,
        ]);

        $middleware->statefulApi();

        // Both broadcasting-auth endpoints authenticate themselves (one via
        // the Sanctum session guard inside the controller, the other via a
        // signed site_key check) — neither depends on the CSRF token, and
        // Pusher-js's own auth request doesn't carry it the way our axios
        // client does for every other call. Requiring it here only breaks
        // channel subscriptions for agents/visitors on a stateful domain
        // (see CHANGES.md) without adding any real protection.
        $middleware->validateCsrfTokens(except: [
            'api/v1/broadcasting/auth',
            'api/v1/widget/broadcasting/auth',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Force every API error response to be JSON, never an HTML page or
        // redirect (architecture doc 17.6 — mobile clients can't parse HTML).
        $exceptions->shouldRenderJsonWhen(function ($request, $throwable) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
