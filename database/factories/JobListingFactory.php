<?php

namespace Database\Factories;

use App\Enums\ApplicationMethod;
use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use App\Models\JobListing;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobListing>
 */
class JobListingFactory extends Factory
{
    use PublicationStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->jobTitle()),
            'location' => 'Accra, Ghana',
            'work_arrangement' => WorkArrangement::Hybrid,
            'employment_type' => EmploymentType::FullTime,
            'summary' => fake()->sentence(16),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'application_deadline' => now()->addMonth(),
            'auto_close' => true,
            'application_method' => ApplicationMethod::Form,
            'status' => 'draft',
        ];
    }

    public function open(): static
    {
        return $this->published();
    }
}
