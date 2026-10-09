<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_post_id',
        'caption',
        'media_url',
        'media_type',
        'permalink',
        'posted_at',
        'metadata',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'metadata'  => 'array',
    ];

    // ===== روابط =====

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'post_product_map',
            'instagram_post_id',
            'product_id'
        )->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}