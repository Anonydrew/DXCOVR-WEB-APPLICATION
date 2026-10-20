<?php

namespace App\Enums;

enum PublicationStatus: string
{
    use Concerns;

    case Draft = 'draft';
    case InReview = 'in_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In review',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::InReview => 'warning',
            self::Scheduled => 'info',
            self::Published => 'success',
            self::Archived => 'muted',
        };
    }
}
