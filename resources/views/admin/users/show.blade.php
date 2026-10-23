@extends('admin.layouts.app')
@section('admin-page', 'users-show')

@section('title', 'Chi tiết tài khoản - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <span>Tài khoản</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chi tiết</strong>
@endsection

@section('content')
@php
    /*
     * Chỉ lấy hồ sơ qua quan hệ khi Model tương ứng tồn tại.
     */
    $linkedProfile = null;
    $linkedType = null;
    $linkedCode = $user->business_code;

    if ($user->administrator) {
        $linkedProfile = $user->administrator;
        $linkedType = 'Quản trị viên';
    } elseif (
        class_exists(\App\Models\Patient::class)
        && $user->patient
    ) {
        $linkedProfile = $user->patient;
        $linkedType = 'Bệnh nhân';
    } elseif (
        class_exists(\App\Models\Doctor::class)
        && $user->doctor
    ) {
        $linkedProfile = $user->doctor;
        $linkedType = 'Bác sĩ';
    } elseif (
        class_exists(\App\Models\Employee::class)
        && $user->employee
    ) {
        $linkedProfile = $user->employee;
        $linkedType = 'Nhân viên';
    }
@endphp

<div class="account-detail-page">
    <div class="account-detail-heading">
        <div>
            <h1 class="page-title">Chi tiết tài khoản</h1>
            <p class="page-description">
                Mã tài khoản: @include('admin.layouts.partials.record-code', ['code' => $user->business_code])
            </p>
        </div>

        <div class="account-detail-buttons">
            <a
                href="{{ route('admin.users.index') }}"
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>

            <a
                href="{{ route('admin.users.edit', $user) }}"
                class="admin-btn admin-btn-secondary"
            >Sửa</a>
        </div>
    </div>

    @if(session('success'))
        <div class="account-alert account-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="account-alert account-alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="account-alert account-alert-error">
            <strong>Không thể thực hiện thao tác:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ \App\Models\AdminDisplay::text($error) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="account-detail-grid">

        {{-- THÔNG TIN TÀI KHOẢN --}}

        <section class="account-detail-card">
            <h2>Thông tin tài khoản</h2>

            <dl class="account-details">
                <div>
                    <dt>Mã tài khoản</dt>
                    <dd>
                        <strong>@include('admin.layouts.partials.record-code', ['code' => $user->business_code])</strong>
                    </dd>
                </div>
<div>
                    <dt>Số điện thoại</dt>
                    <dd>{{ $user->phone }}</dd>
                </div>

                <div>
                    <dt>Vai trò</dt>
                    <dd>{{ \App\Models\AdminDisplay::label($user->role?->name ?? 'Chưa xác định') }}</dd>
                </div>

                <div>
                    <dt>Trạng thái</dt>
                    <dd>
                        @if($user->status === 'ACTIVE')
                            @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])
                        @elseif($user->status === 'LOCKED')
                            @include('admin.layouts.partials.status-badge', ['badgeCode' => 'LOCKED'])
                        @else
                            @include('admin.layouts.partials.status-badge', ['badgeCode' => $user->status])
                        @endif
                    </dd>
                </div>

                <div>
                    <dt>Ngày tạo</dt>
                    <dd>
                        {{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}
                    </dd>
                </div>

                <div>
                    <dt>Cập nhật gần nhất</dt>
                    <dd>
                        {{ $user->updated_at?->format('d/m/Y H:i') ?? '-' }}
                    </dd>
                </div>
            </dl>
        </section>

        {{-- HỒ SƠ LIÊN KẾT --}}

        <section class="account-detail-card">
            <h2>Hồ sơ liên kết</h2>

            @if($linkedProfile)
                <div class="account-profile-icon">✓</div>

                <div class="account-profile-type">
                    {{ $linkedType }}
                </div>

                <div class="account-profile-name">
                    {{ $linkedProfile->full_name ?? 'Đã liên kết hồ sơ' }}
                </div>

                <div class="account-profile-code">
                    Mã nghiệp vụ: @include('admin.layouts.partials.record-code', ['code' => $linkedCode])
                </div>

                <p class="account-profile-hint">
                    Hồ sơ này thuộc tài khoản đang xem.
                    Các thông tin chuyên môn và thông tin cá nhân chi tiết
                    được quản lý tại mục quản lý tương ứng.
                </p>

                <a class="admin-btn admin-btn-detail" href="{{ route(match ($linkedType) {
                    'Quản trị viên' => 'admin.administrators.show',
                    'Bác sĩ' => 'admin.doctors.show',
                    'Nhân viên' => 'admin.employees.show',
                    default => 'admin.patients.show',
                }, $linkedProfile) }}">Xem hồ sơ</a>
            @else
