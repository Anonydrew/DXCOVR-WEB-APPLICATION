<?php

namespace App\Notifications;

use App\Models\ClientQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionnaireSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public ClientQuestionnaire $questionnaire) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $questionnaire = $this->questionnaire;
        $email = $questionnaire->answer('about.contact_email') ?: $questionnaire->client_email;

        $message = (new MailMessage)
            ->subject('Onboarding questionnaire submitted: '.$questionnaire->project_name)
            ->greeting('A client has completed their questionnaire')
            ->line('**Client:** '.$questionnaire->contactName())
            ->line('**Project:** '.$questionnaire->project_name);

        if ($questionnaire->client_company) {
            $message->line('**Organisation:** '.$questionnaire->client_company);
        }

        $message->action('Review the answers', route('admin.onboarding.show', $questionnaire))
            ->line('Uploaded files are only available in the admin. This notification contains client information, so please do not forward it unnecessarily.');

        return $email ? $message->replyTo($email, $questionnaire->contactName()) : $message;
    }
}
