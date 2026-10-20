@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-subtle">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>
        <ul class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <li><span class="chip opacity-50" aria-disabled="true"><x-icon name="arrow-left" :size="14" /> Previous</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="chip"><x-icon name="arrow-left" :size="14" /> Previous</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="chip border-transparent" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="hidden sm:block">
                            @if ($page == $paginator->currentPage())
                                <span class="chip" aria-current="true">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="chip" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="chip">Next <x-icon name="arrow-right" :size="14" /></a></li>
            @else
                <li><span class="chip opacity-50" aria-disabled="true">Next <x-icon name="arrow-right" :size="14" /></span></li>
            @endif
        </ul>
    </nav>
@endif