<div class="account-profile-icon account-profile-empty">
                    !
                </div>

                <div class="account-profile-type">
                    Chưa có hồ sơ liên kết
                </div>

                <p class="account-profile-hint">
                    Tài khoản đã được tạo nhưng chưa liên kết với hồ sơ
                    bệnh nhân, bác sĩ, nhân viên hoặc quản trị viên.
                </p>

                <p class="account-profile-hint">
                    Các hồ sơ nghiệp vụ sẽ được bổ sung và quản lý
                    tại mục quản lý tương ứng.
                </p>
            @endif
        </section>
    </div>

    {{-- QUẢN LÝ TRẠNG THÁI VÀ MẬT KHẨU --}}

    <div class="account-management-grid">

        <section class="account-detail-card">
            <h2>Quản lý trạng thái</h2>

            <p class="account-help">
                Khóa tài khoản sẽ ngăn người dùng đăng nhập.
                Dữ liệu và hồ sơ liên quan vẫn được giữ nguyên.
            </p>

            @if($user->status === 'ACTIVE')

                @if(auth()->id() === $user->id)
                    <div class="account-notice">
                        Bạn đang đăng nhập bằng tài khoản này
                        và không thể tự khóa mình.
                    </div>
                @else
                    <form novalidate
                        action="{{ route('admin.users.update-status', $user) }}"
                        method="POST"
                        onsubmit="return confirm('Xác nhận khóa tài khoản {{ $user->business_code }}? Người dùng sẽ không thể đăng nhập.');"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="status"
                            value="LOCKED"
                        >

                        <button
                            class="admin-btn admin-btn-danger"
                            type="submit"
                        >
                            Khóa tài khoản
                        </button>
                    </form>
                @endif

            @elseif($user->status === 'LOCKED')

                <form novalidate
                    action="{{ route('admin.users.update-status', $user) }}"
                    method="POST"
                    onsubmit="return confirm('Xác nhận mở khóa tài khoản này?');"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="status"
                        value="ACTIVE"
                    >

                    <button
                        class="admin-btn admin-btn-success"
                        type="submit"
                    >
                        Mở khóa tài khoản
</button>
                </form>

            @else
                <div class="account-notice">
                    Trạng thái hiện tại chưa được hỗ trợ
                    chuyển đổi tại màn hình này.
                </div>
            @endif
        </section>

        <section class="account-detail-card">
            <h2>Đặt lại mật khẩu</h2>

            <p class="account-help">
                Mật khẩu mới sẽ thay thế mật khẩu hiện tại.
                Không thể xem lại mật khẩu cũ.
            </p>

            <form novalidate
                action="{{ route('admin.users.reset-password', $user) }}"
                method="POST"
                onsubmit="return confirm('Xác nhận đặt lại mật khẩu cho tài khoản {{ $user->business_code }}?');"
            >
                @csrf
                @method('PATCH')

                <div class="account-form-field">
                    <label for="password">
                        Mật khẩu mới
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >

                    @error('password')
                        <small class="account-field-error">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </small>
                    @enderror
                </div>

                <div class="account-form-field">
                    <label for="password_confirmation">
                        Xác nhận mật khẩu
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >
@error('password_confirmation')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <button
                    type="submit"
                    class="admin-btn admin-btn-primary"
                >
                    Lưu mật khẩu mới
                </button>
            </form>
        </section>
    </div>
</div>
@endsection
