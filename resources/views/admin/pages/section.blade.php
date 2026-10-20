@php
    $definition = \App\Support\Sections\SectionRegistry::definition($section->type);
    $base = 'sections['.$section->id.']';
    $dotBase = 'sections.'.$section->id;
    $hasErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, $dotBase.'.'));
    $isOpen = $hasErrors || (int) session('open_section') === $section->id;
@endphp
<li id="section-{{ $section->id }}" data-id="{{ $section->id }}" class="a-card scroll-mt-24" x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }">
    <div class="flex items-center gap-3 px-4 py-3">
        <span class="a-drag-handle a-icon-btn" aria-hidden="true" title="Drag to reorder"><x-icon name="drag" :size="16" /></span>
        <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-left" @click="open = ! open" :aria-expanded="open" aria-controls="section-body-{{ $section->id }}">
            <span class="min-w-0">
                <span class="block truncate font-semibold">{{ $section->displayLabel() }}</span>
                <span class="block text-xs a-subtle">{{ $section->typeLabel() }}</span>
            </span>
        </button>
        @if ($hasErrors)
            <span class="a-badge a-badge-danger">Check fields</span>
        @endif
        <form method="POST" action="{{ route('admin.sections.toggle', $section) }}">
            @csrf
            <button type="submit" class="a-badge {{ $section->is_enabled ? 'a-badge-success' : 'a-badge-muted' }} cursor-pointer" title="Click to {{ $section->is_enabled ? 'hide' : 'show' }}">{{ $section->is_enabled ? 'Visible' : 'Hidden' }}</button>
        </form>
        <button type="button" class="a-icon-btn" @click="open = ! open" :aria-label="open ? 'Collapse section' : 'Expand section'"><x-icon name="chevron-down" :size="16" ::class="open && 'rotate-180'" /></button>
    </div>

    <div id="section-body-{{ $section->id }}" x-show="open" x-cloak class="border-t border-line">
        @if ($definition)
            <form method="POST" action="{{ route('admin.sections.update', $section) }}" class="a-card-body space-y-5">
                @csrf
                @method('PUT')
                <p class="text-sm a-muted">{{ $definition['description'] }}</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input :name="$base.'[label]'" label="Internal label (optional)" :value="$section->label" hint="Helps you find this section in the list. Not shown on the website." maxlength="120" class="sm:col-span-2" />
                    @foreach ($definition['fields'] as $field => $config)
                        @include('admin.pages.section-field', [
                            'name' => $base.'[data]['.$field.']',
                            'config' => $config,
                            'value' => $section->value($field, $config['default'] ?? null),
                        ])
                    @endforeach
                    <x-admin.toggle :name="$base.'[is_enabled]'" label="Show this section on the website" :checked="$section->is_enabled" class="sm:col-span-2" />
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                    <button type="submit" class="a-btn a-btn-primary">Save section</button>
                </div>
            </form>
            <div class="flex justify-end px-5 pb-5">
                <x-admin.delete-button :action="route('admin.sections.destroy', $section)" confirm="Remove this section from the page?" label="Remove section" />
            </div>
        @else
            <p class="a-card-body text-sm text-warning">Unknown section type “{{ $section->type }}”. It is not displayed.</p>
        @endif
    </div>
</li>
