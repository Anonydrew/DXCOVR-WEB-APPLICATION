<?php

namespace App\Notifications;

use App\Models\Enquiry;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnquiryAcknowledgement extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Enquiry $enquiry) {}

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
            ->subject('Thanks for contacting '.$siteName)
            ->greeting('Hello '.$this->enquiry->name.',')
            ->line('Thank you for getting in touch with '.$siteName.'. Your message has reached our team and we will reply as soon as we can.')
            ->line('Your reference is **'.$this->enquiry->reference.'**. You can quote it if you need to follow up.')
            ->line('If you did not submit this enquiry, you can safely ignore this email.')
            ->salutation('The '.$siteName.' team');
    }
}
