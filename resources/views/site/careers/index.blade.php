@extends('layouts.site')

@section('content')
    <x-site.page-hero
        eyebrow="Careers"
        :title="$page?->title ?? 'Build what is next with us'"
        :lede="$page?->excerpt"
        :breadcrumbs="$seo->breadcrumbItems()"
        glyph="design"
    />

    <section id="open-roles" class="surface-dark pb-24 sm:pb-32" aria-labelledby="open-roles-heading">
        <div class="container-dx">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="eyebrow accent">Open roles</p>
                    <h2 id="open-roles-heading" class="display-md mt-4">Current opportunities</h2>
                </div>
                <form method="GET" action="{{ route('careers.index') }}#open-roles" class="flex flex-wrap items-end gap-3" aria-label="Filter roles">
                    @if ($departments->isNotEmpty())
                        <div>
                            <label for="filter-department" class="field-label">Department</label>
                            <select id="filter-department" name="department" class="field min-w-44" data-autosubmit>
                                <option value="">All departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->slug }}" @selected($filters['department'] === $department->slug)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label for="filter-type" class="field-label">Type</label>
                        <select id="filter-type" name="type" class="field min-w-40" data-autosubmit>
                            <option value="">All types</option>
                            @foreach (\App\Enums\EmploymentType::options() as $value => $label)
                                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-arrangement" class="field-label">Work arrangement</label>
                        <select id="filter-arrangement" name="arrangement" class="field min-w-40" data-autosubmit>
                            <option value="">Any arrangement</option>
                            @foreach (\App\Enums\WorkArrangement::options() as $value => $label)
                                <option value="{{ $value }}" @selected($filters['arrangement'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    @if ($hasFilters)
                        <a href="{{ route('careers.index') }}#open-roles" class="btn btn-sm text-muted hover:text-text">Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-12">
                @if ($jobs->isEmpty())
                    <x-site.empty-state :title="$hasFilters ? 'No roles match these filters' : 'No open roles right now'" glyph="design">
                        @if ($hasFilters)
                            Try a different combination, or clear the filters to see every open role.
                        @else
                            We are not actively hiring at the moment. New roles are published here first.
                            @if ($site->get('careers.general_applications_email'))
                                If you would like us to keep you in mind, email <a class="link-underline text-text" href="mailto:{{ $site->get('careers.general_applications_email') }}">{{ $site->get('careers.general_applications_email') }}</a>.
                            @endif
                        @endif
                    </x-site.empty-state>
                @else
                    <ul class="divide-y divide-line border-y border-line">
                        @foreach ($jobs as $job)
                            <li class="index-row group">
                                <div class="grid gap-4 py-7 sm:grid-cols-12 sm:items-center">
                                    <div class="sm:col-span-6">
                                        <h3 class="index-title heading-lg">
                                            <a href="{{ route('careers.show', $job) }}" class="stretched-link">{{ $job->title }}</a>
                                        </h3>
                                        <p class="mt-2 text-muted line-clamp-2">{{ $job->summary }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 sm:col-span-5">
                                        @if ($job->department)
                                            <span class="chip">{{ $job->department->name }}</span>
                                        @endif
                                        <span class="chip">{{ $job->employmentTypeLabel() }}</span>
                                        <span class="chip">{{ $job->work_arrangement->label() }}</span>
                                        <span class="chip"><x-icon name="map-pin" :size="14" /> {{ $job->location }}</span>
                                        @if ($job->application_deadline)
                                            <span class="chip">Closes {{ $job->application_deadline->format('j M Y') }}</span>
                                        @endif
                                    </div>
                                    <div class="hidden justify-end sm:col-span-1 sm:flex" aria-hidden="true">
                                        <x-icon name="arrow-up-right" :size="24" class="text-muted transition duration-500 group-hover:rotate-45 group-hover:text-lime" />
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    @include('site.sections._render')
@endsection
