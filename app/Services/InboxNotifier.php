<?php

namespace App\Services;

use App\Models\ClientQuestionnaire;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Notifications\ApplicationAcknowledgement;
use App\Notifications\EnquiryAcknowledgement;
use App\Notifications\NewApplicationReceived;
use App\Notifications\NewEnquiryReceived;
use App\Notifications\QuestionnaireSubmitted;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends inbox notifications after a submission has been stored.
 *
 * Notifications are queued with retries; any failure to dispatch is logged
 * and never surfaces to the visitor, whose submission is already saved.
 */
class InboxNotifier
{
    public function __construct(protected SiteSettings $settings) {}

    public function enquiryReceived(Enquiry $enquiry): void
    {
        $this->sendToTeam('notifications.enquiry_recipients', new NewEnquiryReceived($enquiry), 'enquiry', $enquiry->reference);

        if ($this->settings->get('notifications.enquiry_acknowledgement', true)) {
            $this->sendTo($enquiry->email, new EnquiryAcknowledgement($enquiry), 'enquiry acknowledgement', $enquiry->reference);
        }
    }

    public function applicationReceived(JobApplication $application): void
    {
        $this->sendToTeam('notifications.application_recipients', new NewApplicationReceived($application), 'application', $application->reference);

        if ($this->settings->get('notifications.application_acknowledgement', true)) {
            $this->sendTo($application->email, new ApplicationAcknowledgement($application), 'application acknowledgement', $application->reference);
        }
    }

    /**
     * Uses the onboarding recipients, or the enquiry recipients when none are set.
     */
    public function questionnaireSubmitted(ClientQuestionnaire $questionnaire): void
    {
        $settingKey = $this->settings->emails('notifications.onboarding_recipients') !== []
            ? 'notifications.onboarding_recipients'
            : 'notifications.enquiry_recipients';

        $this->sendToTeam($settingKey, new QuestionnaireSubmitted($questionnaire), 'onboarding questionnaire', $questionnaire->reference);
    }

    /**
     * @return list<string>
     */
    public function teamRecipients(string $settingKey): array
    {
        $recipients = $this->settings->emails($settingKey);

        if ($recipients === [] && filled(config('dxcovr.notifications.fallback_recipient'))) {
            $recipients = [(string) config('dxcovr.notifications.fallback_recipient')];
        }

        return $recipients;
    }

    protected function sendToTeam(string $settingKey, BaseNotification $notification, string $label, string $reference): void
    {
        $recipients = $this->teamRecipients($settingKey);

        if ($recipients === []) {
            Log::notice("No recipients configured for {$label} notifications; the record is available in the admin.", ['reference' => $reference]);

            return;
        }

        foreach ($recipients as $recipient) {
            $this->sendTo($recipient, $notification, $label, $reference);
        }
    }

    public function sendTo(string $email, BaseNotification $notification, string $label, string $reference): void
    {
        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (Throwable $exception) {
            Log::error("Unable to dispatch {$label} notification.", [
                'reference' => $reference,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
