<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ported 1:1 from the old Node backend's review.controller.js — same
 * routes, same request/response shapes — because this API is embedded
 * live on optinex.ng, incossify.ng, and glamournigeria.ng via the
 * reviews widget. Do not change field names or status codes here
 * without updating those sites too.
 */
class ReviewController extends Controller
{
    // POST /api/reviews
    public function submit(Request $request): JsonResponse
    {
        $site = $request->input('site');
        $name = $request->input('name');
        $location = $request->input('location');
        $rating = $request->input('rating');
        $body = $request->input('body');

        if (! $site || ! $name || ! $body || ! $rating) {
            return response()->json(['error' => 'site, name, body and rating are required'], 400);
        }

        $r = (int) $rating;
        if ($r < 1 || $r > 5) {
            return response()->json(['error' => 'rating must be 1–5'], 400);
        }
        if (strlen($body) > 1000) {
            return response()->json(['error' => 'Review too long (max 1000 chars)'], 400);
        }

        $review = Review::create([
            'site' => strtolower(trim($site)),
            'name' => substr(trim($name), 0, 80),
            'location' => substr(trim((string) $location), 0, 100),
            'rating' => $r,
            'body' => trim($body),
            'approved' => false,
        ]);

        return response()->json(['success' => true, 'id' => (string) $review->id], 201);
    }

    // GET /api/reviews?site=evermore
    public function list(Request $request): JsonResponse
    {
        $site = $request->query('site');
        if (! $site) {
            return response()->json(['error' => 'site param required'], 400);
        }

        $reviews = Review::where('site', strtolower(trim($site)))
            ->where('approved', true)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'location', 'rating', 'body', 'created_at'])
            ->map(fn (Review $r) => [
                'id' => (string) $r->id,
                'name' => $r->name,
                'location' => $r->location,
                'rating' => $r->rating,
                'body' => $r->body,
                'createdAt' => $r->created_at->toIso8601String(),
            ]);

        return response()->json(['reviews' => $reviews]);
    }

    // GET /api/reviews/pending  (admin only)
    public function pending(): JsonResponse
    {
        $reviews = Review::where('approved', false)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Review $r) => [
                'id' => (string) $r->id,
                'site' => $r->site,
                'name' => $r->name,
                'location' => $r->location,
                'rating' => $r->rating,
                'body' => $r->body,
                'approved' => $r->approved,
                'createdAt' => $r->created_at->toIso8601String(),
            ]);

        return response()->json(['reviews' => $reviews]);
    }

    // PATCH /api/reviews/{review}/approve  (admin only)
    public function approve(Review $review): JsonResponse
    {
        $review->update(['approved' => true]);

        return response()->json(['success' => true, 'review' => $review]);
    }

    // DELETE /api/reviews/{review}  (admin only)
    public function reject(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json(['success' => true]);
    }
}
