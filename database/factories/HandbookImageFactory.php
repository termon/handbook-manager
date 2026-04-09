<?php

namespace Database\Factories;

use App\Models\Handbook;
use App\Models\HandbookImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandbookImage>
 */
class HandbookImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'handbook_id' => Handbook::factory(),
            'disk' => 'public',
            'path' => 'handbooks/example/example-image.png',
            'name' => 'example-image.png',
            'alt_text' => 'Example image',
            'mime_type' => 'image/png',
            'size' => 1024,
        ];
    }
}
