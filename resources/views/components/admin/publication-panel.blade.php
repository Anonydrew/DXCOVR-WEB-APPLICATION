@props(['model', 'type', 'canPublish' => true, 'canSubmit' => false])
{{--
    Publication controls. Saving the form never changes status: publishing,
    scheduling, unpublishing and archiving are separate, explicit actions.
--}}
@php
    use App\Enums\PublicationStatus;
    $status = $model->status ?? PublicationStatus::Draft;
    $missing = $model->exists ? $model->missingForPublication() : [];
    $action = $model->exists ? route('admin.publication', [$type, $model->id]) : null;
    $reviewEnabled = app(\App\Services\SiteSettings::class)->get('editorial.review_enabled', true);
@endphp
<div class="a-card" {{ $attributes }}>
    <div class="a-card-header">
        <span class="a-card-title">Publishing</span>
        <x-admin.status-badge :status="$status" />
    </div>
    <div class="a-card-body space-y-4 text-sm">
        @if (! $model->exists)
            <p class="a-muted">Save as a draft first. Publishing options appear once the draft exists.</p>
        @else
            <dl class="space-y-1.5">
                @if ($status === PublicationStatus::Scheduled)
                    <div class="flex justify-between gap-3"><dt class="a-subtle">Publishes</dt><dd class="font-medium text-info">{{ $model->published_at?->format('j M Y, H:i') }}</dd></div>
                @elseif ($model->published_at)
                    <div class="flex justify-between gap-3"><dt class="a-subtle">First published</dt><dd>{{ $model->published_at->format('j M Y, H:i') }}</dd></div>
                @endif
                <div class="flex justify-between gap-3"><dt class="a-subtle">Last updated</dt><dd>{{ $model->updated_at?->diffForHumans() }}</dd></div>
                @if ($model->relationLoaded('editor') && $model->editor)
                    <div class="flex justify-between gap-3"><dt class="a-subtle">By</dt><dd>{{ $model->editor->name }}</dd></div>
                @endif
            </dl>

            @if ($missing !== [] && $status !== PublicationStatus::Published)
                <div class="a-alert a-alert-warning">
                    <x-icon name="alert" :size="16" class="mt-0.5 flex-none text-warning" />
                    <div>
                        <p class="font-semibold">Before publishing</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4">
                            @foreach ($missing as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="space-y-2">
                @if ($canPublish && $status !== PublicationStatus::Published)
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        <input type="hidden" name="action" value="publish">
                        <button type="submit" class="a-btn a-btn-primary w-full" data-confirm="Publish this now? It will be visible to the public immediately." @disabled($missing !== [])>
                            <x-icon name="send" :size="16" /> Publish now
                        </button>
                    </form>

                    <form method="POST" action="{{ $action }}" class="rounded-lg border border-line p-3" x-data="{ open: {{ $status === PublicationStatus::Scheduled || $errors->has('publish_at') ? 'true' : 'false' }} }">
                        @csrf
                        <input type="hidden" name="action" value="schedule">
                        <button type="button" class="flex w-full items-center justify-between font-semibold" @click="open = ! open" :aria-expanded="open">
                            <span class="flex items-center gap-2"><x-icon name="calendar" :size="16" /> {{ $status === PublicationStatus::Scheduled ? 'Change schedule' : 'Schedule' }}</span>
                            <x-icon name="chevron-down" :size="14" />
                        </button>
                        <div x-show="open" x-cloak class="mt-3 space-y-2">
                            <label for="publish-at-{{ $type }}" class="a-label">Publish on ({{ config('app.timezone') }})</label>
                            <input id="publish-at-{{ $type }}" type="datetime-local" name="publish_at" class="a-input" value="{{ old('publish_at', $status === PublicationStatus::Scheduled ? $model->published_at?->format('Y-m-d\TH:i') : now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i')) }}" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                            @error('publish_at')<p class="a-error">{{ $message }}</p>@enderror
                            <button type="submit" class="a-btn w-full" @disabled($missing !== [])>Save schedule</button>
                        </div>
                    </form>
                @endif

                @if ($canSubmit && ! $canPublish && in_array($status, [PublicationStatus::Draft, PublicationStatus::Archived], true))
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        <input type="hidden" name="action" value="submit">
                        <button type="submit" class="a-btn a-btn-primary w-full" @disabled($missing !== [])>
                            <x-icon name="send" :size="16" /> Submit for review
                        </button>
                    </form>
                    @if (! $reviewEnabled)
                        <p class="text-xs a-subtle">An editor needs to publish your work.</p>
                    @endif
                @endif

                @if ($canPublish && in_array($status, [PublicationStatus::Published, PublicationStatus::Scheduled, PublicationStatus::InReview], true))
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        <input type="hidden" name="action" value="unpublish">
                        <button type="submit" class="a-btn w-full" data-confirm="{{ $status === PublicationStatus::Published ? 'Unpublish this? It will no longer be visible to the public.' : 'Return this to draft?' }}">
                            {{ $status === PublicationStatus::Published ? 'Unpublish (move to draft)' : ($status === PublicationStatus::Scheduled ? 'Cancel schedule' : 'Return to draft') }}
                        </button>
                    </form>
                @endif

                @if ($canPublish && $status !== PublicationStatus::Archived)
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        <input type="hidden" name="action" value="archive">
                        <button type="submit" class="a-btn a-btn-ghost w-full" data-confirm="Archive this? It will be hidden from the public but kept in the CMS.">Archive</button>
                    </form>
                @endif

                <form method="POST" action="{{ $action }}" target="_blank">
                    @csrf
                    <input type="hidden" name="action" value="preview">
                    <button type="submit" class="a-btn a-btn-ghost w-full"><x-icon name="eye" :size="16" /> Preview{{ $status === PublicationStatus::Published ? ' (live version)' : '' }}</button>
                </form>
                @if ($status === PublicationStatus::Published && method_exists($model, 'publicUrl'))
                    <a href="{{ $model->publicUrl() }}" target="_blank" rel="noopener" class="a-btn a-btn-ghost w-full"><x-icon name="external" :size="16" /> View on site</a>
                @endif
            </div>
        @endif
    </div>
</div>
