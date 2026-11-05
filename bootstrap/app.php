<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            // Twilio Voice webhooks (POST from Twilio; no session CSRF token)
            'twilio/*',
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The app acts on these status codes, so never answer it with an HTML redirect.
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/v1/live-location') || $request->expectsJson();
        });
    })->create();
