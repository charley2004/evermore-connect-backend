<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'reference',
        'status',
        'metadata',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    private const MILESTONE_BONUS = 10000;

    /**
     * Marks this payment verified, flips the linked user to verified, and
     * credits the welcome milestone bonus exactly once. Shared by the admin
     * API endpoint and the Filament payments resource so both stay in sync.
     */
    public function verifyAndCreditUser(): User
    {
        if ($this->status === 'success') {
            throw new \RuntimeException('Payment already verified');
        }

        $this->update(['status' => 'success', 'verified_at' => now()]);

        $user = $this->user;
        $updates = ['status' => 'verified'];

        if (! $user->milestone_bonus_credited) {
            $updates['wallet_balance'] = $user->wallet_balance + self::MILESTONE_BONUS;
            $updates['milestone_bonus_credited'] = true;
        }

        $user->update($updates);

        return $user->fresh();
    }
}
