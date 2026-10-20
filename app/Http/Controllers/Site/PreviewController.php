<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\JobListing;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Contracts\View\View;

/**
 * Renders unpublished content through a time-limited signed URL. The route
 * carries the "signed" and "noindex" middleware; pages are also marked
 * noindex in their meta tags and show a preview banner.
 */
class PreviewController extends Controller
{
    /**
     * @var array<string, array{0: class-string, 1: class-string, 2?: string}>
     */
    public const TYPES = [
        'article' => [Article::class, InsightController::class],
        'service' => [Service::class, ServiceController::class],
        'project' => [Project::class, WorkController::class],
        'product' => [Product::class, ProductController::class],
        'job' => [JobListing::class, CareerController::class],
        'page' => [Page::class, PageController::class],
    ];

    public function __invoke(string $type, int $id): View
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        [$modelClass, $controllerClass] = self::TYPES[$type];
        $model = $modelClass::query()->findOrFail($id);

        if ($model instanceof Page && $model->isSystem()) {
            abort(404);
        }

        return app($controllerClass)->render($model, true);
    }

    public static function typeFor(object $model): ?string
    {
        foreach (self::TYPES as $type => [$class]) {
            if ($model instanceof $class) {
                return $type;
            }
        }

        return null;
    }
}
