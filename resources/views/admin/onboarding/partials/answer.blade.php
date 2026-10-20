@php
    $form = \App\Support\Onboarding\QuestionnaireForm::class;
    $options = $form::options($field);
@endphp
@switch($field['type'])
    @case('textarea')
        <p class="whitespace-pre-line text-sm leading-relaxed">{{ $value }}</p>
        @break

    @case('url')
        <a href="{{ $value }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all text-sm text-lime hover:underline">{{ $value }}</a>
        @break

    @case('email')
        <a href="mailto:{{ $value }}" class="text-sm hover:text-lime">{{ $value }}</a>
        @break

    @case('date')
        <p class="text-sm">{{ \Illuminate\Support\Carbon::parse($value)->format('j F Y') }}</p>
        @break

    @case('checkboxes')
    @case('radio')
        <ul class="flex flex-wrap gap-2">
            @foreach ((array) $value as $choice)
                <li class="a-badge">{{ $options[$choice] ?? $choice }}</li>
            @endforeach
        </ul>
        @break

    @case('styles')
        <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
            @foreach ((array) $value as $choice)
                @if (isset($form::STYLES[$choice]))
                    <li>
                        <x-onboarding.style-preview :preset="$form::STYLES[$choice]" />
                        <p class="mt-2 text-sm font-semibold">{{ $form::STYLES[$choice]['label'] }}</p>
                        <p class="text-xs a-subtle">{{ $form::STYLES[$choice]['description'] }}</p>
                    </li>
                @endif
            @endforeach
        </ul>
        @break

    @case('palettes')
        <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
            @foreach ((array) $value as $choice)
                @if (isset($form::PALETTES[$choice]))
                    <li>
                        <x-onboarding.palette :palette="$form::PALETTES[$choice]" />
                        <p class="mt-2 text-sm font-semibold">{{ $form::PALETTES[$choice]['label'] }}</p>
                        <p class="font-mono text-[0.7rem] a-subtle">{{ implode(' · ', $form::PALETTES[$choice]['colours']) }}</p>
                    </li>
                @endif
            @endforeach
        </ul>
        @break

    @case('typography')
        @if (isset($form::TYPOGRAPHY[$value]))
            <div class="max-w-sm rounded-xl border border-line bg-ink-2 p-4">
                @unless ($value === 'none')
                    <x-onboarding.type-sample :type="$form::TYPOGRAPHY[$value]" />
                @endunless
                <p class="mt-2 text-sm font-semibold">{{ $form::TYPOGRAPHY[$value]['label'] }}</p>
            </div>
        @endif
        @break

    @case('references')
        <ol class="space-y-3">
            @foreach ((array) $value as $reference)
                <li class="rounded-xl border border-line bg-ink-2 p-3.5">
                    <a href="{{ $reference['url'] }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex max-w-full items-center gap-1.5 break-all text-sm font-medium text-lime hover:underline">
                        {{ $reference['url'] }} <x-icon name="external" :size="13" class="flex-none" />
                    </a>
                    @if (! empty($reference['note']))
                        <p class="mt-1.5 whitespace-pre-line text-sm a-muted">{{ $reference['note'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>
        @break

    @case('consent')
        <p class="flex items-center gap-2 text-sm"><x-icon name="check" :size="16" class="text-lime" /> Confirmed by the client</p>
        @break

    @default
        <p class="text-sm">{{ $value }}</p>
@endswitch
