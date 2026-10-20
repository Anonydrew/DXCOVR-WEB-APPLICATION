<?php

namespace App\Notifications;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewEnquiryReceived extends Notification implements ShouldQueue
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
        $enquiry = $this->enquiry;

        $message = (new MailMessage)
            ->subject('New '.Str::lower($enquiry->type->label()).' from '.$enquiry->name)
            ->greeting('New '.Str::lower($enquiry->type->label()))
            ->line('**Name:** '.$enquiry->name)
            ->line('**Email:** '.$enquiry->email);

        if ($enquiry->organisation) {
            $message->line('**Organisation:** '.$enquiry->organisation);
        }

        if ($enquiry->service) {
            $message->line('**Service:** '.$enquiry->service->title);
        }

        if ($enquiry->product) {
            $message->line('**Product:** '.$enquiry->product->name);
        }

        return $message
            ->line('**Message:**')
            ->line(Str::limit($enquiry->message, 600))
            ->action('Open in the DXCOVR admin', route('admin.enquiries.show', $enquiry))
            ->line('Reply directly to the sender from your email client. This notification contains personal data, so please do not forward it unnecessarily.')
            ->replyTo($enquiry->email, $enquiry->name);
    }
}
