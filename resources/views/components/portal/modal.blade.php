@props(['title' => 'Xác nhận', 'id'])

<dialog id="{{ $id }}" class="portal-modal" aria-labelledby="{{ $id }}-title">
    <div class="portal-modal__content">
        <h2 id="{{ $id }}-title">{{ $title }}</h2>
        <div>{{ $slot }}</div>
        <form method="dialog" class="portal-modal__actions">
            <x-portal.button variant="secondary">Đóng</x-portal.button>
        </form>
    </div>
</dialog>
