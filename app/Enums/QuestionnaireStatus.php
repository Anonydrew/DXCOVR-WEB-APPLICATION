<?php

namespace App\Enums;

enum QuestionnaireStatus: string
{
    use Concerns;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Not started',
            self::InProgress => 'In progress',
            self::Submitted => 'Submitted',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'muted',
            self::InProgress => 'warning',
            self::Submitted => 'success',
        };
    }
}
