<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name'        => 'محصول ' . fake()->word(),
            'description' => fake()->paragraph(),
            'price'       => fake()->numberBetween(50000, 5000000),
            'old_price'   => null,
            'currency'    => 'IRR',
            'stock'       => fake()->numberBetween(0, 100),
            'category'    => fake()->randomElement(['پوشاک', 'دیجیتال', 'خانه', 'زیبایی']),
            'is_active'   => true,
            'metadata'    => null,
        ];
    }
}