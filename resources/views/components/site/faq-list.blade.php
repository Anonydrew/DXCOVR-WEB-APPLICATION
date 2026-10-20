@props(['faqs'])
<div {{ $attributes->merge(['class' => 'divide-y divide-line border-y border-line']) }}>
    @foreach ($faqs as $faq)
        <details class="disclosure group">
            <summary class="flex items-start justify-between gap-6 py-6 text-left">
                <h3 class="heading-md">{{ $faq->question }}</h3>
                <span class="mt-1 grid size-8 flex-none place-items-center rounded-full border border-field">
                    <x-icon name="plus" :size="16" class="disclosure-icon" />
                </span>
            </summary>
            <div class="prose-dx text-muted pb-7">{!! $faq->answer !!}</div>
        </details>
    @endforeach
</div>
