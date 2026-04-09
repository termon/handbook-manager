<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Handbook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Handbook>
 */
class HandbookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'user_id' => User::factory()->author(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
        ];
    }
}
