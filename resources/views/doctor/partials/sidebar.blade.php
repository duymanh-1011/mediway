@php
    $isServices = session('doctor_workspace_mode') === 'services';
    $navigation = $isServices
        ? [['Nhóm dịch vụ', 'services', 'doctor.services.index', ['doctor.services.*', 'doctor.laboratory.*', 'doctor.imaging.*']]]
        : [
            ['Phòng khám và hàng đợi', 'reception', 'doctor.examination.rooms.index', ['doctor.examination.rooms.*', 'doctor.examination.visits.*', 'doctor.visits.*']],
        ];
    $navigation[] = ['Lịch làm việc', 'calendar', 'doctor.schedule', ['doctor.schedule', 'doctor.schedule.legacy']];
@endphp

<aside class="employee-sidebar">
    <details class="employee-menu" open>
        <summary>
            <span class="employee-menu__toggle-icon" aria-hidden="true">☰</span>
            <span>Menu làm việc</span>
            <span class="employee-menu__chevron" aria-hidden="true">⌄</span>
        </summary>
        <nav aria-label="Điều hướng bác sĩ">
            <p class="employee-nav__label">KHÔNG GIAN LÀM VIỆC</p>
            <a href="{{ route('doctor.home') }}" class="employee-nav__item {{ request()->routeIs('doctor.home') ? 'is-active' : '' }}"
                @if (request()->routeIs('doctor.home')) aria-current="page" @endif>
                <x-employee-icon name="home" /><span>Tổng quan</span>
            </a>
            <p class="employee-nav__label">CHỨC NĂNG</p>
            @foreach ($navigation as [$label, $icon, $target, $patterns])
                @php($active = request()->routeIs(...$patterns))
                <a href="{{ route($target) }}" class="employee-nav__item {{ $active ? 'is-active' : '' }}"
                    @if ($active) aria-current="{{ request()->routeIs($target) ? 'page' : 'location' }}" @endif>
                    <x-employee-icon :name="$icon" /><span>{{ $label }}</span>
                </a>
            @endforeach
            <p class="employee-nav__label">TÀI KHOẢN</p>
            <a href="{{ route('doctor.profile') }}" class="employee-nav__item {{ request()->routeIs('doctor.profile*') ? 'is-active' : '' }}"
                @if (request()->routeIs('doctor.profile*')) aria-current="page" @endif>
                <x-employee-icon name="user" /><span>Hồ sơ cá nhân</span>
            </a>
        </nav>
    </details>
    <x-workspace-guidance
        :context="$isServices ? 'Bác sĩ thực hiện dịch vụ' : 'Bác sĩ khám bệnh'"
        :stages="$isServices ? [
            'Trước khi lưu kết quả' => [
                'Đối chiếu tên bệnh nhân và dịch vụ trên phiếu chỉ định.',
                'Kiểm tra nội dung kết quả và tệp đính kèm.',
            ],
            'Sau khi hoàn tất dịch vụ' => [
                'Kiểm tra phiếu đã chuyển sang Đã hoàn tất.',
                'Nếu đã thực hiện hết chỉ định, hướng dẫn bệnh nhân chờ gọi lại tại phòng khám.',
            ],
        ] : [
            'Trước khi bắt đầu khám' => [
                'Đối chiếu tên bệnh nhân và mã lượt khám trước khi ghi hồ sơ.',
            ],
            'Sau khi hoàn tất lượt khám' => [
                'Hướng dẫn bệnh nhân xem kết quả và đơn thuốc nếu có.',
                'Nhắc lịch tái khám nếu đã hẹn.',
            ],
        ]"
    />
</aside>
