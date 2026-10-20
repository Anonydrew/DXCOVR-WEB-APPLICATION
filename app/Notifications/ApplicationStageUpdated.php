<?php

namespace App\Notifications;

use App\Enums\ApplicationStage;
use App\Models\JobApplication;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the applicant only when a recruiter explicitly chooses to notify them.
 */
class ApplicationStageUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public JobApplication $application, public ?string $personalMessage = null) {}

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
        $role = $this->application->jobListing->title;

        $message = (new MailMessage)
            ->subject('Update on your application: '.$role)
            ->greeting('Hello '.$this->application->name.',');

        $message->line(match ($this->application->stage) {
            ApplicationStage::UnderReview => 'Your application for **'.$role.'** is now being reviewed by our team.',
            ApplicationStage::Shortlisted => 'Good news! Your application for **'.$role.'** has been shortlisted.',
            ApplicationStage::Interview => 'We would like to invite you to interview for the **'.$role.'** role. We will be in touch with the details.',
            ApplicationStage::Offered => 'We are pleased to let you know that we would like to make you an offer for the **'.$role.'** role.',
            ApplicationStage::Hired => 'Welcome aboard! We look forward to working with you as **'.$role.'**.',
            ApplicationStage::Declined => 'Thank you for your interest in the **'.$role.'** role. After careful consideration, we will not be progressing your application on this occasion.',
            default => 'There is an update on your application for **'.$role.'**.',
        });

        if (filled($this->personalMessage)) {
            $message->line($this->personalMessage);
        }

        return $message
            ->line('Your application reference is **'.$this->application->reference.'**.')
            ->salutation('The '.$siteName.' team');
    }
}
