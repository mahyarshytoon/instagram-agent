<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\InstagramPost;
use App\Models\InstagramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'instagram_user_id' => InstagramUser::factory(),
            'instagram_post_id' => InstagramPost::factory(),
            'status'            => 'active',
            'message_count'     => 0,
            'last_message_at'   => now(),
            'started_at'        => now(),
            'closed_at'         => null,
        ];
    }
}