<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // POST /api/auth/signup
    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:30',
            'niche' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $email = strtolower(trim($request->input('email')));

        if (User::where('email', $email)->exists()) {
            return response()->json(['error' => 'An account with this email already exists'], 409);
        }

        $user = User::create([
            'full_name' => trim($request->input('fullName')),
            'email' => $email,
            'password' => Hash::make($request->input('password')),
            'phone' => trim((string) $request->input('phone', '')),
            'niche' => trim((string) $request->input('niche', '')),
            'referral_code' => $this->generateReferralCode(),
            'status' => 'pending_payment',
        ]);

        $token = $user->createToken('evermore')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user->toPublicArray()], 201);
    }

    // POST /api/auth/login
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        if (! $user || ! $user->password || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        $token = $user->createToken('evermore')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user->toPublicArray()]);
    }

    // GET /api/auth/me  (auth:sanctum)
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->toPublicArray()]);
    }

    // POST /api/auth/logout  (auth:sanctum)
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    private function generateReferralCode(): string
    {
        do {
            $code = 'EVM-'.strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
