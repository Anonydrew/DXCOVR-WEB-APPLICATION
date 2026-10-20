<?php

namespace App\Enums;

enum WorkArrangement: string
{
    use Concerns;

    case OnSite = 'onsite';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::OnSite => 'On-site',
            self::Hybrid => 'Hybrid',
            self::Remote => 'Remote',
        };
    }
}
