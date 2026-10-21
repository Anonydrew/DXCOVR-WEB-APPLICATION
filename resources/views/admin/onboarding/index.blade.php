@extends('layouts.admin')

@section('title', 'Client onboarding')

@section('content')
    <x-admin.page-header title="Client onboarding" description="Private questionnaires your clients complete before a project starts: goals, audience, style inspirations, references and files.">
        <x-slot:actions>
            @can('onboarding.manage')
                <a href="{{ route('admin.onboarding.questions.index') }}" class="a-btn"><x-icon name="edit" :size="16" /> Edit questions</a>
                <a href="{{ route('admin.onboarding.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New questionnaire</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Status">
        <a href="{{ route('admin.onboarding.index') }}" class="a-btn a-btn-sm {{ ! request('status') ? 'a-btn-primary' : '' }}">All</a>
        @foreach (\App\Enums\QuestionnaireStatus::cases() as $status)
            <a href="{{ route('admin.onboarding.index', ['status' => $status->value]) }}" class="a-btn a-btn-sm {{ request('status') === $status->value ? 'a-btn-primary' : '' }}">{{ $status->label() }} ({{ $statusCounts[$status->value] ?? 0 }})</a>
        @endforeach
    </nav>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.onboarding.index')" placeholder="Search client, email, organisation or project">
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
        </x-admin.filters>

        @if ($questionnaires->isEmpty())
            <div class="p-6">
                <x-admin.empty-state title="No questionnaires yet" icon="sparkle">
                    Create a questionnaire for a client, then share its private link by email or WhatsApp.
                </x-admin.empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th scope="col">Client</th><th scope="col">Project</th><th scope="col">Status</th><th scope="col">Progress</th><th scope="col">Last activity</th></tr></thead>
                    <tbody>
                        @foreach ($questionnaires as $questionnaire)
                            @php $progress = $questionnaire->progress(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.onboarding.show', $questionnaire) }}" class="font-semibold hover:text-lime">
                                        @if ($questionnaire->isSubmitted() && ! $questionnaire->reviewed_at)<span class="mr-1.5 inline-block size-2 rounded-full bg-lime" aria-label="Not yet reviewed"></span>@endif
                                        {{ $questionnaire->client_name }}
                                    </a>
                                    <p class="text-xs a-subtle">{{ $questionnaire->client_company ?: $questionnaire->client_email }}</p>
                                </td>
                                <td class="a-muted">{{ $questionnaire->project_name }}</td>
                                <td>
                                    <x-admin.status-badge :status="$questionnaire->status" :at="$questionnaire->submitted_at" />
                                    @if ($questionnaire->isExpired() && ! $questionnaire->isSubmitted())
                                        <span class="a-badge a-badge-danger ml-1">Link expired</span>
                                    @endif
                                </td>
                                <td class="min-w-36">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-1.5 w-20 overflow-hidden rounded-full bg-surface-3" aria-hidden="true"><div class="h-full rounded-full bg-lime" style="width: {{ $progress['total'] ? round($progress['answered'] / $progress['total'] * 100) : 0 }}%"></div></div>
                                        <span class="text-xs a-muted">{{ $progress['answered'] }}/{{ $progress['total'] }} sections</span>
                                    </div>
                                    @if ($questionnaire->files_count)
                                        <p class="mt-1 text-xs a-subtle">{{ trans_choice(':count file|:count files', $questionnaire->files_count) }}</p>
                                    @endif
                                </td>
                                @php $activity = $questionnaire->submitted_at ?? $questionnaire->last_saved_at ?? $questionnaire->first_opened_at; @endphp
                                <td class="whitespace-nowrap text-sm a-muted">
                                    @if ($activity)
                                        <span title="{{ $activity->format('j M Y H:i') }}">{{ $activity->diffForHumans() }}</span>
                                    @else
                                        Not opened yet
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $questionnaires->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
