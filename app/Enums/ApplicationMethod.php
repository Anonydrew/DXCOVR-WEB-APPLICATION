<?php

namespace App\Enums;

enum ApplicationMethod: string
{
    use Concerns;

    case Form = 'form';
    case Email = 'email';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Form => 'Application form on this website',
            self::Email => 'Apply by email',
            self::External => 'External application link',
        };
    }
}
