<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['service' => 'evermore-connect-api', 'status' => 'ok']);
});

// Matches the old Node backend's GET /health exactly (in addition to
// Laravel's own built-in /up health check).
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// This is a pure JSON API with no web login page. Laravel's auth
// middleware calls route('login') to redirect unauthenticated requests
// that didn't ask for JSON (e.g. a client that forgot an Accept header)
// — without this named route, that lookup itself throws instead of
// cleanly reporting 401. Named 'login' purely so that lookup resolves.
Route::get('/login', function () {
    return response()->json(['error' => 'Invalid or expired session'], 401);
})->name('login');
