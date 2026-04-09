<?php

namespace Database\Factories;

use App\Models\Handbook;
use App\Models\HandbookPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HandbookPage>
 */
class HandbookPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));

        return [
            'handbook_id' => Handbook::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'position' => 0,
            'body' => "# {$title}\n\n".fake()->paragraphs(3, true),
        ];
    }
}
