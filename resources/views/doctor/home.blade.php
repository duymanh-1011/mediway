@extends('doctor.layout')

@section('title', 'Tổng quan')

@section('content')
    @php
        $doctorName = $doctor->full_name ?? 'Bác sĩ';
        $doctorCode = $doctor->doctor_code ?? '—';
        $specialtyName = $doctor->specialty?->name
            ?? 'Chưa cập nhật';

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
    @endphp

    {{-- KHUNG CHÀO MỪNG --}}
    <section class="employee-welcome">

        <div class="employee-welcome__copy">

            <p class="portal-eyebrow">
                KHU VỰC BÁC SĨ
            </p>

            <h1>Xin chào, {{ $doctorName }}</h1>

            <p>
                Chúc bạn một ngày làm việc hiệu quả tại Mediway.
            </p>

            <span class="employee-welcome__identity">
                <x-employee-icon name="shield" />

                Bác sĩ

                <span aria-hidden="true">·</span>

                {{ $doctorCode }}
            </span>

        </div>

        <div class="employee-welcome__art" aria-hidden="true">
            <div class="employee-welcome__ring"></div>

            <span>
                <x-employee-icon name="reception" />
            </span>

            <i></i>
        </div>

    </section>

    {{-- TỔNG QUAN CỦA BẠN --}}
    <section class="employee-section" aria-labelledby="doctor-overview-title">

        <div class="employee-section__heading">

            <div>
                <p class="portal-eyebrow">
                    KHÔNG GIAN LÀM VIỆC
                </p>

                <h2 id="doctor-overview-title">
                    Tổng quan của bạn
                </h2>

                <p>
                    Thông tin tài khoản và công việc
                    theo vị trí của bạn.
                </p>
            </div>

        </div>

        <div class="employee-grid">

            {{-- THẺ THÔNG TIN BÁC SĨ --}}
            <section class="employee-card employee-overview-card">

                <div class="employee-card__heading">

                    <span class="employee-card__icon">
                        <x-employee-icon name="user" />
                    </span>

                    <div>
                        <h3>Thông tin bác sĩ</h3>
                        <p>Thông tin hồ sơ của bạn tại Mediway</p>
                    </div>

                </div>

                <dl class="employee-facts">

                    <div>
                        <dt>Mã bác sĩ</dt>
                        <dd>{{ $doctorCode }}</dd>
                    </div>

                    <div>
                        <dt>Vị trí công việc</dt>
                        <dd>Bác sĩ</dd>
                    </div>

                    <div>
                        <dt>Trạng thái phiên</dt>

                        <dd>
                            <span class="employee-status">
                                <span aria-hidden="true"></span>
                                Đang đăng nhập
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt>Chuyên khoa</dt>
                        <dd>{{ $specialtyName }}</dd>
                    </div>

                </dl>

                <a class="employee-card__link" href="{{ route('doctor.profile') }}">
                    Xem hồ sơ cá nhân
                    <x-employee-icon name="arrow" />
                </a>

            </section>

            {{-- THẺ CÔNG VIỆC --}}
            <section class="employee-card employee-overview-card">

                <div class="employee-card__heading">

                    <span class="employee-card__icon">
                        <x-employee-icon name="calendar" />
                    </span>

                    <div>
                        <h3>Công việc của bạn</h3>
                        <p>Chức năng theo vị trí hiện tại</p>
                    </div>

                </div>

                <div class="employee-work">

                    <span class="employee-work__icon">
                        <x-employee-icon name="reception" />
                    </span>

                    <div>

                        <h4>Phòng khám và hàng đợi</h4>

                        <p>
                            Theo dõi phòng khám, gọi bệnh nhân và
                            điều phối lượt khám.
                        </p>

                        @if ($canExamine)

                            <a class="employee-card__link" href="{{ route('doctor.examination.rooms.index') }}">
                                Mở chức năng
                                <x-employee-icon name="arrow" />
                            </a>

                        @else

                            <span class="employee-work__status">
                                Chưa được cấp quyền khám bệnh
                            </span>

                        @endif

                    </div>

                </div>

                <div class="employee-work__summary">

                    <span>Quyền truy cập khám bệnh</span>

                    <strong>
                        {{ $canExamine ? 'Đã cấp' : 'Chưa cấp' }}
                    </strong>

                </div>

            </section>

        </div>

    </section>

    {{-- CÁC NHÓM CÔNG VIỆC --}}
    <section class="employee-section" aria-labelledby="doctor-functions-title">

        <div class="employee-section__heading">

            <div>

                <p class="portal-eyebrow">
                    CHỨC NĂNG
                </p>

                <h2 id="doctor-functions-title">
                    Các nhóm công việc
                </h2>

                <p>
                    Truy cập các chức năng khám bệnh
                    trong hệ thống.
                </p>

            </div>

        </div>

        <div class="employee-services__grid">

            {{-- NHÓM 1: PHÒNG KHÁM VÀ HÀNG ĐỢI --}}
            @if ($canExamine)

                <a class="employee-service" href="{{ route('doctor.examination.rooms.index') }}">

                    <span class="employee-service__icon">
                        <x-employee-icon name="reception" />
                    </span>

                    <h3>Phòng khám và hàng đợi</h3>

                    <p>
                        Xem phòng khám, hàng đợi, gọi, gọi lại
                        và bỏ qua bệnh nhân theo quyền.
                    </p>

                    <span class="employee-service__status">
                        Mở chức năng
                        <x-employee-icon name="arrow" />
                    </span>

                </a>

            @else

                <div class="employee-service">

                    <span class="employee-service__icon">
                        <x-employee-icon name="reception" />
                    </span>

                    <h3>Phòng khám và hàng đợi</h3>

                    <p>
                        Xem phòng khám và điều phối bệnh nhân.
                    </p>

                    <span class="employee-service__status">
                        Chưa được cấp quyền
                    </span>

                </div>

            @endif

            {{-- NHÓM 2: BẮT ĐẦU KHÁM --}}
            <div class="employee-service">

                <span class="employee-service__icon">
                    <x-employee-icon name="calendar" />
                </span>

                <h3>Bắt đầu khám</h3>

                <p>
                    Bắt đầu lượt khám hợp lệ sau khi đã gọi
                    bệnh nhân tại hàng đợi của phòng.
                </p>

                <span class="employee-service__status">
                    Thao tác tại hàng đợi
                </span>

            </div>

            {{-- NHÓM 3: HOÀN TẤT LƯỢT KHÁM --}}
            <div class="employee-service">

                <span class="employee-service__icon">
                    <x-employee-icon name="shield" />
                </span>

                <h3>Hoàn tất lượt khám</h3>

                <p>
                    Hoàn tất sau khi hồ sơ chuyên môn đã được
                    xác nhận đủ điều kiện.
                </p>

                <span class="employee-service__status">
                    Thao tác tại lượt khám hợp lệ
                </span>

            </div>

        </div>

    </section>

@endsection
