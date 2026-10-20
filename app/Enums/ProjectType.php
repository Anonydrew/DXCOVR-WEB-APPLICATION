<?php

namespace App\Enums;

/**
 * Distinguishes real client delivery from internal products, prototypes and concepts.
 */
enum ProjectType: string
{
    use Concerns;

    case Client = 'client';
    case Internal = 'internal';
    case Prototype = 'prototype';
    case Concept = 'concept';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client project',
            self::Internal => 'Internal product',
            self::Prototype => 'Prototype / experiment',
            self::Concept => 'Concept design',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Client => 'Client work',
            self::Internal => 'DXCOVR product',
            self::Prototype => 'Prototype',
            self::Concept => 'Concept',
        };
    }
}
