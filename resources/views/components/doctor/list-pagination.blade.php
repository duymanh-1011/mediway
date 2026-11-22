@props(['paginator', 'label' => 'bản ghi'])

<div class="doctor-list-footer">
    <span class="doctor-list-footer__summary">
        Hiển thị {{ $paginator->count() }} / {{ $paginator->total() }} {{ $label }}
    </span>

    <nav class="doctor-list-pages" aria-label="Phân trang danh sách">
        @if ($paginator->onFirstPage())
            <span class="portal-btn portal-btn--secondary doctor-list-page" aria-disabled="true" tabindex="-1">
                Trước
            </span>
        @else
            <a class="portal-btn portal-btn--secondary doctor-list-page" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                Trước
            </a>
        @endif

        <span class="doctor-list-pages__position">
            Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="portal-btn portal-btn--secondary doctor-list-page" href="{{ $paginator->nextPageUrl() }}" rel="next">
                Sau
            </a>
        @else
            <span class="portal-btn portal-btn--secondary doctor-list-page" aria-disabled="true" tabindex="-1">
                Sau
            </span>
        @endif
    </nav>
</div>
