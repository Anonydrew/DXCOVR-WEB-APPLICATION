<?php

namespace Database\Factories;

use App\Models\Article;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    use PublicationStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->unique()->sentence(6), '.'),
            'excerpt' => fake()->sentence(18),
            'body' => '<h2>'.fake()->sentence(3).'</h2><p>'.fake()->paragraph(5).'</p><h2>'.fake()->sentence(3).'</h2><p>'.fake()->paragraph(4).'</p>',
            'show_author' => true,
            'show_toc' => true,
            'is_featured' => false,
            'reading_time' => 2,
            'status' => 'draft',
        ];
    }
}
