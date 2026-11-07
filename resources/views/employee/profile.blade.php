@extends('employee.layout')
@section('title', 'Hồ sơ nhân viên')
@section('content')
<div class="employee-profile">
    <header class="employee-profile__heading">
        <div><a class="employee-profile__back" href="{{ route('employee.home') }}">← Quay lại tổng quan</a><p class="portal-eyebrow">TÀI KHOẢN NHÂN VIÊN</p><h1>Hồ sơ cá nhân</h1><p>Quản lý thông tin cá nhân và tài khoản của bạn.</p></div>
        <a class="portal-btn portal-btn--primary employee-profile__edit" href="{{ route('employee.profile.edit') }}"><x-employee-icon name="edit" /> Chỉnh sửa thông tin</a>
    </header>

    <section class="employee-profile__identity" aria-label="Thông tin tài khoản">
        <span class="employee-profile__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($employee->full_name), 0, 1)) }}</span>
        <div class="employee-profile__identity-text"><p>HỒ SƠ NHÂN VIÊN</p><h2>{{ $employee->full_name }}</h2><span>{{ \App\Support\EmployeeAccess::TYPES[\App\Support\EmployeeAccess::typeCode($employee->detail->employee_type)] }} <span aria-hidden="true">·</span> {{ $employee->employee_code }}</span></div>
        <span class="employee-profile__active"><span aria-hidden="true"></span> Đang hoạt động</span>
    </section>

    <div class="employee-profile__grid">
        <section class="employee-card employee-profile__card">
            <div class="employee-card__heading"><span class="employee-card__icon"><x-employee-icon name="user" /></span><div><h2>Thông tin cá nhân</h2><p>Thông tin cơ bản của nhân viên</p></div></div>
            <dl class="employee-profile__facts">
                <div><dt>Họ và tên</dt><dd>{{ $employee->full_name }}</dd></div>
                <div><dt>Mã nhân viên</dt><dd>{{ $employee->employee_code }}</dd></div>
                <div><dt>Ngày sinh</dt><dd>{{ $employee->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd></div>
                <div><dt>Giới tính</dt><dd>{{ $employee->gender ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Số điện thoại đăng nhập</dt><dd>{{ auth()->user()->phone }}</dd></div>
            </dl>
        </section>
        <section class="employee-card employee-profile__card">
            <div class="employee-card__heading"><span class="employee-card__icon"><x-employee-icon name="contact" /></span><div><h2>Thông tin liên hệ</h2><p>Thông tin bạn có thể cập nhật</p></div></div>
            <dl class="employee-profile__facts employee-profile__facts--single">
                <div><dt>Email</dt><dd>{{ $employee->detail->email ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Địa chỉ hiện tại</dt><dd>{{ $employee->detail->current_address ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Địa chỉ thường trú</dt><dd>{{ $employee->detail->permanent_address ?: 'Chưa cập nhật' }}</dd></div>
            </dl>
        </section>
        <section class="employee-card employee-profile__card employee-profile__work">
            <div class="employee-card__heading"><span class="employee-card__icon"><x-employee-icon name="briefcase" /></span><div><h2>Thông tin công việc</h2><p>Vị trí và đơn vị công tác của bạn</p></div></div>
            <dl class="employee-profile__facts employee-profile__facts--work">
                <div><dt>Loại nhân viên</dt><dd>{{ \App\Support\EmployeeAccess::TYPES[\App\Support\EmployeeAccess::typeCode($employee->detail->employee_type)] }}</dd></div>
                <div><dt>Phòng ban</dt><dd>{{ $employee->detail->department ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Chức danh</dt><dd>{{ $employee->detail->position ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Ngày tuyển dụng</dt><dd>{{ $employee->detail->hire_date?->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd></div>
                <div><dt>Địa điểm làm việc</dt><dd>{{ $employee->detail->work_location ?: 'Chưa cập nhật' }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="employee-card employee-profile__security" aria-labelledby="employee-security-title">
        <div class="employee-card__heading employee-profile__security-intro"><span class="employee-card__icon"><x-employee-icon name="shield" /></span><div><h2 id="employee-security-title">Bảo mật tài khoản</h2><p>Thay đổi mật khẩu để bảo vệ tài khoản của bạn.</p></div></div>
        <form class="portal-form employee-profile__password" method="post" action="{{ route('employee.password.update') }}">@csrf @method('PUT')
            <div class="employee-profile__password-fields">
            <div class="employee-profile__field"><label for="current_password">Mật khẩu hiện tại</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror>@error('current_password')<p class="portal-field-error" id="current-password-error">{{ $message }}</p>@enderror</div>
            <div class="employee-profile__field"><label for="password">Mật khẩu mới</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>@error('password')<p class="portal-field-error" id="password-error">{{ $message }}</p>@enderror</div>
            <div class="employee-profile__field"><label for="password_confirmation">Xác nhận mật khẩu mới</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
            </div>
            <div class="employee-profile__password-actions"><p>Mật khẩu mới cần ít nhất 8 ký tự và phải khác mật khẩu hiện tại.</p><button class="portal-btn portal-btn--primary" type="submit">Đổi mật khẩu</button></div>
        </form>
    </section>
</div>
@endsection
