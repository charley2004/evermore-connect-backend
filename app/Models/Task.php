<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    public const TYPE_TRAINING = 'everai_training';
    public const TYPE_JOB_BOARD = 'job_board';

    // Every member always has at least this many open tasks per stream —
    // index()/topUp() refill the pool down to this floor on every request,
    // which is what makes it look like a continuous stream of work.
    private const POOL_SIZE = 6;

    /**
     * Real task content isn't wired up yet (no external task pipeline) —
     * the site promises members are paid per EverAI training task completed
     * and per Outsourced Job Board placement, so until a real source exists
     * these templates stand in for it. Pay ranges are calibrated against the
     * "₦19,880 from 6 completed tasks" example on the homepage hero card.
     */
    private const TEMPLATES = [
        self::TYPE_TRAINING => [
            ['title' => 'Rate AI Response Accuracy', 'description' => 'Review an AI-generated response to a user prompt and rate how accurate, relevant, and helpful it is.'],
            ['title' => 'Compare Two AI Responses', 'description' => 'Given two AI responses to the same prompt, choose which one is more helpful and explain why in a short note.'],
            ['title' => 'Flag Harmful or Incorrect Output', 'description' => 'Review an AI response for factual errors, unsafe suggestions, or policy violations and flag anything that should be corrected.'],
            ['title' => 'Rewrite a Weak AI Response', 'description' => 'Improve a low-quality AI-generated response so it better answers the original prompt.'],
            ['title' => 'Score Conversation Tone', 'description' => 'Review a short AI conversation and score it for tone, clarity, and helpfulness.'],
            ['title' => 'Prompt-Response Relevance Check', 'description' => 'Confirm whether an AI response actually addresses what the user asked, and note any drift from the prompt.'],
        ],
        self::TYPE_JOB_BOARD => [
            ['title' => 'Priority Dataset Review', 'description' => 'High-priority placement: review a batch of AI training examples for a partner project and confirm labeling accuracy.'],
            ['title' => 'Outsourced Content Accuracy Audit', 'description' => 'Fact-check a set of AI-assisted content pieces against source material for an external client project.'],
            ['title' => 'Specialist Feedback Placement', 'description' => 'Apply domain expertise to review AI outputs in a specialist subject area for a priority external placement.'],
            ['title' => 'Rapid-Turnaround QA Batch', 'description' => 'Time-sensitive placement requiring fast, accurate review of a batch of AI-generated outputs.'],
        ],
    ];

    private const PAY_RANGES = [
        self::TYPE_TRAINING => [400, 1200],
        self::TYPE_JOB_BOARD => [5000, 12000],
    ];

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'pay_amount',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'pay_amount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tops this user's available pool for $type back up to POOL_SIZE,
     * generating new tasks from the templates above as needed. Called
     * before every listing so the member never runs out of work.
     */
    public static function topUpAvailable(User $user, string $type): void
    {
        $available = static::where('user_id', $user->id)
            ->where('type', $type)
            ->where('status', 'available')
            ->count();

        $templates = self::TEMPLATES[$type] ?? [];
        [$min, $max] = self::PAY_RANGES[$type] ?? [0, 0];

        if (! $templates || $available >= self::POOL_SIZE) {
            return;
        }

        $rows = [];
        $now = now();

        for ($i = $available; $i < self::POOL_SIZE; $i++) {
            $template = $templates[array_rand($templates)];
            $rows[] = [
                'user_id' => $user->id,
                'type' => $type,
                'title' => $template['title'],
                'description' => $template['description'],
                'pay_amount' => random_int($min * 100, $max * 100) / 100,
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        static::insert($rows);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'payAmount' => (float) $this->pay_amount,
            'status' => $this->status,
            'completedAt' => $this->completed_at?->toIso8601String(),
        ];
    }
}
