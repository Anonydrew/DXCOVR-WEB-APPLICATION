<?php

namespace Database\Factories;

use App\Enums\QuestionnaireStatus;
use App\Models\ClientQuestionnaire;
use App\Support\Onboarding\QuestionnaireForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientQuestionnaire>
 */
class ClientQuestionnaireFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_name' => fake()->name(),
            'client_email' => fake()->safeEmail(),
            'client_company' => fake()->company(),
            'project_name' => fake()->words(3, true),
            'sections' => array_keys(QuestionnaireForm::optionalSections()),
            'status' => QuestionnaireStatus::Pending,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => QuestionnaireStatus::InProgress,
            'first_opened_at' => now()->subDay(),
            'last_saved_at' => now(),
            'answers' => ['about' => ['contact_name' => 'Ama Mensah', 'contact_email' => 'ama@example.com']],
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => QuestionnaireStatus::Submitted,
            'first_opened_at' => now()->subDays(2),
            'last_saved_at' => now()->subHour(),
            'submitted_at' => now()->subHour(),
            'answers' => [
                'about' => ['contact_name' => 'Ama Mensah', 'contact_email' => 'ama@example.com', 'company' => 'Mensah Clinics'],
                'project' => ['types' => ['website', 'business_system'], 'summary' => 'A booking website connected to our clinic system.'],
                'look' => ['styles' => ['minimal', 'warm'], 'palettes' => ['ocean'], 'typography' => 'modern', 'mood' => ['friendly', 'trustworthy']],
                'inspiration' => ['references' => [['url' => 'https://example.com', 'note' => 'Simple booking flow.']]],
                'final' => ['consent' => true],
            ],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
