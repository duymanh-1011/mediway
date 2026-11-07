<div class="admin-list-footer">
    <span>Hiển thị {{ $rows->count() }} / {{ $rows->total() }} {{ $recordLabel ?? 'bản ghi' }}</span>
    <nav class="admin-list-pages" aria-label="Phân trang danh sách">
        @if ($rows->onFirstPage())
            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
        @else
            <a class="admin-btn admin-btn-secondary" href="{{ $rows->previousPageUrl() }}" rel="prev">Trước</a>
        @endif
        <span>Trang {{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>
        @if ($rows->hasMorePages())
            <a class="admin-btn admin-btn-secondary" href="{{ $rows->nextPageUrl() }}" rel="next">Sau</a>
        @else
            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
        @endif
    </nav>
</div>
