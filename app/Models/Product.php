<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'old_price',
        'currency',
        'stock',
        'category',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'old_price' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata'  => 'array',
    ];

    // ===== روابط =====

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            InstagramPost::class,
            'post_product_map',
            'product_id',
            'instagram_post_id'
        )->withTimestamps();
    }
}