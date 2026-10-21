<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sends a client the private link to their invoice.
 */
class InvoiceIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Invoice $invoice) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(SiteSettings::class);
        $issuer = (string) ($settings->get('invoicing.business_name') ?: $settings->get('site.name'));
        $invoice = $this->invoice;
        $name = Str::before((string) ($invoice->billTo()['name'] ?? ''), ' ');

        $message = (new MailMessage)
            ->subject('Invoice '.$invoice->number.' from '.$issuer)
            ->greeting($name !== '' ? 'Hello '.$name.',' : 'Hello,')
            ->line('Please find invoice **'.$invoice->number.'**'.($invoice->subject ? ' for '.$invoice->subject : '').'.');

        if ($invoice->isPaid()) {
            $message->line('This invoice has been paid in full. Thank you.');
        } else {
            $message->line('Amount due: **'.$invoice->money($invoice->balance()).'**'.($invoice->due_date ? ', by '.$invoice->due_date->format('j F Y') : '').'.');
        }

        if (filled($replyTo = $settings->get('invoicing.email'))) {
            $message->replyTo($replyTo, $issuer);
        }

        return $message
            ->action('View invoice', $invoice->shareUrl())
            ->line('You can view, print or save the invoice as a PDF from that page. Payment details are included on the invoice.')
            ->salutation('Thank you,'."\n".$issuer);
    }
}
