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
        // Only the /api/* JSON endpoints get a forced 401 JSON body instead
        // of Laravel's default redirect-to-"login" behavior. Everything else
        // (the Filament panel) needs the default handling so it can redirect
        // to its own session-based login page.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'Invalid or expired session'], 401);
            }
        });
    })->create();
