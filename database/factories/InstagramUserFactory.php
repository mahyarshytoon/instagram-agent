<?php

namespace Database\Factories;

use App\Models\InstagramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstagramUserFactory extends Factory
{
    protected $model = InstagramUser::class;

    public function definition(): array
    {
        return [
            'instagram_id'   => 'ig_' . fake()->unique()->numerify('##########'),
            'username'       => fake()->userName(),
            'full_name'      => fake()->name(),
            'profile_pic_url' => fake()->imageUrl(200, 200, 'people'),
            'is_follower'    => fake()->boolean(70),  // ۷۰٪ شانس فالوور بودن
            'score'          => fake()->numberBetween(0, 100),
            'total_comments' => fake()->numberBetween(0, 50),
            'last_seen_at'   => now(),
            'metadata'       => null,
        ];
    }
}