@extends('layouts.admin')

@section('title', $job->exists ? 'Edit job listing' : 'New job listing')

@section('content')
    <x-admin.page-header :title="$job->exists ? $job->title : 'New job listing'" :back="route('admin.jobs.index')">
        <x-slot:actions>
            @if ($job->exists)
                @can('applications.view')
                    <a href="{{ route('admin.applications.index', ['job' => $job->id]) }}" class="a-btn">Applications ({{ $job->applications_count }})</a>
                @endcan
                @if ($job->closed_at)
                    <form method="POST" action="{{ route('admin.jobs.reopen', $job) }}">@csrf <button type="submit" class="a-btn">Reopen applications</button></form>
                @else
                    <form method="POST" action="{{ route('admin.jobs.close', $job) }}" data-confirm="Close applications for this role now?">@csrf <button type="submit" class="a-btn">Close applications</button></form>
                @endif
                <x-admin.delete-button :action="route('admin.jobs.destroy', $job)" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="POST" action="{{ $job->exists ? route('admin.jobs.update', $job) : route('admin.jobs.store') }}" class="space-y-6" data-dirty-guard
              x-data="{ method: @js(old('application_method', $job->application_method?->value)), type: @js(old('employment_type', $job->employment_type?->value)) }">
            @csrf
            @if ($job->exists) @method('PUT') @endif

            <div class="a-card a-card-body space-y-5">
                <x-admin.input name="title" label="Job title" :value="$job->title" required maxlength="200" />
                @include('admin.partials.slug', ['model' => $job, 'prefix' => '/careers/'])
                <x-admin.textarea name="summary" label="Summary" :value="$job->summary" required rows="3" counter="600" recommended="240" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.select name="department_id" label="Department" :options="$departments" :value="$job->department_id" placeholder="None" />
                    <x-admin.input name="location" label="Location" :value="$job->location" required placeholder="e.g. Accra, Ghana" />
                    <x-admin.select name="work_arrangement" label="Work arrangement" :options="\App\Enums\WorkArrangement::options()" :value="$job->work_arrangement" required />
                    <x-admin.select name="employment_type" label="Employment type" :options="\App\Enums\EmploymentType::options()" :value="$job->employment_type" required x-model="type" />
                    <div x-show="type === 'other'" x-cloak class="sm:col-span-2">
                        <x-admin.input name="employment_type_label" label="Describe the employment type" :value="$job->employment_type_label" maxlength="80" />
                    </div>
                    <x-admin.input name="salary_range" label="Salary range" :value="$job->salary_range" hint="Optional. Only include approved figures." maxlength="120" />
                </div>
            </div>

            <div class="a-card a-card-body space-y-6">
                <h2 class="a-card-title">Role details</h2>
                <x-admin.rich-editor name="description" label="About the role" :value="$job->description" compact />
                <x-admin.rich-editor name="responsibilities" label="Responsibilities" :value="$job->responsibilities" compact />
                <x-admin.rich-editor name="requirements" label="Requirements" :value="$job->requirements" compact />
                <x-admin.rich-editor name="nice_to_have" label="Nice to have" :value="$job->nice_to_have" compact />
                <x-admin.rich-editor name="benefits" label="Benefits" :value="$job->benefits" compact hint="Only list benefits that are confirmed." />
            </div>

            <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
                <h2 class="a-card-title sm:col-span-2">Applications</h2>
                <x-admin.select name="application_method" label="How candidates apply" :options="\App\Enums\ApplicationMethod::options()" :value="$job->application_method" required x-model="method" class="sm:col-span-2" />
                <div x-show="method === 'email'" x-cloak class="sm:col-span-2">
                    <x-admin.input name="application_email" label="Application email" type="email" :value="$job->application_email" />
                </div>
                <div x-show="method === 'external'" x-cloak class="sm:col-span-2">
                    <x-admin.input name="application_url" label="Application link" type="url" :value="$job->application_url" />
                </div>
                <x-admin.textarea name="application_instructions" label="Application instructions" :value="$job->application_instructions" rows="2" class="sm:col-span-2" />
                <x-admin.input name="application_deadline" label="Application deadline" type="datetime-local" :value="$job->application_deadline" hint="Leave blank to keep the role open until closed manually." />
                <x-admin.toggle name="auto_close" label="Close automatically after the deadline" :checked="$job->auto_close" class="sm:self-end" />
            </div>

            <x-admin.seo-panel :seo="$job->seo" :fallback-title="$job->title" :fallback-description="$job->summary" :url="$job->exists ? route('careers.show', $job) : url('/careers')" />

            @include('admin.partials.save-bar', ['label' => $job->exists ? 'Save changes' : 'Save draft', 'note' => 'Saving never changes the publication status.'])
        </form>

        <aside class="space-y-6">
            <x-admin.publication-panel :model="$job" type="job" />
            @if ($job->exists)
                <div class="a-card a-card-body text-sm">
                    <p class="a-mono a-subtle">Applications</p>
                    <p class="mt-2">{{ $job->isAcceptingApplications() ? 'Accepting applications' : ($job->closed_at ? 'Closed '.$job->closed_at->format('j M Y') : 'Not accepting applications') }}</p>
                </div>
            @endif
        </aside>
    </div>
@endsection
