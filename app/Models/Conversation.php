<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_user_id',
        'instagram_post_id',
        'status',
        'message_count',
        'last_message_at',
        'started_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'started_at'      => 'datetime',
        'closed_at'       => 'datetime',
    ];

    // ===== روابط =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(InstagramUser::class, 'instagram_user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(InstagramPost::class, 'instagram_post_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }
}