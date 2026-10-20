<?php

namespace App\Notifications;

use App\Models\JobApplication;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationAcknowledgement extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public JobApplication $application) {}

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
            ->subject('Application received: '.$this->application->jobListing->title)
            ->greeting('Hello '.$this->application->name.',')
            ->line('Thank you for applying for the **'.$this->application->jobListing->title.'** role at '.$siteName.'.')
            ->line('We review every application carefully and will contact you about the next steps.')
            ->line('Your application reference is **'.$this->application->reference.'**.')
            ->salutation('The '.$siteName.' team');
    }
}
