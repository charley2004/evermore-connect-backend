<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.key' => \App\Http\Middleware\RequireAdminKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Pure JSON API, no web login route — always respond with 401 JSON
        // instead of Laravel's default redirect-to-"login"-route behavior.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            return response()->json(['error' => 'Invalid or expired session'], 401);
        });
    })->create();
