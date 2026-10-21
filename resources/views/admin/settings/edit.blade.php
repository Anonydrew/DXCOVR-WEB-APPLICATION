@extends('layouts.admin')

@section('title', $definition['label'].' settings')

@section('content')
    <x-admin.page-header title="Settings" :description="$definition['description']" />

    @include('admin.partials.tabs', ['tabs' => $groups->map(fn ($label, $key) => ['label' => $label, 'route' => 'admin.settings.edit', 'params' => ['group' => $key]])->values()->all()])

    <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="max-w-3xl space-y-6" data-dirty-guard>
        @csrf
        @method('PUT')

        <div class="a-card a-card-body space-y-5">
            @foreach ($definition['fields'] as $key => $field)
                @php
                    $name = 'settings['.str_replace('.', '-', $key).']';
                    $value = $values[$key];
                @endphp
                @switch($field['type'])
                    @case('textarea')
                        <x-admin.textarea :name="$name" :label="$field['label']" :value="$value" :hint="$field['hint'] ?? null" rows="3" />
                        @break
                    @case('emails')
                        <x-admin.textarea :name="$name" :label="$field['label']" :value="implode(PHP_EOL, (array) $value)" :hint="$field['hint'] ?? null" rows="3" />
                        @break
                    @case('toggle')
                        <x-admin.toggle :name="$name" :label="$field['label']" :checked="(bool) $value" :hint="$field['hint'] ?? null" />
                        @break
                    @case('number')
                        <x-admin.input :name="$name" :label="$field['label']" type="number" :value="$value" :hint="$field['hint'] ?? null" />
                        @break
                    @case('email')
                        <x-admin.input :name="$name" :label="$field['label']" type="email" :value="$value" :hint="$field['hint'] ?? null" />
                        @break
                    @case('media')
                        <x-admin.media-field :name="$name" :label="$field['label']" :media="$value ? $media->get($value) : null" :hint="$field['hint'] ?? null" />
                        @break
                    @case('social')
                        <x-admin.repeater :name="$name" :label="$field['label']" :rows="(array) $value" :max="12" add-label="Add profile"
                            hint="Shown as icons in the footer and on the Contact page. Each network shows its own logo. Upload a custom icon (a transparent PNG works best) to replace it, or for a network not in the list choose “Other”. Custom icons are shown in white like the logos."
                            :fields="['network' => ['label' => 'Network', 'type' => 'select', 'options' => $networks], 'url' => ['label' => 'Profile address (https://…)', 'type' => 'url', 'max' => 500], 'label' => ['label' => 'Label (optional, e.g. “Our TikTok”)', 'max' => 60], 'icon' => ['label' => 'Custom icon (optional)', 'type' => 'media']]" />
                        @break
                    @default
                        <x-admin.input :name="$name" :label="$field['label']" :value="$value" :hint="$field['hint'] ?? null" />
                @endswitch
            @endforeach
        </div>

        @include('admin.partials.save-bar', ['label' => 'Save settings'])
    </form>
@endsection
