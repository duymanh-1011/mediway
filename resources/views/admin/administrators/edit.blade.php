@extends('admin.layouts.app')
@section('admin-page', 'administrators-edit')

@section('title', 'Sửa quản trị viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <span>Quản trị viên</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')
<div class="ae-page">
    <div class="ae-heading">
        <div>
            <h1 class="page-title">Sửa quản trị viên</h1>
            <p class="page-description">
                {{ $administrator->full_name }} —
                @include('admin.layouts.partials.record-code', ['code' => $administrator->user?->business_code])
            </p>
        </div>

        <a href="{{ route('admin.administrators.show', $administrator) }}"
           class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>

    <div class="ae-card">
        <div class="ae-section-title">
            <h2>Thông tin quản trị viên</h2>
            <p>
                Mã tài khoản và vai trò Administrator được giữ nguyên.
                Trạng thái được quản lý tại trang chi tiết.
            </p>
        </div>

        @if($errors->any())
            <div class="ae-alert">
                Vui lòng kiểm tra dữ liệu trước khi lưu.
            </div>
        @endif

        <form novalidate method="POST"
              action="{{ route('admin.administrators.update', $administrator) }}">
            @csrf
            @method('PUT')

            <div class="ae-grid">
                <div class="ae-field">
                    <label for="business_code">Mã tài khoản</label>
                    <input class="admin-record-code" id="business_code" type="text"
                           value="{{ $administrator->user?->business_code }}"
                           disabled>
                </div>

                <div class="ae-field">
                    <label for="role">Vai trò</label>
                    <input id="role" type="text" value="Administrator" disabled>
                </div>

                <div class="ae-field">
                    <label for="full_name">Họ và tên <span>*</span></label>
                    <input id="full_name" type="text" name="full_name"
                           value="{{ old('full_name', $administrator->full_name) }}"
                           maxlength="120" required>
                    @error('full_name')
                        <small class="ae-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="phone">Số điện thoại <span>*</span></label>
                    <input id="phone" type="text" name="phone"
                           value="{{ old('phone', $administrator->user?->phone) }}"
                           maxlength="10" inputmode="numeric" required>
                    @error('phone')
                        <small class="ae-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email"
value="{{ old('email', $administrator->email) }}"
                           maxlength="150">
                    @error('email')
                        <small class="ae-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="date_of_birth">Ngày sinh</label>
                    <input id="date_of_birth" type="date" name="date_of_birth"
                           value="{{ old('date_of_birth', $administrator->date_of_birth) }}"
                           max="{{ now()->subDay()->toDateString() }}">
                    @error('date_of_birth')
                        <small class="ae-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="MALE"
                            @selected(old('gender', $administrator->gender) === 'MALE')>
                            Nam
                        </option>
                        <option value="FEMALE"
                            @selected(old('gender', $administrator->gender) === 'FEMALE')>
                            Nữ
                        </option>
                        <option value="OTHER"
                            @selected(old('gender', $administrator->gender) === 'OTHER')>
                            Khác
                        </option>
                    </select>
                    @error('gender')
                        <small class="ae-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
                    @enderror
                </div>

                <div class="ae-field ae-full">
                    <label for="address">Địa chỉ</label>
                    <input id="address" type="text" name="address"
                           value="{{ old('address', $administrator->address) }}"
                           maxlength="255">
                    @error('address')
                        <small class="ae-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
                    @enderror
                </div>
            </div>

            <div class="ae-note">
                Tài khoản và hồ sơ quản trị viên được cập nhật đồng thời.
                Không thay đổi mật khẩu tại màn hình này.
            </div>

            <div class="ae-actions">
                <a href="{{ route('admin.administrators.show', $administrator) }}"
                   class="admin-btn admin-btn-secondary">Hủy</a>

                <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
