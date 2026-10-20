<?php

namespace Database\Factories\Concerns;

use App\Enums\PublicationStatus;
use DateTimeInterface;

trait PublicationStates
{
    public function published(?DateTimeInterface $at = null): static
    {
        return $this->state(fn () => [
            'status' => PublicationStatus::Published,
            'published_at' => $at ?? now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PublicationStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(?DateTimeInterface $at = null): static
    {
        return $this->state(fn () => [
            'status' => PublicationStatus::Scheduled,
            'published_at' => $at ?? now()->addDay(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => PublicationStatus::Archived]);
    }
}
