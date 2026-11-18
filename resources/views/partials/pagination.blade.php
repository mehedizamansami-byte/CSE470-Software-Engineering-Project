@if ($paginator->hasPages())
    <div class="row between small">
        <span class="muted">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
        <div class="row">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost btn-sm" aria-disabled="true">← Previous</span>
            @else
                <a class="btn btn-secondary btn-sm" href="{{ $paginator->previousPageUrl() }}">← Previous</a>
            @endif
            <span class="muted">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a class="btn btn-secondary btn-sm" href="{{ $paginator->nextPageUrl() }}">Next →</a>
            @else
                <span class="btn btn-ghost btn-sm" aria-disabled="true">Next →</span>
            @endif
        </div>
    </div>
@endif
