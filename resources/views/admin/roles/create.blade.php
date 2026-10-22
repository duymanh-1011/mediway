@extends('admin.layouts.app')
@section('admin-page', 'roles-create')

@section('title', 'Thêm vai trò - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <a href="{{ route('admin.roles.index') }}">Vai trò</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
<div class="role-form-page">

    <div class="role-page-heading">
        <div>
            <h1 class="page-title">Thêm vai trò</h1>
            <p class="page-description">
                Tạo một vai trò mới trong hệ thống Mediway.
            </p>
        </div>

        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
            ← Quay lại danh sách
        </a>
    </div>

    <form novalidate action="{{ route('admin.roles.store') }}" method="POST">
        @csrf

        <div class="card role-form-card">
            <div class="role-card-heading">
                <h2>Thông tin vai trò</h2>
                <p>Những trường có dấu * là bắt buộc.</p>
            </div>

            <div class="role-form-group">
                <label for="name">
                    Tên vai trò <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    maxlength="50"
                    placeholder="Ví dụ: Receptionist"
                    class="{{ $errors->has('name') ? 'input-invalid' : '' }}"
                    required
                >

                @error('name')
                    <div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                @enderror

                <small>
                    Chỉ dùng chữ cái không dấu, số và dấu gạch dưới.
                    Tên phải bắt đầu bằng chữ cái.
                </small>
            </div>

            <div class="role-form-group">
                <label for="description">Mô tả</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="255"
                    placeholder="Nhập mô tả chức năng của vai trò..."
                    class="{{ $errors->has('description') ? 'input-invalid' : '' }}"
                >{{ old('description') }}</textarea>

                @error('description')
                    <div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                @enderror
            </div>

            <div class="role-form-group">
                <label for="status">
                    Trạng thái <span class="required">*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    class="{{ $errors->has('status') ? 'input-invalid' : '' }}"
                    required
                >
<option value="ACTIVE" {{ old('status', 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>
                        Hoạt động
                    </option>

                    <option value="INACTIVE" {{ old('status') === 'INACTIVE' ? 'selected' : '' }}>
                        Ngừng hoạt động
                    </option>
                </select>

                @error('status')
                    <div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                @enderror
            </div>

            <div class="role-info-box">
                <strong>Lưu ý</strong>
                <p>
                    Việc tạo vai trò chỉ bổ sung dữ liệu danh mục.
                    Vai trò mới chưa tự động có quyền truy cập các chức năng
                    của hệ thống cho đến khi được cấu hình trong code phân quyền.
                </p>
            </div>

            <div class="role-form-actions">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                    Hủy bỏ
                </a>

                <button type="submit" class="btn btn-primary">
                    Lưu vai trò
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
