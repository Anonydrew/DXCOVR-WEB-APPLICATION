<?php

namespace App\Enums;

enum MediaKind: string
{
    use Concerns;

    case Image = 'image';
    case Video = 'video';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Video => 'Video',
            self::Document => 'Document',
        };
    }
}
