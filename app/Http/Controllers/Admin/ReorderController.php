<?php

namespace App\Http\Controllers\Admin;

use App\Models\ArticleCategory;
use App\Models\Department;
use App\Models\Faq;
use App\Models\NavigationItem;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Services\Navigation;
use App\Support\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Persists drag-and-drop ordering. Each type requires its own permission.
 */
class ReorderController extends AdminController
{
    /**
     * @var array<string, array{0: class-string, 1: string}>
     */
    protected const TYPES = [
        'services' => [Service::class, 'services.manage'],
        'projects' => [Project::class, 'projects.manage'],
        'products' => [Product::class, 'products.manage'],
        'project-categories' => [ProjectCategory::class, 'projects.manage'],
        'article-categories' => [ArticleCategory::class, 'taxonomy.manage'],
        'team' => [TeamMember::class, 'company.manage'],
        'testimonials' => [Testimonial::class, 'company.manage'],
        'faqs' => [Faq::class, 'company.manage'],
        'departments' => [Department::class, 'careers.manage'],
        'sections' => [PageSection::class, 'pages.manage'],
        'navigation' => [NavigationItem::class, 'navigation.manage'],
    ];

    public function __invoke(Request $request, string $type): JsonResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        [$class, $permission] = self::TYPES[$type];
        abort_unless($request->user()->can($permission), 403);

        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ])['ids'];

        DB::transaction(function () use ($class, $ids): void {
            foreach (array_values($ids) as $position => $id) {
                $class::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        if (in_array($type, ['navigation'], true)) {
            app(Navigation::class)->flush();
        }

        ContentCache::flush();

        $this->audit()->record('content.reordered', null, 'Reordered '.str_replace('-', ' ', $type), ['count' => count($ids)]);

        return response()->json(['message' => 'Order saved']);
    }
}
