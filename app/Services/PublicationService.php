<?php

namespace App\Services;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\JobListing;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ContentSubmittedForReview;
use App\Support\ContentCache;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The only place where publication state changes. Every transition is
 * validated, timestamped and written to the audit log.
 */
class PublicationService
{
    /**
     * Content types that take part in the publishing workflow and scheduler.
     *
     * @var list<class-string<Model>>
     */
    public const PUBLISHABLE = [
        Article::class,
        Service::class,
        Project::class,
        Product::class,
        JobListing::class,
        Page::class,
    ];

    public function __construct(protected AuditLogger $audit, protected SiteSettings $settings) {}

    /**
     * Publish immediately. A past publication date (for example when re-publishing) is preserved.
     */
    public function publish(Model $item): void
    {
        $this->guard($item);

        $item->status = PublicationStatus::Published;

        if ($item->published_at === null || $item->published_at->isFuture()) {
            $item->published_at = now();
        }

        $item->save();

        $this->audit->record('content.published', $item, 'Published '.$item->contentTypeLabel().' “'.$item->displayTitle().'”');
    }

    /**
     * Schedule for automatic publication by the scheduler (php artisan content:publish-scheduled).
     */
    public function schedule(Model $item, CarbonInterface $publishAt): void
    {
        if ($publishAt->lte(now())) {
            throw ValidationException::withMessages([
                'publish_at' => 'Choose a date and time in the future, or publish now instead.',
            ]);
        }

        $this->guard($item);

        $item->status = PublicationStatus::Scheduled;
        $item->published_at = $publishAt;
        $item->save();

        $this->audit->record('content.scheduled', $item, 'Scheduled '.$item->contentTypeLabel().' “'.$item->displayTitle().'” for '.$publishAt->toDayDateTimeString(), [
            'publish_at' => $publishAt->toIso8601String(),
        ]);
    }

    public function submitForReview(Model $item): void
    {
        $this->guard($item);

        $item->status = PublicationStatus::InReview;

        if ($item instanceof Article) {
            $item->submitted_at = now();
        }

        $item->save();

        $this->audit->record('content.submitted', $item, 'Submitted '.$item->contentTypeLabel().' “'.$item->displayTitle().'” for review');

        $this->notifyReviewers($item);
    }

    /**
     * Return the item to draft. Used for "unpublish" and "cancel schedule".
     */
    public function revertToDraft(Model $item): void
    {
        $previous = $item->status;

        $item->status = PublicationStatus::Draft;

        if ($previous === PublicationStatus::Scheduled) {
            $item->published_at = null;
        }

        $item->save();

        $this->audit->record('content.unpublished', $item, 'Moved '.$item->contentTypeLabel().' “'.$item->displayTitle().'” to draft', [
            'from' => $previous?->value,
        ]);
    }

    public function archive(Model $item): void
    {
        $previous = $item->status;

        $item->status = PublicationStatus::Archived;
        $item->save();

        $this->audit->record('content.archived', $item, 'Archived '.$item->contentTypeLabel().' “'.$item->displayTitle().'”', [
            'from' => $previous?->value,
        ]);
    }

    /**
     * Publish every scheduled item whose time has arrived.
     *
     * @return int Number of items published.
     */
    public function publishDue(): int
    {
        $count = 0;

        foreach (self::PUBLISHABLE as $class) {
            $class::query()->dueForPublication()->each(function (Model $item) use (&$count): void {
                DB::transaction(function () use ($item): void {
                    $item->status = PublicationStatus::Published;
                    $item->saveQuietly();
                });

                $this->audit->record('content.auto_published', $item, 'Automatically published scheduled '.$item->contentTypeLabel().' “'.$item->displayTitle().'”');
                $count++;
            });
        }

        if ($count > 0) {
            ContentCache::flush();
        }

        return $count;
    }

    /**
     * @throws ValidationException
     */
    public function guard(Model $item): void
    {
        $missing = [];

        if (blank($item->displayTitle())) {
            $missing[] = 'A title is required.';
        }

        $missing = array_merge($missing, $item->missingForPublication());

        if ($missing !== []) {
            throw ValidationException::withMessages(['publication' => $missing]);
        }
    }

    protected function notifyReviewers(Model $item): void
    {
        if (! $this->settings->get('notifications.editorial_review', true)) {
            return;
        }

        try {
            $reviewers = User::query()->active()
                ->whereKeyNot(Auth::id() ?? 0)
                ->get()
                ->filter(fn (User $user) => $user->can('articles.publish'));

            if ($reviewers->isNotEmpty()) {
                Notification::send($reviewers, new ContentSubmittedForReview($item, Auth::user()));
            }
        } catch (Throwable $exception) {
            Log::warning('Unable to send review notification.', ['error' => $exception->getMessage()]);
        }
    }
}
