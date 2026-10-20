<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    use Concerns;

    case InProgress = 'in_progress';
    case Live = 'live';
    case Completed = 'completed';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::Live => 'Live',
            self::Completed => 'Completed',
            self::Paused => 'Paused',
        };
    }
}
