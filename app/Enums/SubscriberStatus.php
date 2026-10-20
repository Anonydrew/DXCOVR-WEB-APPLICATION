<?php

namespace App\Enums;

enum SubscriberStatus: string
{
    use Concerns;

    case Pending = 'pending';
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting confirmation',
            self::Subscribed => 'Subscribed',
            self::Unsubscribed => 'Unsubscribed',
        };
    }
}
