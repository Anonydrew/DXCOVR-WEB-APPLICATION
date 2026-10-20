@props(['action', 'confirm' => 'Delete this permanently? This cannot be undone.', 'label' => 'Delete', 'icon' => true, 'size' => 'sm'])
<form method="POST" action="{{ $action }}" class="inline" data-confirm="{{ $confirm }}">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'a-btn a-btn-danger '.($size === 'sm' ? 'a-btn-sm' : '')]) }}>
        @if ($icon)<x-icon name="trash" :size="14" />@endif {{ $label }}
    </button>
</form>
