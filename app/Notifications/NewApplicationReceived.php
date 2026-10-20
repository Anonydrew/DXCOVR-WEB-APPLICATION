<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the recruitment team a new application has arrived. The CV is never
 * attached; it can only be downloaded through the authorised admin.
 */
class NewApplicationReceived extends Notification implements ShouldQueue
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
        $job = $this->application->jobListing;

        return (new MailMessage)
            ->subject('New application: '.$job->title)
            ->greeting('New application received')
            ->line('A new application has been submitted for **'.$job->title.'**.')
            ->line('**Applicant:** '.$this->application->name)
            ->action('Review the application', route('admin.applications.show', $this->application))
            ->line('For the applicant\'s privacy, the CV is not attached. Sign in to the admin to review it.');
    }
}
