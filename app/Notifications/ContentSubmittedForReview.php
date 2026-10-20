<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class ContentSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Model $content, public ?User $submittedBy = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $route = $this->content instanceof Article ? 'admin.articles.edit' : null;

        $message = (new MailMessage)
            ->subject('Ready for review: '.$this->content->displayTitle())
            ->line(($this->submittedBy?->name ?? 'An author').' has submitted the '.$this->content->contentTypeLabel().' “'.$this->content->displayTitle().'” for review.');

        if ($route && Route::has($route)) {
            $message->action('Review it', route($route, $this->content));
        }

        return $message;
    }
}
