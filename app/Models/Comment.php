<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_comment_id',
        'instagram_post_id',
        'instagram_user_id',
        'conversation_id',          // ✅ اضافه شد
        'parent_comment_id',
        'text',
        'sanitized_text',
        'status',
        'rejection_reason',
        'processed_at',             // ✅ اضافه شد
        'is_spam',
        'spam_score',
        'metadata',
    ];

    protected $casts = [
        'is_spam'      => 'boolean',
        'metadata'     => 'array',
        'processed_at' => 'datetime',   // ✅ اضافه شد
    ];

    // ===== روابط =====

    public function post(): BelongsTo
    {
        return $this->belongsTo(InstagramPost::class, 'instagram_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(InstagramUser::class, 'instagram_user_id');
    }
	
	public function instagramUser(): BelongsTo
	{
    return $this->belongsTo(InstagramUser::class, 'instagram_user_id');
	}

    public function conversation(): BelongsTo    // ✅ اضافه شد
    {
        return $this->belongsTo(Conversation::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }
}