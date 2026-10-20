<?php

namespace Database\Factories;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => (string) Str::ulid(),
            'type' => EnquiryType::Project,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'organisation' => fake()->company(),
            'message' => fake()->paragraph(),
            'consent_given_at' => now(),
            'status' => EnquiryStatus::New,
        ];
    }
}
