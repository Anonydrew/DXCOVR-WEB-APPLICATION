@props(['action', 'search' => true, 'placeholder' => 'Search…'])
<form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4" role="search">
    @if ($search)
        <div class="min-w-48 flex-1">
            <label for="filter-q" class="sr-only">Search</label>
            <input id="filter-q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="a-input" maxlength="100">
        </div>
    @endif
    {{ $slot }}
    <button type="submit" class="a-btn"><x-icon name="filter" :size="14" /> Filter</button>
    @if (collect(request()->except('page'))->filter()->isNotEmpty())
        <a href="{{ $action }}" class="a-btn a-btn-ghost">Reset</a>
    @endif
</form>
