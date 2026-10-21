@props(['name', 'label', 'rows' => [], 'fields' => [], 'hint' => null, 'max' => 20, 'addLabel' => 'Add item'])
{{--
    Repeating groups of fields. $fields: [key => ['label' => …, 'type' => text|textarea|url|select|media|hidden, 'options' => […]]]
    Hidden fields carry a value with each row (e.g. a stored key) without showing it.
--}}
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $current = array_values((array) old($key, $rows ?? []));
    $blank = collect($fields)->map(fn ($definition) => ($definition['type'] ?? 'text') === 'select' ? (string) array_key_first($definition['options'] ?? ['' => '']) : '')->all();
    $current = array_map(fn ($row) => array_merge($blank, array_map(fn ($value) => $value ?? '', (array) $row)), $current);

    // Thumbnails for media sub-fields, loaded in one query.
    $mediaFields = collect($fields)->filter(fn ($definition) => ($definition['type'] ?? 'text') === 'media')->keys();
    if ($mediaFields->isNotEmpty()) {
        $ids = collect($current)->flatMap(fn ($row) => $mediaFields->map(fn ($field) => $row[$field] ?? null))->filter()->unique();
        $library = $ids->isEmpty() ? collect() : \App\Models\Media::query()->whereIn('id', $ids)->get()->keyBy('id');
        $current = array_map(function ($row) use ($mediaFields, $library) {
            foreach ($mediaFields as $field) {
                $row[$field.'__thumb'] = $library->get((int) ($row[$field] ?? 0))?->variantUrl(480) ?? '';
            }

            return $row;
        }, $current);
    }
    $error = collect($errors->getMessages())->filter(fn ($messages, $field) => str_starts_with($field, $key))->flatten()->first();
@endphp
<div {{ $attributes }} x-data="repeater(@js($current), @js($blank), {{ (int) $max }})">
    <div class="flex items-center justify-between gap-3">
        <p class="a-label">{{ $label }}</p>
        <span class="text-xs a-subtle"><span x-text="rows.length"></span>/{{ $max }}</span>
    </div>
    @if ($hint)
        <p class="a-hint -mt-1 mb-2">{{ $hint }}</p>
    @endif
    <input type="hidden" name="{{ $name }}" value="" x-show="false">
    <ol x-ref="rows" class="space-y-3">
        <template x-for="(row, index) in rows" :key="index">
            <li class="rounded-xl border border-line bg-ink-2 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <span class="a-mono a-subtle" x-text="'#' + (index + 1)"></span>
                    <div class="flex gap-1">
                        <button type="button" class="a-icon-btn !size-7" @click="move(index, -1)" :disabled="index === 0" aria-label="Move up"><x-icon name="arrow-up" :size="14" /></button>
                        <button type="button" class="a-icon-btn !size-7" @click="move(index, 1)" :disabled="index === rows.length - 1" aria-label="Move down"><x-icon name="arrow-down" :size="14" /></button>
                        <button type="button" class="a-icon-btn !size-7" @click="remove(index)" aria-label="Remove item"><x-icon name="trash" :size="14" /></button>
                    </div>
                </div>
                @php $visibleFields = collect($fields)->reject(fn ($definition) => ($definition['type'] ?? 'text') === 'hidden'); @endphp
                @foreach ($fields as $field => $definition)
                    @if (($definition['type'] ?? 'text') === 'hidden')
                        <input type="hidden" :name="`{{ $name }}[${index}][{{ $field }}]`" :value="row.{{ $field }}">
                    @endif
                @endforeach
                <div class="grid gap-3 {{ $visibleFields->count() > 1 ? 'sm:grid-cols-2' : '' }}">
                    @foreach ($visibleFields as $field => $definition)
                        @php
                            $type = $definition['type'] ?? 'text';
                        @endphp
                        <div class="{{ in_array($type, ['textarea', 'media'], true) ? 'sm:col-span-2' : '' }}">
                            @if ($type === 'media')
                                <p class="a-label !text-xs">{{ $definition['label'] }}</p>
                            @else
                                <label class="a-label !text-xs" :for="`{{ $key }}-${index}-{{ $field }}`">{{ $definition['label'] }}</label>
                            @endif
                            @if ($type === 'media')
                                <div class="flex items-center gap-3 rounded-lg border border-line bg-ink p-2">
                                    <input type="hidden" :name="`{{ $name }}[${index}][{{ $field }}]`" :value="row.{{ $field }}">
                                    <span class="a-thumb a-checker size-12 flex-none !rounded-lg">
                                        <img x-show="row.{{ $field }}__thumb" :src="row.{{ $field }}__thumb" alt="">
                                    </span>
                                    <button type="button" class="a-btn a-btn-sm" @click="chooseMedia(row, '{{ $field }}')" x-text="row.{{ $field }} ? 'Replace image' : 'Choose image'"></button>
                                    <button type="button" class="a-btn a-btn-sm a-btn-ghost" x-show="row.{{ $field }}" @click="row.{{ $field }} = ''; row.{{ $field }}__thumb = ''">Remove</button>
                                </div>
                            @elseif ($type === 'textarea')
                                <textarea :id="`{{ $key }}-${index}-{{ $field }}`" :name="`{{ $name }}[${index}][{{ $field }}]`" x-model="row.{{ $field }}" rows="2" class="a-input" maxlength="{{ $definition['max'] ?? 1000 }}"></textarea>
                            @elseif ($type === 'select')
                                <select :id="`{{ $key }}-${index}-{{ $field }}`" :name="`{{ $name }}[${index}][{{ $field }}]`" x-model="row.{{ $field }}" class="a-input">
                                    @foreach ($definition['options'] ?? [] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input :id="`{{ $key }}-${index}-{{ $field }}`" type="text" :name="`{{ $name }}[${index}][{{ $field }}]`" x-model="row.{{ $field }}" class="a-input" maxlength="{{ $definition['max'] ?? 255 }}" @if ($type === 'url') placeholder="/page or https://…" @endif>
                            @endif
                        </div>
                    @endforeach
                </div>
            </li>
        </template>
    </ol>
    <button type="button" class="a-btn a-btn-sm mt-3" @click="add()" :disabled="rows.length >= max"><x-icon name="plus" :size="14" /> {{ $addLabel }}</button>
    @if ($error)
        <p class="a-error">{{ $error }}</p>
    @endif
</div>
