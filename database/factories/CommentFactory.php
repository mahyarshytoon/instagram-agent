<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\InstagramPost;
use App\Models\InstagramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'instagram_comment_id' => 'comment_' . fake()->unique()->numerify('##########'),
            'instagram_post_id'    => InstagramPost::factory(),
            'instagram_user_id'    => InstagramUser::factory(),
            'conversation_id'      => null,
            'parent_comment_id'    => null,
            'text'                 => 'قیمت این محصول چنده؟',
            'sanitized_text'       => 'قیمت این محصول چنده؟',
            'status'               => 'pending',
            'rejection_reason'     => null,
            'is_spam'              => false,
            'spam_score'           => 0,
            'processed_at'         => null,
            'metadata'             => null,
        ];
    }
}