<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'site',
        'name',
        'location',
        'rating',
        'body',
        'approved',
    ];

    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
            'rating' => 'integer',
        ];
    }
}
