@php
    $form = \App\Support\Onboarding\QuestionnaireForm::class;
    $name = "answers[{$sectionKey}][{$fieldKey}]";
    $dotted = "answers.{$sectionKey}.{$fieldKey}";
    $id = "q-{$sectionKey}-{$fieldKey}";
    $current = old($dotted, $value);
    $error = $errors->first($dotted) ?: $errors->first($dotted.'.*') ?: collect($errors->getMessages())->first(fn ($messages, $key) => str_starts_with($key, $dotted.'.'))[0] ?? null;
    $hint = $field['hint'] ?? null;
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
    $selected = collect((array) $current)->map(fn ($item) => is_string($item) ? $item : null)->filter()->all();
@endphp

<div class="ob-field" data-field="{{ $dotted }}">
    @switch($field['type'])
        @case('textarea')
            <label for="{{ $id }}" class="field-label">{{ $field['label'] }}@if ($required) <span class="accent" aria-hidden="true">*</span>@endif</label>
            @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-2.5">{{ $hint }}</p>@endif
            <textarea id="{{ $id }}" name="{{ $name }}" rows="4" class="field" maxlength="{{ $field['max'] ?? 2000 }}" @if ($required) aria-required="true" @endif @if ($error) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ is_string($current) ? $current : '' }}</textarea>
            @break

        @case('checkboxes')
        @case('radio')
            @php $multiple = $field['type'] === 'checkboxes'; @endphp
            <fieldset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($multiple && isset($field['max_items'])) data-max-choices="{{ $field['max_items'] }}" @endif>
                <legend class="field-label">{{ $field['label'] }}@if ($required) <span class="accent" aria-hidden="true">*</span>@endif</legend>
                @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-3">{{ $hint }}</p>@endif
                <div class="flex flex-wrap gap-2.5">
                    @foreach ($form::options($field) as $optionValue => $optionLabel)
                        <label class="ob-pill">
                            <input type="{{ $multiple ? 'checkbox' : 'radio' }}" class="ob-choice-input" name="{{ $name }}{{ $multiple ? '[]' : '' }}" value="{{ $optionValue }}" @checked(in_array((string) $optionValue, $selected, true))>
                            <span>{{ $optionLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @break

        @case('styles')
            <fieldset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif data-max-choices="{{ $field['max_items'] }}">
                <legend class="field-label">{{ $field['label'] }}@if ($required) <span class="accent" aria-hidden="true">*</span>@endif</legend>
                @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-3">{{ $hint }}</p>@endif
                <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                    @foreach ($form::STYLES as $styleKey => $style)
                        <label class="ob-card-choice">
                            <input type="checkbox" class="ob-choice-input" name="{{ $name }}[]" value="{{ $styleKey }}" @checked(in_array($styleKey, $selected, true))>
                            <x-onboarding.style-preview :preset="$style" />
                            <span class="mt-3 block font-semibold">{{ $style['label'] }}</span>
                            <span class="mt-1 block text-sm leading-snug text-muted">{{ $style['description'] }}</span>
                            <span class="ob-tick" aria-hidden="true"><x-icon name="check" :size="14" /></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @break

        @case('palettes')
            <fieldset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif data-max-choices="{{ $field['max_items'] }}">
                <legend class="field-label">{{ $field['label'] }}@if ($required) <span class="accent" aria-hidden="true">*</span>@endif</legend>
                @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-3">{{ $hint }}</p>@endif
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($form::PALETTES as $paletteKey => $palette)
                        <label class="ob-card-choice">
                            <input type="checkbox" class="ob-choice-input" name="{{ $name }}[]" value="{{ $paletteKey }}" @checked(in_array($paletteKey, $selected, true))>
                            <x-onboarding.palette :palette="$palette" />
                            <span class="mt-2.5 block text-sm font-semibold">{{ $palette['label'] }}</span>
                            <span class="ob-tick" aria-hidden="true"><x-icon name="check" :size="14" /></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @break

        @case('typography')
            <fieldset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <legend class="field-label">{{ $field['label'] }}@if ($required) <span class="accent" aria-hidden="true">*</span>@endif</legend>
                @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-3">{{ $hint }}</p>@endif
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($form::TYPOGRAPHY as $typeKey => $type)
                        <label class="ob-card-choice">
                            <input type="radio" class="ob-choice-input" name="{{ $name }}" value="{{ $typeKey }}" @checked($current === $typeKey)>
                            @if ($typeKey === 'none')
                                <span class="ob-type-sample"><span class="ob-type-sample-glyph text-subtle">?</span><span class="ob-type-sample-text">{{ $type['description'] }}</span></span>
                            @else
                                <x-onboarding.type-sample :type="$type" />
                            @endif
                            <span class="mt-3 block text-sm font-semibold">{{ $type['label'] }}</span>
                            @if ($typeKey !== 'none')<span class="block text-sm text-muted">{{ $type['description'] }}</span>@endif
                            <span class="ob-tick" aria-hidden="true"><x-icon name="check" :size="14" /></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @break

        @case('references')
            @php
                $rows = collect(is_array($current) ? $current : [])->filter(fn ($row) => is_array($row))->values();
                $visible = max(2, $rows->count() + 1);
            @endphp
            <fieldset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <legend class="field-label">{{ $field['label'] }}@if ($required) <span class="accent" aria-hidden="true">*</span>@endif</legend>
                @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-3">{{ $hint }}</p>@endif
                <ol class="space-y-3" data-reference-list>
                    @for ($i = 0; $i < $field['max_items']; $i++)
                        @php $rowError = $errors->first("{$dotted}.{$i}.url") ?: $errors->first("{$dotted}.{$i}.note"); @endphp
                        <li class="ob-reference {{ $i >= $visible ? 'ob-extra-row' : '' }}" data-reference-row>
                            <span class="ob-reference-number" aria-hidden="true">{{ $i + 1 }}</span>
                            <div class="grid flex-1 gap-2.5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
                                <div>
                                    <label for="{{ $id }}-{{ $i }}-url" class="sr-only">Link {{ $i + 1 }}</label>
                                    <input id="{{ $id }}-{{ $i }}-url" type="url" inputmode="url" name="{{ $name }}[{{ $i }}][url]" value="{{ $rows[$i]['url'] ?? '' }}" class="field" placeholder="https://" @if ($rowError) aria-invalid="true" @endif>
                                </div>
                                <div>
                                    <label for="{{ $id }}-{{ $i }}-note" class="sr-only">What you like about link {{ $i + 1 }}</label>
                                    <input id="{{ $id }}-{{ $i }}-note" type="text" name="{{ $name }}[{{ $i }}][note]" value="{{ $rows[$i]['note'] ?? '' }}" class="field" maxlength="500" placeholder="What do you like about it?">
                                </div>
                                @if ($rowError)<p class="field-error md:col-span-2">{{ $rowError }}</p>@endif
                            </div>
                        </li>
                    @endfor
                </ol>
                <button type="button" class="btn btn-secondary btn-sm js-only mt-4" data-reference-add><x-icon name="plus" :size="16" /> Add another link</button>
            </fieldset>
            @break

        @case('uploads')
            <div>
                <p class="field-label" id="{{ $id }}-label">{{ $field['label'] }}</p>
                @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-3">{{ $hint }}</p>@endif
                <label class="ob-dropzone" data-dropzone>
                    <x-icon name="upload" :size="26" />
                    <span class="mt-2 font-semibold">Choose files <span class="font-normal text-muted">or drag them here</span></span>
                    <span class="mt-1 text-sm text-subtle">Up to {{ config('dxcovr.onboarding.max_files') }} files in total</span>
                    <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,image/*,application/pdf" class="sr-only" aria-labelledby="{{ $id }}-label" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif data-ob-file-input>
                </label>
                @php $fileError = $errors->first('files') ?: $errors->first('files.*') ?: collect($errors->getMessages())->first(fn ($messages, $key) => str_starts_with($key, 'files.'))[0] ?? null; @endphp
                <p class="field-error" data-upload-error @if (! $fileError) hidden @endif>{{ $fileError }}</p>
                <ul class="ob-files mt-4" data-file-list>
                    @foreach ($questionnaire->files as $file)
                        <li class="ob-file" data-file-item>
                            @if ($file->isImage())
                                <img src="{{ route('onboarding.files.show', [$token, $file->id]) }}" alt="" class="ob-file-thumb" loading="lazy">
                            @else
                                <span class="ob-file-thumb grid place-items-center"><x-icon name="file" :size="22" /></span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $file->original_name }}</span>
                                <span class="block text-xs text-subtle">{{ $file->humanSize() }}</span>
                            </span>
                            <button type="submit" name="remove_file" value="{{ $file->id }}" formnovalidate class="ob-file-remove" data-remove-url="{{ route('onboarding.files.destroy', [$token, $file->id]) }}" aria-label="Remove {{ $file->original_name }}"><x-icon name="close" :size="16" /></button>
                        </li>
                    @endforeach
                </ul>
            </div>
            @break

        @case('consent')
            <div class="ob-consent">
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" class="checkbox" @checked((bool) $current) @if ($error) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <label for="{{ $id }}" class="text-sm leading-relaxed text-muted">{{ $field['label'] }} <span class="accent" aria-hidden="true">*</span></label>
            </div>
            @break

        @default
            <label for="{{ $id }}" class="field-label">{{ $field['label'] }}@if ($required) <span class="accent" aria-hidden="true">*</span>@endif</label>
            @if ($hint)<p id="{{ $id }}-hint" class="field-hint -mt-1 mb-2.5">{{ $hint }}</p>@endif
            <input id="{{ $id }}" name="{{ $name }}" type="{{ $field['type'] }}" value="{{ is_string($current) ? $current : '' }}" class="field {{ $field['type'] === 'date' ? 'max-w-xs' : '' }}"
                   @if (isset($field['max'])) maxlength="{{ $field['max'] }}" @endif
                   @if ($field['type'] === 'email') autocomplete="email" @elseif ($fieldKey === 'contact_name') autocomplete="name" @elseif ($field['type'] === 'tel') autocomplete="tel" @elseif ($fieldKey === 'company') autocomplete="organization" @endif
                   @if ($required) aria-required="true" @endif @if ($error) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
    @endswitch

    @if ($error && $field['type'] !== 'references')
        <p id="{{ $id }}-error" class="field-error">{{ $error }}</p>
    @elseif ($error)
        <p id="{{ $id }}-error" class="sr-only">{{ $error }}</p>
    @endif
</div>
