<?php

namespace Database\Factories;

use App\Enums\MediaKind;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::lower((string) Str::ulid());

        return [
            'disk' => 'public',
            'path' => 'media/'.now()->format('Y/m').'/'.$name.'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'kind' => MediaKind::Image,
            'size' => 120_000,
            'width' => 1600,
            'height' => 900,
            'alt' => fake()->sentence(6),
            'variants' => null,
        ];
    }

    public function withoutAlt(): static
    {
        return $this->state(fn () => ['alt' => null]);
    }
}
