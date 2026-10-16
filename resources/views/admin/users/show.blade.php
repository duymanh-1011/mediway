@extends('admin.layouts.app')

@section('title', 'Chi tiết tài khoản - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Tài khoản / Chi tiết
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
                Mã tài khoản: {{ $user->business_code }}
            </p>
        </div>

        <div class="account-detail-buttons">
            <a
                href="{{ route('admin.users.index') }}"
                class="account-detail-btn account-btn-light"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route('admin.users.edit', $user) }}"
                class="account-detail-btn account-btn-primary"
            >
                Sửa thông tin
            </a>
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
                    <li>{{ $error }}</li>
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
                        <strong>{{ $user->business_code }}</strong>
                    </dd>
                </div>
<div>
                    <dt>Số điện thoại</dt>
                    <dd>{{ $user->phone }}</dd>
                </div>

                <div>
                    <dt>Vai trò</dt>
                    <dd>{{ $user->role?->name ?? 'Chưa xác định' }}</dd>
                </div>

                <div>
                    <dt>Trạng thái</dt>
                    <dd>
                        @if($user->status === 'ACTIVE')
                            <span class="account-status account-status-active">
                                Hoạt động
                            </span>
                        @elseif($user->status === 'LOCKED')
                            <span class="account-status account-status-locked">
                                Đã khóa
                            </span>
                        @else
                            <span class="account-status account-status-other">
                                {{ $user->status }}
                            </span>
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
                    Mã nghiệp vụ: {{ $linkedCode }}
                </div>

                <p class="account-profile-hint">
                    Hồ sơ này được liên kết với tài khoản đăng nhập hiện tại.
                    Các thông tin chuyên môn và thông tin cá nhân chi tiết
                    được quản lý tại module tương ứng.
                </p>

                @if(
                    $linkedType === 'Quản trị viên'
                    && $user->administrator
                )
                    <a
                        href="{{ route('admin.administrators.show', $user->administrator) }}"
                        class="account-detail-btn account-btn-light"
                    >
                        Xem hồ sơ quản trị viên
                    </a>
                @endif
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
                    tại module tương ứng.
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
                    <form
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
                            class="account-detail-btn account-btn-danger"
                            type="submit"
                        >
                            Khóa tài khoản
                        </button>
                    </form>
                @endif

            @elseif($user->status === 'LOCKED')

                <form
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
                        class="account-detail-btn account-btn-primary"
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

            <form
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
                            {{ $message }}
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
                </div>

                <button
                    type="submit"
                    class="account-detail-btn account-btn-primary"
                >
                    Lưu mật khẩu mới
                </button>
            </form>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
.account-detail-page {
    width: 100%;
    min-width: 0;
}

.account-detail-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.account-detail-heading .page-description {
    margin: 7px 0 0;
    color: #64748b;
}

.account-detail-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.account-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
    gap: 18px;
    margin-bottom: 20px;
}
.account-management-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.account-detail-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(15,23,42,.035);
    min-width: 0;
}

.account-detail-card h2 {
    font-size: 17px;
    margin: 0 0 19px;
    color: #0f172a;
}

.account-details {
    margin: 0;
}

.account-details > div {
    display: grid;
    grid-template-columns: 145px minmax(0, 1fr);
    gap: 12px;
    padding: 13px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
}

.account-details > div:last-child {
    border: 0;
}

.account-details dt {
    color: #64748b;
}

.account-details dd {
    margin: 0;
    color: #0f172a;
    overflow-wrap: anywhere;
}

.account-status {
    padding: 6px 10px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
    display: inline-block;
}

.account-status-active {
    background: #dcfce7;
    color: #166534;
}

.account-status-locked {
    background: #fee2e2;
    color: #991b1b;
}

.account-status-other {
    background: #f1f5f9;
    color: #475569;
}

.account-profile-icon {
    width: 45px;
    height: 45px;
    border-radius: 14px;
    background: #dcfce7;
    color: #166534;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    font-weight: 800;
    margin-bottom: 15px;
}

.account-profile-empty {
    background: #fef3c7;
    color: #92400e;
}

.account-profile-type {
    font-size: 12px;
    font-weight: 700;
    color: #2563eb;
    margin-bottom: 7px;
}

.account-profile-name {
    font-size: 20px;
    font-weight: 750;
    color: #0f172a;
    margin-bottom: 6px;
}

.account-profile-code {
    color: #64748b;
    font-size: 13px;
}

.account-profile-hint,
.account-help {
    font-size: 13px;
    color: #64748b;
    line-height: 1.7;
    margin: 12px 0 20px;
}

.account-detail-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    border-radius: 9px;
    min-height: 40px;
    padding: 10px 15px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
}

.account-btn-light {
    background: #fff;
    border-color: #cbd5e1;
    color: #334155;
}

.account-btn-primary {
    background: #2563eb;
    color: #fff;
}

.account-btn-danger {
    background: #dc2626;
    color: #fff;
}

.account-notice {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 9px;
    padding: 13px 15px;
    color: #92400e;
    font-size: 13px;
    line-height: 1.6;
}

.account-form-field {
    margin-bottom: 16px;
}

.account-form-field label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.account-form-field input {
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    border-radius: 9px;
border: 1px solid #cbd5e1;
    outline: 0;
}

.account-form-field input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px #dbeafe;
}

.account-field-error {
    display: block;
    color: #dc2626;
    font-size: 12px;
    margin-top: 6px;
}

.account-alert {
    padding: 13px 16px;
    border-radius: 10px;
    margin-bottom: 17px;
    font-size: 13px;
}

.account-alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.account-alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.account-alert ul {
    margin: 8px 0 0;
    padding-left: 19px;
}

@media (max-width: 850px) {
    .account-detail-grid,
    .account-management-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 550px) {
    .account-detail-card {
        padding: 17px;
    }

    .account-details > div {
        grid-template-columns: 1fr;
        gap: 5px;
    }
}
</style>
@endpush