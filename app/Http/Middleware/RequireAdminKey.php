<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ports the Node backend's isAdmin() check: accept the admin key either
 * as ?adminKey=... (matches the existing admin/index.html panel exactly)
 * or as an X-Admin-Key header.
 */
class RequireAdminKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = config('app.admin_key');
        $provided = $request->query('adminKey') ?? $request->header('X-Admin-Key');

        if (! $key || $provided !== $key) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
