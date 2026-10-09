<?php

namespace Database\Factories;

use App\Models\InstagramPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstagramPostFactory extends Factory
{
    protected $model = InstagramPost::class;

    public function definition(): array
    {
        return [
            'instagram_post_id' => 'post_' . fake()->unique()->numerify('##########'),
            'caption'           => fake()->sentence(10),
            'media_url'         => fake()->imageUrl(800, 800, 'business'),
            'media_type'        => fake()->randomElement(['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM']),
            'permalink'         => fake()->url(),
            'posted_at'         => fake()->dateTimeBetween('-30 days', 'now'),
            'metadata'          => null,
        ];
    }
}