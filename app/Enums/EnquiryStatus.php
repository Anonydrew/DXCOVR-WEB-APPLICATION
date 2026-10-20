<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    use Concerns;

    case New = 'new';
    case InProgress = 'in_progress';
    case Responded = 'responded';
    case Closed = 'closed';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Responded => 'Responded',
            self::Closed => 'Closed',
            self::Spam => 'Spam',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'info',
            self::InProgress => 'warning',
            self::Responded => 'success',
            self::Closed, self::Spam => 'muted',
        };
    }
}
