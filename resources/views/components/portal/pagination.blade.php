@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="portal-pagination" aria-label="Phân trang">
        {{ $paginator->links() }}
    </nav>
@endif
