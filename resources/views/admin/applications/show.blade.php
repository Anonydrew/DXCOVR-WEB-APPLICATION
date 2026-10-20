@extends('layouts.admin')

@section('title', 'Application · '.$application->name)

@section('content')
    <x-admin.page-header :title="$application->name" :description="'Applied for '.$application->jobListing?->title.' · '.$application->created_at->format('j M Y, H:i')" :back="route('admin.applications.index')">
        <x-slot:actions>
            <a href="{{ route('admin.applications.cv', $application) }}" class="a-btn a-btn-primary"><x-icon name="download" :size="16" /> Download CV</a>
            @can('applications.delete')
                <x-admin.delete-button :action="route('admin.applications.destroy', $application)" confirm="Permanently delete this application, its notes and the CV file? This cannot be undone." label="Delete" />
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-6">
            <section class="a-card" aria-labelledby="applicant-heading">
                <div class="a-card-header"><h2 id="applicant-heading" class="a-card-title">Applicant</h2><x-admin.status-badge :status="$application->stage" /></div>
                <dl class="a-card-body grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="a-mono a-subtle">Email</dt><dd class="mt-1"><a href="mailto:{{ $application->email }}" class="hover:text-lime">{{ $application->email }}</a></dd></div>
                    <div><dt class="a-mono a-subtle">Phone</dt><dd class="mt-1">{{ $application->phone ?: 'Not set' }}</dd></div>
                    <div><dt class="a-mono a-subtle">Portfolio / profile</dt><dd class="mt-1 break-all">
                        @if ($application->portfolio_url)
                            <a href="{{ $application->portfolio_url }}" target="_blank" rel="noopener noreferrer nofollow" class="hover:text-lime">{{ $application->portfolio_url }}</a>
                        @else Not set @endif
                    </dd></div>
                    <div><dt class="a-mono a-subtle">CV</dt><dd class="mt-1">{{ $application->cv_original_name }} · {{ $application->cvHumanSize() }}</dd></div>
                    <div><dt class="a-mono a-subtle">Reference</dt><dd class="mt-1 font-mono text-xs">{{ $application->reference }}</dd></div>
                    <div><dt class="a-mono a-subtle">Consent given</dt><dd class="mt-1">{{ $application->consent_given_at->format('j M Y, H:i') }}</dd></div>
                </dl>
            </section>

            <section class="a-card" aria-labelledby="letter-heading">
                <div class="a-card-header"><h2 id="letter-heading" class="a-card-title">Cover letter</h2></div>
                <div class="a-card-body whitespace-pre-line text-sm leading-relaxed">{{ $application->cover_letter }}</div>
            </section>

            <section class="a-card" aria-labelledby="notes-heading">
                <div class="a-card-header"><h2 id="notes-heading" class="a-card-title">Internal notes</h2></div>
                <div class="a-card-body space-y-4">
                    @can('applications.update')
                        <form method="POST" action="{{ route('admin.applications.notes.store', $application) }}" class="space-y-2">
                            @csrf
                            <label for="note-body" class="sr-only">Add a note</label>
                            <textarea id="note-body" name="body" rows="3" class="a-input" placeholder="Add a note for the hiring team (never shared with the applicant)" maxlength="4000" required></textarea>
                            @error('body')<p class="a-error">{{ $message }}</p>@enderror
                            <button type="submit" class="a-btn a-btn-sm">Add note</button>
                        </form>
                    @endcan
                    @forelse ($application->notes as $note)
                        <article class="rounded-xl border border-line bg-ink-2 p-3 text-sm">
                            <p class="whitespace-pre-line">{{ $note->body }}</p>
                            <p class="mt-2 text-xs a-subtle">{{ $note->user?->name ?? 'Former user' }} · {{ $note->created_at->format('j M Y, H:i') }}</p>
                        </article>
                    @empty
                        <p class="text-sm a-subtle">No notes yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            @can('applications.update')
                <form method="POST" action="{{ route('admin.applications.stage', $application) }}" class="a-card a-card-body space-y-4" x-data="{ notify: false }">
                    @csrf
                    @method('PATCH')
                    <h2 class="a-card-title">Update stage</h2>
                    <x-admin.select name="stage" label="Stage" :options="\App\Enums\ApplicationStage::options()" :value="$application->stage" required />
                    <x-admin.textarea name="note" label="Internal note (optional)" rows="2" />
                    <label class="flex items-start gap-2.5 text-sm">
                        <input type="hidden" name="notify_applicant" value="0">
                        <input type="checkbox" name="notify_applicant" value="1" class="a-checkbox" x-model="notify">
                        <span>Email the applicant about this change</span>
                    </label>
                    <div x-show="notify" x-cloak>
                        <x-admin.textarea name="message_to_applicant" label="Personal message (optional)" rows="3" hint="Added to the standard update email." />
                    </div>
                    <button type="submit" class="a-btn a-btn-primary w-full">Save stage</button>
                </form>
            @endcan

            <section class="a-card" aria-labelledby="history-heading">
                <div class="a-card-header"><h2 id="history-heading" class="a-card-title">History</h2></div>
                <ol class="a-card-body space-y-4 text-sm">
                    @foreach ($application->stageChanges as $change)
                        <li class="border-l-2 border-line-strong pl-3">
                            <p><span class="font-semibold">{{ $change->to_stage->label() }}</span>@if ($change->from_stage) <span class="a-subtle">from {{ $change->from_stage->label() }}</span>@endif</p>
                            @if ($change->note)
                                <p class="mt-1 a-muted">{{ $change->note }}</p>
                            @endif
                            <p class="mt-1 text-xs a-subtle">{{ $change->user?->name ?? 'System' }} · {{ $change->created_at->format('j M Y, H:i') }}@if ($change->applicant_notified) · applicant emailed @endif</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            <p class="text-xs a-subtle">This application will be deleted automatically {{ $retentionDays }} days after a final decision (or after submission if no decision is recorded).</p>
        </aside>
    </div>
@endsection
