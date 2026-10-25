@extends('admin.layouts.app')
@section('admin-page', 'roles-edit')

@section('title', 'Sửa vai trò - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <a href="{{ route('admin.roles.index') }}">Vai trò</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')
<div class="role-edit-page">

    <div class="role-edit-heading">
        <div>
            <h1 class="page-title">Sửa vai trò</h1>
            <p class="page-description">
                Cập nhật thông tin vai trò {{ \App\Models\AdminDisplay::label($role->name) }}.
            </p>
        </div>

        <a href="{{ route('admin.roles.show', $role) }}"
           class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>

    <form novalidate action="{{ route('admin.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card role-edit-card">

            <h2 class="role-edit-title">Thông tin vai trò</h2>

            @if ($role->isSystemRole())
                <div class="role-warning">
                    <strong>Vai trò hệ thống</strong>
                    <p>
                        Đây là vai trò cốt lõi của Mediway.
                        Bạn chỉ có thể chỉnh sửa mô tả.
                        Tên và trạng thái được bảo vệ.
                    </p>
                </div>
            @elseif ($role->users_count > 0)
                <div class="role-warning">
                    Vai trò đang có {{ $role->users_count }} tài khoản liên kết.
                    Không thể vô hiệu hóa vai trò này.
                </div>
            @endif

            <div class="role-edit-field">
                <label for="name">Tên vai trò</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $role->name) }}"
                    maxlength="50"
                    {{ $role->isSystemRole() ? 'disabled' : 'required' }}
                    class="{{ $errors->has('name') ? 'input-invalid' : '' }}"
                >

                @error('name')
                    <div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                @enderror
            </div>

            <div class="role-edit-field">
                <label for="description">Mô tả</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="255"
                    class="{{ $errors->has('description') ? 'input-invalid' : '' }}"
                >{{ old('description', $role->description) }}</textarea>

                @error('description')
                    <div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                @enderror
            </div>

            <div class="role-edit-field">
                <label for="status">Trạng thái</label>

                <select
                    id="status"
name="status"
                    {{ $role->isSystemRole() ? 'disabled' : '' }}
                    class="{{ $errors->has('status') ? 'input-invalid' : '' }}"
                >
                    <option
                        value="ACTIVE"
                        {{ old('status', $role->status) === 'ACTIVE' ? 'selected' : '' }}
                    >
                        Hoạt động
                    </option>

                    <option
                        value="INACTIVE"
                        {{ old('status', $role->status) === 'INACTIVE' ? 'selected' : '' }}
                        {{ $role->users_count > 0 ? 'disabled' : '' }}
                    >
                        Ngừng hoạt động
                    </option>
                </select>

                @error('status')
                    <div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                @enderror
            </div>

            <div class="role-edit-actions">
                <a href="{{ route('admin.roles.show', $role) }}"
                   class="admin-btn admin-btn-secondary">Hủy</a>

                <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>
            </div>

        </div>
    </form>

</div>
@endsection
