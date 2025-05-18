<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'title' => fake()->sentence(3),
            'publisher' => fake()->company(),
            'dimensions' => fake()->randomElement(['14x21 cm', '15x23 cm']),
            'stock' => fake()->numberBetween(1, 20),
        ];
    }
}
