<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStage;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Services\Recruitment;
use App\Services\SiteSettings;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Recruitment pipeline. Every route requires "applications.view"; changing
 * stages, adding notes, deleting and exporting require further permissions
 * (see routes/admin.php). CVs are streamed from private storage only.
 */
class ApplicationController extends AdminController
{
    public function __construct(protected Recruitment $recruitment) {}

    public function index(Request $request): View
    {
        $applications = $this->filtered($request)
            ->with('jobListing')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.applications.index', [
            'applications' => $applications,
            'jobs' => JobListing::query()->whereHas('applications')->orderBy('title')->pluck('title', 'id'),
            'stageCounts' => JobApplication::query()->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage'),
        ]);
    }

    public function show(JobApplication $application): View
    {
        $application->load(['jobListing', 'notes.user', 'stageChanges.user']);

        $this->audit()->record('applications.viewed', $application, 'Viewed application '.$application->reference);

        return view('admin.applications.show', [
            'application' => $application,
            'retentionDays' => (int) app(SiteSettings::class)->get('careers.retention_days', 365),
        ]);
    }

    public function downloadCv(JobApplication $application): StreamedResponse
    {
        $disk = Storage::disk($application->cv_disk);
        abort_unless($disk->exists($application->cv_path), 404, 'The CV file could not be found.');

        $extension = pathinfo($application->cv_path, PATHINFO_EXTENSION);
        $filename = Str::slug($application->name).'-cv-'.Str::lower(Str::substr($application->reference, -6)).'.'.$extension;

        $this->audit()->record('applications.cv_downloaded', $application, 'Downloaded the CV for application '.$application->reference);

        return $disk->download($application->cv_path, $filename, [
            'Content-Type' => $application->cv_mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function updateStage(Request $request, JobApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'stage' => ['required', Rule::enum(ApplicationStage::class)],
            'note' => ['nullable', 'string', 'max:2000'],
            'notify_applicant' => ['boolean'],
            'message_to_applicant' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->recruitment->changeStage(
            $application,
            ApplicationStage::from($data['stage']),
            $request->user(),
            $data['note'] ?? null,
            $request->boolean('notify_applicant'),
            $data['message_to_applicant'] ?? null,
        );

        return back()->with('status', $request->boolean('notify_applicant') ? 'Stage updated and the applicant has been emailed.' : 'Stage updated.');
    }

    public function storeNote(Request $request, JobApplication $application): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        $this->recruitment->addNote($application, $request->user(), $data['body']);

        return back()->with('status', 'Note added.');
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        $this->recruitment->delete($application, 'Deleted by '.request()->user()->name);

        return redirect()->route('admin.applications.index')->with('status', 'Application and CV permanently deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->audit()->record('export.applications', null, 'Exported job applications to CSV', ['filters' => $request->only(['job', 'stage', 'q'])]);

        $rows = $this->filtered($request)->with('jobListing')->latest()->lazy(200)->map(fn (JobApplication $application) => [
            $application->reference,
            $application->created_at,
            $application->jobListing?->title,
            $application->name,
            $application->email,
            $application->phone,
            $application->portfolio_url,
            $application->stage->label(),
            $application->stage_changed_at,
        ]);

        return CsvExport::download('applications-'.now()->format('Y-m-d').'.csv', [
            'Reference', 'Submitted', 'Role', 'Name', 'Email', 'Phone', 'Portfolio', 'Stage', 'Stage updated',
        ], $rows);
    }

    protected function filtered(Request $request): Builder
    {
        return JobApplication::query()
            ->when($request->query('job'), fn (Builder $query, $job) => $query->where('job_listing_id', $job))
            ->when(in_array($request->query('stage'), ApplicationStage::values(), true), fn (Builder $query) => $query->where('stage', $request->query('stage')))
            ->search($request->string('q')->limit(100, '')->toString() ?: null);
    }
}
