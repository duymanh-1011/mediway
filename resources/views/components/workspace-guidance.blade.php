@props(['context', 'stages'])

<section class="employee-sidebar__guidance" aria-label="Lưu ý trong ca">
    <div class="workspace-guidance__header">
        <span class="workspace-guidance__icon"><x-employee-icon name="shield" /></span>
        <div>
            <h2>Lưu ý trong ca</h2>
            <p>{{ $context }}</p>
        </div>
    </div>
    <div class="workspace-guidance__stages">
        @foreach ($stages as $title => $reminders)
            <details class="workspace-guidance__details">
                <summary>{{ $title }}</summary>
                <ul>
                    @foreach ($reminders as $reminder)
                        <li>{{ $reminder }}</li>
                    @endforeach
                </ul>
            </details>
        @endforeach
    </div>
    <div class="workspace-guidance__privacy">
        <x-employee-icon name="lock" />
        <p>Khóa màn hình khi rời máy và đăng xuất khi kết thúc ca.</p>
    </div>
</section>
