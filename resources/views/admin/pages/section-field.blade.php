@php
    $type = $config['type'];
    $wide = in_array($type, ['textarea', 'richtext', 'repeater', 'media'], true);
    $class = $wide ? 'sm:col-span-2' : '';
    $required = ! empty($config['required']);
@endphp
@switch($type)
    @case('textarea')
        <x-admin.textarea :name="$name" :label="$config['label']" :value="$value" :required="$required" :rows="3" :hint="$config['help'] ?? null" :class="$class" maxlength="{{ $config['max'] ?? 2000 }}" />
        @break
    @case('richtext')
        <x-admin.rich-editor :name="$name" :label="$config['label']" :value="$value" :required="$required" compact :class="$class" />
        @break
    @case('url')
        <x-admin.input :name="$name" :label="$config['label']" :value="$value" :required="$required" :hint="$config['help'] ?? 'A page on this site (e.g. /contact) or a full https:// address.'" :class="$class" maxlength="500" />
        @break
    @case('media')
        @php
            $media = $value ? \App\Models\Media::query()->find($value) : null;
        @endphp
        <x-admin.media-field :name="$name" :label="$config['label']" :media="$media" :hint="$config['help'] ?? null" :class="$class" />
        @break
    @case('toggle')
        <x-admin.toggle :name="$name" :label="$config['label']" :checked="(bool) $value" :hint="$config['help'] ?? null" :class="$class" />
        @break
    @case('number')
        <x-admin.input :name="$name" :label="$config['label']" type="number" :value="$value" :required="$required" :class="$class" min="{{ $config['min'] ?? 0 }}" max="{{ $config['max'] ?? 100 }}" />
        @break
    @case('select')
        <x-admin.select :name="$name" :label="$config['label']" :options="$config['options']" :value="$value" :required="$required" :class="$class" />
        @break
    @case('repeater')
        <x-admin.repeater :name="$name" :label="$config['label']" :rows="is_array($value) ? $value : []" :max="$config['max_items'] ?? 20" :class="$class" :hint="$config['help'] ?? null"
            :fields="collect($config['fields'])->map(fn ($sub) => ['label' => $sub['label'], 'type' => $sub['type'] === 'url' ? 'url' : $sub['type'], 'options' => $sub['options'] ?? [], 'max' => $sub['max'] ?? null])->all()" />
        @break
    @default
        <x-admin.input :name="$name" :label="$config['label']" :value="$value" :required="$required" :hint="$config['help'] ?? null" :class="$class" maxlength="{{ $config['max'] ?? 255 }}" />
@endswitch
