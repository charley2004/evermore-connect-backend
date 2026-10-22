<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    private const MILESTONE_BONUS = 10000;

    // GET /api/admin/payments/pending?adminKey=...
    public function pendingPayments(): JsonResponse
    {
        $payments = Payment::with('user:id,full_name,email,referral_code')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Payment $p) => [
                'id' => (string) $p->id,
                'reference' => $p->reference,
                'amount' => $p->amount,
                'status' => $p->status,
                'createdAt' => $p->created_at->toIso8601String(),
                'user' => [
                    'id' => (string) $p->user->id,
                    'fullName' => $p->user->full_name,
                    'email' => $p->user->email,
                    'referralCode' => $p->user->referral_code,
                ],
            ]);

        return response()->json(['payments' => $payments]);
    }

    // PATCH /api/admin/payments/{payment}/verify?adminKey=...
    // Marks the payment verified, flips the linked user to verified,
    // and credits the welcome milestone bonus exactly once.
    public function verifyPayment(Payment $payment): JsonResponse
    {
        if ($payment->status === 'success') {
            return response()->json(['error' => 'Payment already verified'], 409);
        }

        $payment->update(['status' => 'success', 'verified_at' => now()]);

        $user = $payment->user;
        $updates = ['status' => 'verified'];

        if (! $user->milestone_bonus_credited) {
            $updates['wallet_balance'] = $user->wallet_balance + self::MILESTONE_BONUS;
            $updates['milestone_bonus_credited'] = true;
        }

        $user->update($updates);

        return response()->json([
            'success' => true,
            'payment' => ['id' => (string) $payment->id, 'status' => $payment->status],
            'user' => $user->fresh()->toPublicArray(),
        ]);
    }
}
