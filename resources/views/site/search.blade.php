@extends('layouts.site')

@section('content')
    <section class="surface-dark relative overflow-hidden">
        <div class="grid-lines pointer-events-none absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]" aria-hidden="true"></div>
        <div class="container-dx relative py-16 sm:py-24">
            <p class="eyebrow accent">Search</p>
            <h1 class="display-md mt-5">{{ $term !== '' ? 'Results for “'.$term.'”' : 'Search DXCOVR' }}</h1>
            <form method="GET" action="{{ route('search') }}" role="search" class="mt-10 flex max-w-3xl flex-col gap-3 sm:flex-row">
                <label for="search-q" class="sr-only">Search the site</label>
                <input id="search-q" type="search" name="q" value="{{ $term }}" class="field flex-1 text-lg" placeholder="Search services, work, products, insights and careers" minlength="2" maxlength="100" required @if ($term === '') autofocus @endif>
                <button type="submit" class="btn btn-primary"><x-icon name="search" :size="18" /> Search</button>
            </form>
        </div>
    </section>

    <section class="surface-dark pb-24 sm:pb-32" aria-live="polite">
        <div class="container-dx">
            @if ($term === '')
                <p class="text-muted">Type at least two characters to search.</p>
            @elseif (mb_strlen($term) < 2)
                <p class="text-muted">Please enter at least two characters.</p>
            @elseif ($total === 0)
                <x-site.empty-state :title="'No results for “'.$term.'”'" glyph="ai">
                    Check the spelling, try a broader term, or explore the main sections of the site.
                    <x-slot:actions>
                        <a href="{{ route('services.index') }}" class="btn btn-secondary">Services</a>
                        <a href="{{ route('work.index') }}" class="btn btn-secondary">Work</a>
                        <a href="{{ route('insights.index') }}" class="btn btn-secondary">Insights</a>
                    </x-slot:actions>
                </x-site.empty-state>
            @else
                <p class="text-sm text-subtle" role="status">{{ trans_choice(':count result|:count results', $total) }}</p>
                <div class="mt-8 space-y-14">
                    @foreach ($groups as $group => $items)
                        <section aria-labelledby="results-{{ \Illuminate\Support\Str::slug($group) }}">
                            <h2 id="results-{{ \Illuminate\Support\Str::slug($group) }}" class="mono-label text-subtle">{{ $group }} <span class="text-muted">({{ $items->count() }})</span></h2>
                            <ul class="mt-4 divide-y divide-line border-y border-line">
                                @foreach ($items as $item)
                                    <li class="index-row group">
                                        <div class="py-6">
                                            <p class="mono-label accent">{{ $item['meta'] }}</p>
                                            <h3 class="index-title heading-md mt-2"><a href="{{ $item['url'] }}" class="stretched-link">{{ $item['title'] }}</a></h3>
                                            @if ($item['excerpt'])
                                                <p class="mt-2 max-w-3xl text-muted line-clamp-2">{{ $item['excerpt'] }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
