<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'keywords',
        'question',
        'answer',
        'priority',
        'is_active',
        'hit_count',
    ];

    protected $casts = [
        'keywords'  => 'array',
        'is_active' => 'boolean',
    ];
}