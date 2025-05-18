<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
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
            'member_number' => strtoupper('MBR' . fake()->unique()->numberBetween(1000, 9999)),
            'name' => fake()->name(),
            'birth_date' => fake()->date('Y-m-d'),
            'stock' => fake()->numberBetween(0, 5),
        ];
    }
}
