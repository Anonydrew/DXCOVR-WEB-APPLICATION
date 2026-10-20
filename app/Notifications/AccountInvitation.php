<?php

namespace App\Notifications;

use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites a newly created administrator to choose their own password.
 * Sent synchronously so the inviter immediately sees any delivery failure.
 */
class AccountInvitation extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

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
        $expires = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Your '.$siteName.' admin account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An administrator account has been created for you on the '.$siteName.' website.')
            ->action('Choose your password', route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line("This link expires in {$expires} minutes. If it expires, use “Forgot password” on the sign-in page.");
    }
}
