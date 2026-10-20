@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm">
        <p class="a-subtle">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <div class="flex gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="a-btn a-btn-sm" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="a-btn a-btn-sm">Previous</a>
            @endif
            <span class="a-btn a-btn-sm a-btn-ghost" aria-current="page">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="a-btn a-btn-sm">Next</a>
            @else
                <span class="a-btn a-btn-sm" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
