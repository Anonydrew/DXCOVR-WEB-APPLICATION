<?php

namespace Database\Factories;

use App\Models\Service;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    use PublicationStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'icon' => 'software',
            'short_description' => fake()->sentence(16),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'capabilities' => ['API development', 'System integrations'],
            'use_cases' => [['title' => 'Use case', 'body' => fake()->sentence()]],
            'process_steps' => [['title' => 'Discover', 'body' => fake()->sentence()]],
            'is_featured' => false,
            'sort_order' => 0,
            'status' => 'draft',
        ];
    }
}
