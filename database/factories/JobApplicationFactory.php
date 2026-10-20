<?php

namespace Database\Factories;

use App\Enums\ApplicationStage;
use App\Models\JobApplication;
use App\Models\JobListing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => (string) Str::ulid(),
            'job_listing_id' => JobListing::factory()->published(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'cover_letter' => fake()->paragraph(4),
            'cv_disk' => 'local',
            'cv_path' => 'applications/test/'.Str::lower((string) Str::ulid()).'/cv.pdf',
            'cv_original_name' => 'cv.pdf',
            'cv_mime' => 'application/pdf',
            'cv_size' => 20_000,
            'consent_given_at' => now(),
            'stage' => ApplicationStage::Received,
        ];
    }
}
