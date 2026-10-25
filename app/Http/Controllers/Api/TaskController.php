<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    // GET /api/tasks?type=everai_training|job_board  (auth:sanctum)
    // Only verified members can see/claim tasks — unverified accounts get
    // an empty pool rather than a 403, so the locked dashboard can still
    // render the preview UI without a special case.
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([Task::TYPE_TRAINING, Task::TYPE_JOB_BOARD])],
        ]);

        $user = $request->user();

        if ($user->status !== 'verified') {
            return response()->json(['tasks' => []]);
        }

        Task::topUpAvailable($user, $data['type']);

        $tasks = $user->tasks()
            ->where('type', $data['type'])
            ->where('status', 'available')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Task $t) => $t->toPublicArray());

        return response()->json(['tasks' => $tasks]);
    }

    // POST /api/tasks/{task}/submit  (auth:sanctum)
    // Auto-approved: completing a task credits the wallet immediately,
    // no admin review step.
    public function submit(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        if ($task->user_id !== $user->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($task->status !== 'available') {
            return response()->json(['error' => 'Task already completed'], 409);
        }

        DB::transaction(function () use ($task, $user) {
            $task->update(['status' => 'completed', 'completed_at' => now()]);
            $user->update(['wallet_balance' => $user->wallet_balance + $task->pay_amount]);
        });

        return response()->json([
            'success' => true,
            'task' => $task->fresh()->toPublicArray(),
            'user' => $user->fresh()->toPublicArray(),
        ]);
    }

    // GET /api/tasks/history?period=today|week|month|all  (auth:sanctum)
    public function history(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['sometimes', Rule::in(['today', 'week', 'month', 'all'])],
        ]);

        $query = $request->user()->tasks()
            ->where('status', 'completed')
            ->orderByDesc('completed_at');

        match ($data['period'] ?? 'all') {
            'today' => $query->whereDate('completed_at', today()),
            'week' => $query->where('completed_at', '>=', now()->startOfWeek()),
            'month' => $query->where('completed_at', '>=', now()->startOfMonth()),
            default => null,
        };

        $tasks = $query->get();

        return response()->json([
            'tasks' => $tasks->map(fn (Task $t) => $t->toPublicArray()),
            'totalAmount' => (float) $tasks->sum('pay_amount'),
            'count' => $tasks->count(),
        ]);
    }
}
