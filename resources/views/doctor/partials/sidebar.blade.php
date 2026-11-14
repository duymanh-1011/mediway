
@php
    $currentUser = request()->user();

    $currentDoctor = request()
        ->attributes
        ->get('current_doctor');

    $canExamine =
        \Illuminate\Support\Facades\Route::has(
            'doctor.examination.rooms.index'
        )
        && $currentUser instanceof \App\Models\User
        && $currentDoctor instanceof \App\Models\Doctor
        && app(\App\Support\Doctor\DoctorAccess::class)
            ->canExamine($currentUser, $currentDoctor);

    $examinationActive = request()->routeIs(
        'doctor.examination.*'
    );

    $profileActive = request()->routeIs(
        'doctor.profile',
        'doctor.profile.*'
    );
@endphp

<aside class="employee-sidebar">

    <details class="employee-menu" open>

        <summary>
            <span
                class="employee-menu__toggle-icon"
                aria-hidden="true"
            >
                ☰
            </span>

            <span>Menu làm việc</span>

            <span
                class="employee-menu__chevron"
                aria-hidden="true"
            >
                ⌄
            </span>
        </summary>

        <nav aria-label="Điều hướng bác sĩ">

            {{-- KHÔNG GIAN LÀM VIỆC --}}
            <p class="employee-nav__label">
                KHÔNG GIAN LÀM VIỆC
            </p>

            <a
                href="{{ route('doctor.home') }}"
                class="employee-nav__item {{ request()->routeIs('doctor.home') ? 'is-active' : '' }}"
                @if (request()->routeIs('doctor.home'))
                    aria-current="page"
                @endif
            >
                <x-employee-icon name="home" />

                <span>Tổng quan</span>
            </a>

            {{-- CHỨC NĂNG --}}
            <p class="employee-nav__label">
                CHỨC NĂNG
            </p>

            <a href="{{ route('doctor.visits.index') }}" class="employee-nav__item {{ request()->routeIs('doctor.visits.*') ? 'is-active' : '' }}" @if(request()->routeIs('doctor.visits.*')) aria-current="page" @endif>
                <x-employee-icon name="reception" /><span>Danh sách lượt khám</span>
            </a>

            @if ($canExamine)

                <a
                    href="{{ route('doctor.examination.rooms.index') }}"
                    class="employee-nav__item {{ $examinationActive ? 'is-active' : '' }}"
                    @if ($examinationActive)
                        aria-current="location"
                    @endif
                >
                    <x-employee-icon name="reception" />

                    <span>
                        Phòng khám và hàng đợi
                    </span>
                </a>

            @else

                <div
                    class="employee-nav__item employee-nav__item--disabled"
                    aria-label="Phòng khám và hàng đợi: Chưa được cấp quyền"
                >
                    <x-employee-icon name="reception" />

                    <span>
                        Phòng khám và hàng đợi

                        <small>
                            Chưa được cấp quyền
                        </small>
                    </span>

                    <x-employee-icon name="lock" />
                </div>

            @endif

            {{-- TÀI KHOẢN --}}
            <p class="employee-nav__label">
                TÀI KHOẢN
            </p>

            <a
                href="{{ route('doctor.profile') }}"
                class="employee-nav__item {{ $profileActive ? 'is-active' : '' }}"
                @if (request()->routeIs('doctor.profile', 'doctor.profile.*'))
                    aria-current="page"
                @endif
            >
                <x-employee-icon name="user" />

                <span>
                    Hồ sơ cá nhân
                </span>
            </a>

        </nav>

    </details>

    {{-- THẺ CẦN HỖ TRỢ --}}
    @if (\Illuminate\Support\Facades\Route::has('public.support'))

        <div class="employee-sidebar__help">

            <x-employee-icon name="help" />

            <div>
                <strong>
                    Cần hỗ trợ?
                </strong>

                <p>
                    Thông tin và hướng dẫn sử dụng Mediway.
                </p>

                <a href="{{ route('public.support') }}">
                    Xem hỗ trợ
                    <span aria-hidden="true">→</span>
                </a>
            </div>

        </div>

    @endif

</aside>
