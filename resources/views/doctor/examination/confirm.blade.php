<x-portal.button type="button" variant="secondary" :data-d01-dialog="$dialogId" aria-haspopup="dialog">{{ $label }}</x-portal.button>

<dialog id="{{ $dialogId }}" class="portal-modal d01-dialog" aria-labelledby="{{ $dialogId }}-title">
    <div class="portal-modal__content">
        <div class="d01-dialog__heading">
            <h2 id="{{ $dialogId }}-title">{{ $heading ?? $label }}</h2>
            <form method="dialog">
                <x-portal.button type="submit" variant="ghost" aria-label="Đóng xác nhận">×</x-portal.button>
            </form>
        </div>
        <p><strong>{{ $visit->patient?->full_name }}</strong></p>
        <p>Số khám {{ $queue->queue_number }}</p>
        <div class="portal-modal__actions d01-dialog__actions">
            <form method="dialog">
                <x-portal.button type="submit" variant="secondary" autofocus>Hủy</x-portal.button>
            </form>
            <form method="post" action="{{ $url }}">
                @csrf
                @foreach ($expected as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="confirm" value="1">
                <x-portal.button type="submit" data-pending-label="Đang xử lý…">{{ $submitLabel ?? 'Xác nhận' }}</x-portal.button>
            </form>
        </div>
    </div>
</dialog>
