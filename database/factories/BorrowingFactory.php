<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Borrowing>
 */
class BorrowingFactory extends Factory
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
            'member_id' => Member::inRandomOrder()->first()->id ?? Member::factory(),
            'book_id' => Book::inRandomOrder()->first()->id ?? Book::factory(),
            'borrowed_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
        ];
    }
}
