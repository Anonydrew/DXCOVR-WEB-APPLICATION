<?php

namespace Database\Factories;

use App\Models\Page;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    use PublicationStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'template' => 'standard',
            'excerpt' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'status' => 'draft',
        ];
    }
}
