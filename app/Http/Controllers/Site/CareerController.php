<?php

namespace App\Http\Controllers\Site;

use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\JobApplicationRequest;
use App\Models\Department;
use App\Models\JobListing;
use App\Models\Page;
use App\Services\Recruitment;
use App\Services\RichContent;
use App\Services\SiteSettings;
use App\Services\Spam\SpamGuard;
use App\Support\Sections\SectionDataResolver;
use App\Support\Seo\Seo;
use App\Support\Seo\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    public function __construct(protected RichContent $content, protected SectionDataResolver $resolver) {}

    public function index(Request $request): View
    {
        $filters = [
            'department' => $request->string('department')->limit(120, '')->toString() ?: null,
            'type' => in_array($request->query('type'), EmploymentType::values(), true) ? $request->query('type') : null,
            'arrangement' => in_array($request->query('arrangement'), WorkArrangement::values(), true) ? $request->query('arrangement') : null,
        ];

        $jobs = JobListing::query()->open()
            ->with('department')
            ->when($filters['department'], fn (Builder $query, string $slug) => $query->whereHas('department', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($filters['type'], fn (Builder $query, string $type) => $query->where('employment_type', $type))
            ->when($filters['arrangement'], fn (Builder $query, string $arrangement) => $query->where('work_arrangement', $arrangement))
            ->orderBy('application_deadline')
            ->latest('published_at')
            ->get();

        $page = Page::system('careers');
        $sections = $page ? $page->enabledSections()->get() : collect();

        return view('site.careers.index', [
            'page' => $page,
            'jobs' => $jobs,
            'filters' => $filters,
            'hasFilters' => array_filter($filters) !== [],
            'departments' => Department::query()->whereHas('jobListings', fn (Builder $query) => $query->open())->orderBy('sort_order')->get(),
            'sections' => $sections,
            'sectionData' => $this->resolver->resolve($sections),
            'seo' => Seo::make($page?->title ?? 'Careers')
                ->description($page?->excerpt)
                ->meta($page?->seo)
                ->noindex(array_filter($filters) !== [])
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Careers', 'url' => route('careers.index')],
                ]),
        ]);
    }

    public function show(JobListing $job): View
    {
        abort_unless($job->isPubliclyVisible(), 404);

        return $this->render($job);
    }

    public function render(JobListing $job, bool $preview = false): View
    {
        $job->load(['department', 'seo.ogImage']);

        $sections = collect([
            'description' => 'About the role',
            'responsibilities' => 'What you will do',
            'requirements' => 'What we are looking for',
            'nice_to_have' => 'Nice to have',
            'benefits' => 'What we offer',
        ])->map(fn (string $label, string $field) => ['label' => $label, 'html' => $this->content->prepare($job->{$field})['html']])
            ->filter(fn (array $section) => trim(strip_tags($section['html'])) !== '');

        $seo = Seo::make($job->title.' · Careers')
            ->description($job->summary)
            ->meta($job->seo)
            ->noindex($preview || ! $job->isAcceptingApplications())
            ->canonical($preview ? null : route('careers.show', $job))
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Careers', 'url' => route('careers.index')],
                ['name' => $job->title, 'url' => route('careers.show', $job)],
            ]);

        if ($job->isAcceptingApplications() && ! $preview) {
            $seo->schema(StructuredData::jobPosting($job));
        }

        return view('site.careers.show', [
            'job' => $job,
            'contentSections' => $sections,
            'isPreview' => $preview,
            'privacyNotice' => app(SiteSettings::class)->get('careers.privacy_notice'),
            'seo' => $seo,
        ]);
    }

    public function apply(JobApplicationRequest $request, JobListing $job, SpamGuard $spam, Recruitment $recruitment): RedirectResponse
    {
        abort_unless($job->isPubliclyVisible(), 404);

        if (! $job->acceptsFormApplications()) {
            return redirect()->route('careers.show', $job)
                ->withErrors(['form' => 'Applications for this role are closed or are not accepted through this form.']);
        }

        $spam->check($request, 'job-application');

        $application = $recruitment->submit(
            $job,
            $request->safe()->only(['name', 'email', 'phone', 'cover_letter', 'portfolio_url']),
            $request->file('cv'),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('careers.received')->with('application', [
            'reference' => $application->reference,
            'role' => $job->title,
        ]);
    }

    public function received(Request $request): View|RedirectResponse
    {
        $application = $request->session()->get('application');

        if (! is_array($application)) {
            return redirect()->route('careers.index');
        }

        return view('site.careers.received', [
            'application' => $application,
            'seo' => Seo::make('Application received')->noindex(),
        ]);
    }
}
