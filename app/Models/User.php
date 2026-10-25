<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone',
        'niche',
        'status',
        'referral_code',
        'wallet_balance',
        'milestone_bonus_credited',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
            'milestone_bonus_credited' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Only accounts explicitly flagged is_admin can reach the Filament
     * panel — a regular customer signup can never land here, even if their
     * session/credentials are compromised.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Filament's UI (avatar, user menu, etc.) expects a "name" — this model
     * stores it as full_name instead, so this is how Filament finds it.
     */
    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    /**
     * Shape returned to the frontend — mirrors the field names
     * js/dashboard.js and js/auth.js already expect.
     */
    public function toPublicArray(): array
    {
        return [
            'id' => (string) $this->id,
            'fullName' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'niche' => $this->niche,
            'status' => $this->status,
            'referralCode' => $this->referral_code,
            'walletBalance' => (float) $this->wallet_balance,
            'joinedAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
