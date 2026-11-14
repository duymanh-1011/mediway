@props(['action', 'workspace', 'label'])
@php($dialogId = 'workspace-switch-'.$workspace)
<div class="employee-workspace-switch">
    <button type="button" class="portal-btn portal-btn--secondary" aria-label="Chuyển sang {{ $label }}"
        aria-haspopup="dialog" aria-controls="{{ $dialogId }}" data-workspace-dialog="{{ $dialogId }}">
        <x-employee-icon name="switch" />
        <span>{{ $label }}</span>
    </button>

    <dialog id="{{ $dialogId }}" class="portal-modal workspace-switch-dialog" aria-labelledby="{{ $dialogId }}-title" aria-describedby="{{ $dialogId }}-description">
        <div class="portal-modal__content">
            <h2 id="{{ $dialogId }}-title">Xác nhận đổi chức năng</h2>
            <p id="{{ $dialogId }}-description">Bạn muốn chuyển sang <strong>{{ $label }}</strong>?</p>
            <div class="portal-modal__actions">
                <form method="dialog">
                    <x-portal.button type="submit" variant="secondary" autofocus>Ở lại</x-portal.button>
                </form>
                <form method="post" action="{{ $action }}">
                    @csrf
                    <input type="hidden" name="workspace" value="{{ $workspace }}">
                    <x-portal.button type="submit">Đổi chức năng</x-portal.button>
                </form>
            </div>
        </div>
    </dialog>
</div>
