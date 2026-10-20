<?php

namespace Database\Factories;

use App\Enums\ProjectType;
use App\Models\Project;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    use PublicationStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(4, true)),
            'summary' => fake()->sentence(20),
            'project_type' => ProjectType::Concept,
            'challenge' => '<p>'.fake()->paragraph().'</p>',
            'solution' => '<p>'.fake()->paragraph().'</p>',
            'is_confidential' => false,
            'is_featured' => false,
            'sort_order' => 0,
            'status' => 'draft',
        ];
    }

    public function forClient(?string $client = 'Example Client Ltd'): static
    {
        return $this->state(fn () => ['project_type' => ProjectType::Client, 'client_name' => $client]);
    }
}
