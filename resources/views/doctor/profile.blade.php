@extends('doctor.layout')

@section('title', 'Hồ sơ cá nhân')

@section('content')
    @php
        $detail = $doctor->detail;

        $doctorName = $doctor->full_name ?? 'Bác sĩ';
        $doctorCode = $doctor->doctor_code ?? 'Chưa cập nhật';

        $doctorInitial = mb_strtoupper(
            mb_substr(trim($doctorName), 0, 1)
        );

        $specialtyName = $doctor->specialty?->name
            ?? 'Chưa cập nhật';

        $email = $detail?->email ?: 'Chưa cập nhật';

        $currentAddress = $detail?->current_address
            ?: 'Chưa cập nhật';

        $permanentAddress = $detail?->permanent_address
            ?: 'Chưa cập nhật';

        $qualification = $detail?->professional_qualification
            ?: 'Chưa cập nhật';

        $licenseNumber = $detail?->practice_license_number
            ?: 'Chưa cập nhật';

        $workplace = $detail?->current_workplace
            ?: 'Chưa cập nhật';

        // Tương thích với tên route mật khẩu hiện có.
        $passwordRoute = \Illuminate\Support\Facades\Route::has(
            'doctor.password.update'
        )
            ? 'doctor.password.update'
            : (
                \Illuminate\Support\Facades\Route::has(
                    'doctor.password.change'
                )
                ? 'doctor.password.change'
                : null
            );
    @endphp

    <div class="employee-profile">

        {{-- ================================
        TIÊU ĐỀ HỒ SƠ
        ================================= --}}

        <header class="employee-profile__heading">

            <div>

                <a class="employee-profile__back" href="{{ route('doctor.home') }}">
                    ← Quay lại tổng quan
                </a>

                <p class="portal-eyebrow">
                    TÀI KHOẢN BÁC SĨ
                </p>

                <h1>Hồ sơ cá nhân</h1>

                <p>
                    Quản lý thông tin cá nhân và tài khoản của bạn.
                </p>

            </div>

            @if (\Illuminate\Support\Facades\Route::has('doctor.profile.edit'))

                <a class="portal-btn portal-btn--primary employee-profile__edit" href="{{ route('doctor.profile.edit') }}">
                    <x-employee-icon name="edit" />
                    Chỉnh sửa thông tin
                </a>

            @endif

        </header>


        {{-- ================================
        THẺ NHẬN DIỆN BÁC SĨ
        GIỐNG EMPLOYEE PROFILE
        ================================= --}}

        <section class="employee-profile__identity" aria-label="Thông tin tài khoản bác sĩ">

            <span class="employee-profile__avatar" aria-hidden="true">
                {{ $doctorInitial ?: 'B' }}
            </span>

            <div class="employee-profile__identity-text">

                <p>HỒ SƠ BÁC SĨ</p>

                <h2>{{ $doctorName }}</h2>

                <span>
                    {{ $specialtyName }}

                    <span aria-hidden="true">·</span>

                    {{ $doctorCode }}
                </span>

            </div>

            <span class="employee-profile__active">
                <span aria-hidden="true"></span>
                Đang đăng nhập
            </span>

        </section>


        {{-- ================================
        CÁC THẺ THÔNG TIN
        ================================= --}}

        <div class="employee-profile__grid">

            {{-- THÔNG TIN CÁ NHÂN --}}

            <section class="employee-card employee-profile__card" aria-labelledby="doctor-personal-title">

                <div class="employee-card__heading">

                    <span class="employee-card__icon">
                        <x-employee-icon name="user" />
                    </span>

                    <div>

                        <h2 id="doctor-personal-title">
                            Thông tin cá nhân
                        </h2>

                        <p>Thông tin cơ bản của bác sĩ</p>

                    </div>

                </div>

                <dl class="employee-profile__facts">

                    <div>
                        <dt>Họ và tên</dt>
                        <dd>{{ $doctorName }}</dd>
                    </div>

                    <div>
                        <dt>Mã bác sĩ</dt>
                        <dd>{{ $doctorCode }}</dd>
                    </div>

                    <div>
                        <dt>Ngày sinh</dt>

                        <dd>
                            {{
        $doctor->date_of_birth
                ?->format('d/m/Y')
        ?? 'Chưa cập nhật'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Giới tính</dt>

                        <dd>
                            {{ $doctor->gender ?: 'Chưa cập nhật' }}
                        </dd>
                    </div>

                    <div>
                        <dt>Số điện thoại đăng nhập</dt>

                        <dd>
                            {{ auth()->user()?->phone ?: 'Chưa cập nhật' }}
                        </dd>
                    </div>

                </dl>

            </section>


            {{-- THÔNG TIN LIÊN HỆ --}}

            <section class="employee-card employee-profile__card" aria-labelledby="doctor-contact-title">

                <div class="employee-card__heading">

                    <span class="employee-card__icon">
                        <x-employee-icon name="contact" />
                    </span>

                    <div>

                        <h2 id="doctor-contact-title">
                            Thông tin liên hệ
                        </h2>

                        <p>Thông tin bạn có thể cập nhật</p>

                    </div>

                </div>

                <dl class="employee-profile__facts employee-profile__facts--single">

                    <div>
                        <dt>Email</dt>
                        <dd>{{ $email }}</dd>
                    </div>

                    <div>
                        <dt>Địa chỉ hiện tại</dt>
                        <dd>{{ $currentAddress }}</dd>
                    </div>

                    <div>
                        <dt>Địa chỉ thường trú</dt>
                        <dd>{{ $permanentAddress }}</dd>
                    </div>

                </dl>

            </section>


            {{-- THÔNG TIN CHUYÊN MÔN
            CHIẾM CẢ HAI CỘT --}}

            <section class="employee-card employee-profile__card employee-profile__work"
                aria-labelledby="doctor-work-title">

                <div class="employee-card__heading">

                    <span class="employee-card__icon">
                        <x-employee-icon name="briefcase" />
                    </span>

                    <div>

                        <h2 id="doctor-work-title">
                            Thông tin chuyên môn
                        </h2>

                        <p>
                            Chuyên khoa, trình độ và giấy phép
                            hành nghề bác sĩ
                        </p>

                    </div>

                </div>

                <dl class="employee-profile__facts employee-profile__facts--work">

                    <div>
                        <dt>Chức danh</dt>
                        <dd>Bác sĩ</dd>
                    </div>

                    <div>
                        <dt>Chuyên khoa</dt>
                        <dd>{{ $specialtyName }}</dd>
                    </div>

                    <div>
                        <dt>Trình độ chuyên môn</dt>
                        <dd>{{ $qualification }}</dd>
                    </div>

                    <div>
                        <dt>Số giấy phép hành nghề</dt>
                        <dd>{{ $licenseNumber }}</dd>
                    </div>

                    <div>
                        <dt>Nơi công tác</dt>
                        <dd>{{ $workplace }}</dd>
                    </div>

                </dl>

            </section>

        </div>


        {{-- ================================
        BẢO MẬT TÀI KHOẢN
        GIỐNG EMPLOYEE PROFILE
        ================================= --}}

        <section class="employee-card employee-profile__security" aria-labelledby="doctor-security-title">

            <div class="employee-card__heading employee-profile__security-intro">

                <span class="employee-card__icon">
                    <x-employee-icon name="shield" />
                </span>

                <div>

                    <h2 id="doctor-security-title">
                        Bảo mật tài khoản
                    </h2>

                    <p>
                        Thay đổi mật khẩu để bảo vệ tài khoản của bạn.
                    </p>

                </div>

            </div>

            @if ($passwordRoute)

                <form class="portal-form employee-profile__password" method="post" action="{{ route($passwordRoute) }}">
                    @csrf
                    @method('PUT')

                    <div class="employee-profile__password-fields">

                        {{-- MẬT KHẨU HIỆN TẠI --}}

                        <div class="employee-profile__field">

                            <label for="current_password">
                                Mật khẩu hiện tại
                            </label>

                            <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                                required @error('current_password') aria-invalid="true"
                                aria-describedby="current-password-error" @enderror>

                            @error('current_password')

                                <p class="portal-field-error" id="current-password-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- MẬT KHẨU MỚI --}}

                        <div class="employee-profile__field">

                            <label for="password">
                                Mật khẩu mới
                            </label>

                            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8"
                                maxlength="72" required @error('password') aria-invalid="true" aria-describedby="password-error"
                                @enderror>

                            @error('password')

                                <p class="portal-field-error" id="password-error">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- XÁC NHẬN MẬT KHẨU --}}

                        <div class="employee-profile__field">

                            <label for="password_confirmation">
                                Xác nhận mật khẩu mới
                            </label>

                            <input id="password_confirmation" name="password_confirmation" type="password"
                                autocomplete="new-password" required>

                        </div>

                    </div>

                    <div class="employee-profile__password-actions">

                        <p>
                            Mật khẩu mới cần ít nhất 8 ký tự và
                            phải khác mật khẩu hiện tại.
                        </p>

                        <button class="portal-btn portal-btn--primary" type="submit">
                            Đổi mật khẩu
                        </button>

                    </div>

                </form>

            @else

                <p>
                    Chức năng đổi mật khẩu chưa được cấu hình
                    cho tài khoản bác sĩ.
                </p>

            @endif

        </section>

    </div>
@endsection