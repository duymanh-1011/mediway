<x-portal.button type="button" variant="secondary" :commandfor="$dialogId" command="show-modal">{{ $label }}</x-portal.button>

<dialog id="{{ $dialogId }}" class="portal-modal" aria-labelledby="{{ $dialogId }}-title">
    <div class="portal-modal__content">
        <h2 id="{{ $dialogId }}-title">{{ $label }}</h2>
        <p>{{ $visit->patient?->full_name }} · {{ $visit->visit_code }}</p>
        <form method="post" action="{{ $url }}" class="portal-modal__actions">
            @csrf
            @foreach ($expected as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="confirm" value="1">
            <x-portal.button type="submit" data-pending-label="Đang xử lý…">Xác nhận</x-portal.button>
        </form>
        <form method="dialog" class="portal-modal__actions">
            <x-portal.button type="submit" variant="secondary">Đóng</x-portal.button>
        </form>
    </div>
</dialog>
