<?php

namespace App\Notifications;

use App\Models\NewsletterSubscriber;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Double opt-in confirmation. Subscribers are not added until they click the link.
 */
class ConfirmNewsletterSubscription extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = app(SiteSettings::class)->get('site.name');

        return (new MailMessage)
            ->subject('Confirm your subscription to '.$siteName)
            ->line('Please confirm that you would like to receive occasional updates from '.$siteName.'.')
            ->action('Confirm subscription', route('newsletter.confirm', $this->subscriber->token))
            ->line('If you did not request this, ignore this email and you will not be subscribed.');
    }
}
