<?php
// app/Models/FakeMetaRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakeMetaRequest extends Model
{
    protected $fillable = ['method', 'endpoint', 'headers', 'payload', 'response'];

    protected $casts = [
        'headers'  => 'array',
        'payload'  => 'array',
        'response' => 'array',
    ];
}