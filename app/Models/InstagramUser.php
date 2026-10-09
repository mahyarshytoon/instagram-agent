<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_id',
        'username',
        'full_name',
        'profile_pic_url',
        'is_follower',
        'score',
        'total_comments',
        'last_seen_at',
        'metadata',
    ];

    protected $casts = [
        'is_follower'    => 'boolean',
        'metadata'       => 'array',
        'last_seen_at'   => 'datetime',
    ];

    // ===== روابط =====

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}