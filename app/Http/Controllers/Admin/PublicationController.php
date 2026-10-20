<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use App\Models\JobListing;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Services\PublicationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

/**
 * Explicit publication actions for every publishable content type.
 */
class PublicationController extends AdminController
{
    /**
     * Content type => [model class, permission required to publish, edit route].
     *
     * @var array<string, array{0: class-string<Model>, 1: string, 2: string}>
     */
    protected const TYPES = [
        'article' => [Article::class, 'articles.publish', 'admin.articles.edit'],
        'service' => [Service::class, 'services.manage', 'admin.services.edit'],
        'project' => [Project::class, 'projects.manage', 'admin.projects.edit'],
        'product' => [Product::class, 'products.manage', 'admin.products.edit'],
        'job' => [JobListing::class, 'careers.manage', 'admin.jobs.edit'],
        'page' => [Page::class, 'pages.manage', 'admin.pages.edit'],
    ];

    public function __invoke(Request $request, string $type, int $id, PublicationService $publication): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        [$class, $permission, $editRoute] = self::TYPES[$type];
        $model = $class::query()->findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['publish', 'schedule', 'unpublish', 'archive', 'submit', 'preview'])],
            'publish_at' => ['required_if:action,schedule', 'nullable', 'date'],
        ]);

        $action = $validated['action'];

        if ($model instanceof Page && $model->isSystem() && $action !== 'preview') {
            return back()->with('error', 'System pages are always live. Edit their sections and metadata instead.');
        }

        $this->authorizeAction($model, $action, $permission);

        if ($action === 'preview') {
            return redirect()->away(URL::temporarySignedRoute(
                'preview',
                now()->addDays((int) config('dxcovr.preview_link_days', 7)),
                ['type' => $type, 'id' => $model->getKey()],
            ));
        }

        match ($action) {
            'publish' => $publication->publish($model),
            'schedule' => $publication->schedule($model, Carbon::parse($validated['publish_at'], config('app.timezone'))),
            'submit' => $publication->submitForReview($model),
            'unpublish' => $publication->revertToDraft($model),
            'archive' => $publication->archive($model),
        };

        $messages = [
            'publish' => 'Published. It is now live on the website.',
            'schedule' => 'Scheduled for '.$model->published_at?->format('j M Y, H:i').'. It will go live automatically.',
            'submit' => 'Submitted for review. Editors have been notified.',
            'unpublish' => 'Moved to draft. It is no longer visible on the website.',
            'archive' => 'Archived. It is hidden from the website and kept in the CMS.',
        ];

        return redirect()->route($editRoute, $model)->with('status', $messages[$action]);
    }

    protected function authorizeAction(Model $model, string $action, string $permission): void
    {
        if ($model instanceof Article) {
            Gate::authorize(match ($action) {
                'submit' => 'submit',
                'preview' => 'update',
                default => 'publish',
            }, $model);

            return;
        }

        abort_unless(request()->user()->can($permission), 403);
    }
}
