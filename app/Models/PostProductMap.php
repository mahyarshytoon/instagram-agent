<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostProductMap extends Model
{
    use HasFactory;

    protected $table = 'post_product_map';

    protected $fillable = [
        'instagram_post_id',
        'product_id',
    ];
}