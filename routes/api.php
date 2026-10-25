<?php

use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// ── Auth ───────────────────────────────────────────────
Route::post('/auth/signup', [AuthController::class, 'signup']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ── Activation (real) ─────────────────────────────
    Route::post('/activation/submit', [ActivationController::class, 'submit']);

    // ── Tasks — EverAI Training & Outsourced Job Board ─
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/history', [TaskController::class, 'history']);
    Route::post('/tasks/{task}/submit', [TaskController::class, 'submit']);
});

// ── Reviews — same contract as the old Node backend ───
Route::get('/reviews/pending', [ReviewController::class, 'pending'])->middleware('admin.key');
Route::get('/reviews', [ReviewController::class, 'list']);
Route::post('/reviews', [ReviewController::class, 'submit']);
Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve'])->middleware('admin.key');
Route::delete('/reviews/{review}', [ReviewController::class, 'reject'])->middleware('admin.key');

// ── Admin — payment verification ──────────────────────
Route::middleware('admin.key')->prefix('admin')->group(function () {
    Route::get('/payments/pending', [AdminController::class, 'pendingPayments']);
    Route::patch('/payments/{payment}/verify', [AdminController::class, 'verifyPayment']);
});
