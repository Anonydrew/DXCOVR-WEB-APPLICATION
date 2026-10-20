<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationMethod;
use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use App\Http\Requests\Admin\JobListingRequest;
use App\Models\Department;
use App\Models\JobListing;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobListingController extends AdminController
{
    protected const RICH_FIELDS = ['description', 'responsibilities', 'requirements', 'nice_to_have', 'benefits'];

    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function index(Request $request): View
    {
        $jobs = JobListing::query()
            ->with('department')
            ->withCount('applications')
            ->withStatus($request->query('status'))
            ->when($request->query('state') === 'open', fn (Builder $query) => $query->open())
            ->when($request->query('state') === 'closed', fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNotNull('closed_at')->orWhere('application_deadline', '<=', now())))
            ->search($request->string('q')->limit(100, '')->toString() ?: null)
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.jobs.index', [
            'jobs' => $jobs,
            'canManage' => $request->user()->can('careers.manage'),
            'canViewApplications' => $request->user()->can('applications.view'),
        ]);
    }

    public function create(): View
    {
        return view('admin.jobs.form', $this->formData(new JobListing([
            'work_arrangement' => WorkArrangement::OnSite,
            'employment_type' => EmploymentType::FullTime,
            'application_method' => ApplicationMethod::Form,
            'auto_close' => true,
        ])));
    }

    public function store(JobListingRequest $request): RedirectResponse
    {
        $job = new JobListing;

        $warning = DB::transaction(function () use ($request, $job) {
            $this->fill($job, $request);
            $job->save();

            return $this->saveSeo($job, $request->validated('seo'));
        });

        $this->audit()->record('careers.job_created', $job, 'Created job listing “'.$job->title.'”');

        return $this->saved('admin.jobs.edit', [$job], 'Job listing saved as a draft.', $warning);
    }

    public function edit(JobListing $job): View
    {
        $job->load(['seo.ogImage', 'editor'])->loadCount('applications');

        return view('admin.jobs.form', $this->formData($job));
    }

    public function update(JobListingRequest $request, JobListing $job): RedirectResponse
    {
        $warning = DB::transaction(function () use ($request, $job) {
            $this->fill($job, $request);
            $job->save();

            return $this->saveSeo($job, $request->validated('seo'));
        });

        $this->audit()->record('careers.job_updated', $job, 'Updated job listing “'.$job->title.'”', $this->audit()->changes($job));

        return $this->saved('admin.jobs.edit', [$job], 'Job listing updated.', $warning);
    }

    public function close(JobListing $job): RedirectResponse
    {
        $job->closed_at = now();
        $job->save();

        $this->audit()->record('careers.job_closed', $job, 'Closed applications for “'.$job->title.'”');

        return back()->with('status', 'Applications closed. The listing stays visible with a “closed” notice until you unpublish or archive it.');
    }

    public function reopen(JobListing $job): RedirectResponse
    {
        if ($job->application_deadline?->isPast()) {
            return back()->with('error', 'Set a future application deadline (or remove it) before reopening.');
        }

        $job->closed_at = null;
        $job->save();

        $this->audit()->record('careers.job_reopened', $job, 'Reopened applications for “'.$job->title.'”');

        return back()->with('status', 'Applications reopened.');
    }

    public function destroy(JobListing $job): RedirectResponse
    {
        if ($job->applications()->exists()) {
            return back()->with('error', 'This listing has applications, so it cannot be deleted. Archive it instead; applications are removed automatically after the retention period.');
        }

        $title = $job->title;
        $job->delete();

        $this->audit()->record('careers.job_deleted', null, 'Deleted job listing “'.$title.'”');

        return redirect()->route('admin.jobs.index')->with('status', 'Job listing deleted.');
    }

    protected function fill(JobListing $job, JobListingRequest $request): void
    {
        $job->fill($request->safe()->only([
            'title', 'slug', 'department_id', 'location', 'work_arrangement', 'employment_type', 'employment_type_label', 'summary',
            'salary_range', 'application_deadline', 'application_method', 'application_email', 'application_url', 'application_instructions',
        ]));

        foreach (self::RICH_FIELDS as $field) {
            $job->{$field} = $this->sanitizer->rich($request->validated($field));
        }

        $job->auto_close = $request->boolean('auto_close');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(JobListing $job): array
    {
        return [
            'job' => $job,
            'departments' => Department::query()->orderBy('sort_order')->pluck('name', 'id'),
        ];
    }
}
