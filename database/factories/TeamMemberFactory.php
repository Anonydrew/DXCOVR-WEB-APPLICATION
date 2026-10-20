<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role_title' => fake()->jobTitle(),
            'short_bio' => fake()->sentence(),
            'is_founder' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function founder(): static
    {
        return $this->state(fn () => ['is_founder' => true]);
    }
}
