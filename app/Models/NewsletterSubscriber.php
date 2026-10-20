<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['email', 'name', 'consent_given_at', 'source', 'ip_address'])]
#[Hidden(['token', 'ip_address'])]
class NewsletterSubscriber extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SubscriberStatus::class,
            'consent_given_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscriber): void {
            $subscriber->token ??= Str::random(64);
            $subscriber->status ??= SubscriberStatus::Pending;
        });
    }
}
