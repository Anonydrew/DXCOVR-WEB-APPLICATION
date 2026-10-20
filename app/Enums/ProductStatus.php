<?php

namespace App\Enums;

enum ProductStatus: string
{
    use Concerns;

    case Concept = 'concept';
    case InDevelopment = 'in_development';
    case Beta = 'beta';
    case Available = 'available';
    case Discontinued = 'discontinued';

    public function label(): string
    {
        return match ($this) {
            self::Concept => 'Concept',
            self::InDevelopment => 'In development',
            self::Beta => 'Beta',
            self::Available => 'Available',
            self::Discontinued => 'Discontinued',
        };
    }
}
