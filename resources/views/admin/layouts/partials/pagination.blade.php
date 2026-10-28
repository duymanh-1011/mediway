@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="Phân trang">
        @if (method_exists($paginator, 'total'))
            <p class="form-help">Hiển thị {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} trên {{ $paginator->total() }} bản ghi</p>
        @endif
        <div class="admin-actions">
            @if ($paginator->onFirstPage())
                <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
            @else
                <a class="admin-btn admin-btn-secondary" href="{{ $paginator->previousPageUrl() }}" rel="prev">Trước</a>
            @endif
            @foreach ($elements ?? [] as $element)
                @if (is_string($element))
                    <span aria-hidden="true">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="admin-btn admin-btn-primary" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="admin-btn admin-btn-secondary" href="{{ $url }}" aria-label="Trang {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a class="admin-btn admin-btn-secondary" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau</a>
            @else
                <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
            @endif
        </div>
    </nav>
@endif
