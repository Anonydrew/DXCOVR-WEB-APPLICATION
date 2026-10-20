<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStage;
use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Media;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Operational overview. Every figure is a live database query; panels are
 * included only for modules the signed-in user may access.
 */
class DashboardController extends AdminController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $stats = [];
        $attention = [];

        if ($user->canAny(['articles.view', 'articles.edit-any'])) {
            $counts = Article::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
            $stats[] = ['label' => 'Published articles', 'value' => (int) ($counts[PublicationStatus::Published->value] ?? 0), 'route' => route('admin.articles.index', ['status' => 'published'])];
            $stats[] = ['label' => 'Drafts & scheduled', 'value' => (int) ($counts[PublicationStatus::Draft->value] ?? 0) + (int) ($counts[PublicationStatus::Scheduled->value] ?? 0), 'route' => route('admin.articles.index', ['status' => 'draft']), 'hint' => (int) ($counts[PublicationStatus::Scheduled->value] ?? 0).' scheduled'];

            if ($user->can('articles.publish') && ($inReview = (int) ($counts[PublicationStatus::InReview->value] ?? 0)) > 0) {
                $attention[] = ['tone' => 'warning', 'text' => trans_choice('{1} 1 article is waiting for review|[2,*] :count articles are waiting for review', $inReview), 'route' => route('admin.articles.index', ['status' => 'in_review'])];
            }
        }

        if ($user->can('careers.view')) {
            $stats[] = ['label' => 'Open job listings', 'value' => JobListing::query()->open()->count(), 'route' => route('admin.jobs.index')];

            $closingSoon = JobListing::query()->open()->whereNotNull('application_deadline')->where('application_deadline', '<=', now()->addDays(7))->count();
            if ($closingSoon > 0) {
                $attention[] = ['tone' => 'info', 'text' => trans_choice('{1} 1 job listing closes within 7 days|[2,*] :count job listings close within 7 days', $closingSoon), 'route' => route('admin.jobs.index')];
            }
        }

        if ($user->can('applications.view')) {
            $newApplications = JobApplication::query()->where('stage', ApplicationStage::Received->value)->count();
            $stats[] = ['label' => 'Applications received', 'value' => JobApplication::query()->count(), 'route' => route('admin.applications.index'), 'hint' => $newApplications.' awaiting review'];

            if ($newApplications > 0) {
                $attention[] = ['tone' => 'info', 'text' => trans_choice('{1} 1 new application has not been reviewed|[2,*] :count new applications have not been reviewed', $newApplications), 'route' => route('admin.applications.index', ['stage' => 'received'])];
            }
        }

        if ($user->can('enquiries.view')) {
            $unread = Enquiry::query()->unread()->count();
            $stats[] = ['label' => 'Unread enquiries', 'value' => $unread, 'route' => route('admin.enquiries.index', ['unread' => 1]), 'hint' => Enquiry::query()->where('created_at', '>=', now()->subDays(30))->count().' in the last 30 days'];

            $stale = Enquiry::query()->unread()->where('created_at', '<=', now()->subDays(2))->count();
            if ($stale > 0) {
                $attention[] = ['tone' => 'warning', 'text' => trans_choice('{1} 1 enquiry has been unread for over two days|[2,*] :count enquiries have been unread for over two days', $stale), 'route' => route('admin.enquiries.index', ['unread' => 1])];
            }
        }

        if ($user->can('projects.view')) {
            $stats[] = ['label' => 'Portfolio projects', 'value' => Project::query()->published()->count(), 'route' => route('admin.projects.index'), 'hint' => Project::query()->count().' in total'];
        }

        if ($user->can('products.view')) {
            $stats[] = ['label' => 'Products', 'value' => Product::query()->published()->count(), 'route' => route('admin.products.index'), 'hint' => Product::query()->count().' in total'];
        }

        if ($user->can('pages.manage')) {
            $draftLegal = Page::query()->where('template', 'legal')->where('status', '!=', PublicationStatus::Published->value)->pluck('title');
            if ($draftLegal->isNotEmpty()) {
                $attention[] = ['tone' => 'warning', 'text' => 'Legal pages awaiting review and publication: '.$draftLegal->implode(', ').'.', 'route' => route('admin.pages.index')];
            }
        }

        if ($user->can('media.view')) {
            $missingAlt = Media::query()->images()->where(fn ($query) => $query->whereNull('alt')->orWhere('alt', ''))->count();
            if ($missingAlt > 0) {
                $attention[] = ['tone' => 'info', 'text' => trans_choice('{1} 1 image has no alternative text|[2,*] :count images have no alternative text', $missingAlt), 'route' => route('admin.media.index', ['missing_alt' => 1])];
            }
        }

        $scheduled = collect();
        if ($user->canAny(['articles.publish', 'services.manage', 'projects.manage', 'products.manage'])) {
            $scheduled = Article::query()->where('status', PublicationStatus::Scheduled->value)->orderBy('published_at')->limit(5)->get(['id', 'title', 'published_at']);
        }

        $myDrafts = $user->can('articles.create')
            ? Article::query()->where('author_id', $user->id)->whereIn('status', [PublicationStatus::Draft->value, PublicationStatus::InReview->value])->latest('updated_at')->limit(5)->get(['id', 'title', 'status', 'updated_at'])
            : collect();

        $activity = $user->can('audit.view')
            ? AuditLog::query()->with('user')->latest('created_at')->latest('id')->limit(10)->get()
            : collect();

        return view('admin.dashboard', [
            'stats' => $stats,
            'attention' => $attention,
            'scheduled' => $scheduled,
            'myDrafts' => $myDrafts,
            'activity' => $activity,
            'needsTwoFactor' => ! $user->hasTwoFactorEnabled(),
        ]);
    }
}
