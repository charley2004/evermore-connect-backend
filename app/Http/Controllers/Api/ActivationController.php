<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivationController extends Controller
{
    private const ACTIVATION_AMOUNT = 14000;

    // POST /api/activation/submit  (auth:sanctum)
    // Real counterpart of the old confirmManualPayment() — creates a
    // Payment row and moves the user into awaiting_verification instead
    // of just flipping a localStorage flag.
    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->status === 'verified') {
            return response()->json(['error' => 'Account is already activated'], 409);
        }

        $payment = $user->payments()->create([
            'amount' => self::ACTIVATION_AMOUNT,
            'reference' => 'EVM-PAY-'.strtoupper(Str::random(10)),
            'status' => 'pending',
            'metadata' => [
                'submitted_at' => now()->toIso8601String(),
                'channel' => 'manual_bank_transfer',
            ],
        ]);

        $user->update(['status' => 'awaiting_verification']);

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => (string) $payment->id,
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'status' => $payment->status,
            ],
            'user' => $user->fresh()->toPublicArray(),
        ], 201);
    }
}
