@extends('admin.layouts.app')

@section('title', 'Chỉnh sửa vai trò - Mediway')

@section('breadcrumb')
    <div class="breadcrumb">
        Admin / Hệ thống /
        <a href="{{ route('admin.roles.index') }}">Vai trò</a>
        / Chỉnh sửa
    </div>
@endsection

@section('content')
<div class="role-edit-page">

    <div class="role-edit-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa vai trò</h1>
            <p class="page-description">
                Cập nhật thông tin vai trò {{ $role->name }}.
            </p>
        </div>

        <a href="{{ route('admin.roles.show', $role) }}"
           class="btn btn-secondary">
            ← Quay lại chi tiết
        </a>
    </div>

    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
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
                    <div class="field-error">{{ $message }}</div>
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
                    <div class="field-error">{{ $message }}</div>
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
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="role-edit-actions">
                <a href="{{ route('admin.roles.show', $role) }}"
                   class="btn btn-secondary">
                    Hủy bỏ
                </a>

                <button type="submit" class="btn btn-primary">
                    Lưu thay đổi
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('styles')
<style>
    .role-edit-page { max-width: 960px; margin: 0 auto; }

    .role-edit-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 22px;
    }

    .role-edit-heading .page-description { margin-bottom: 0; }

    .role-edit-card { padding: 28px; }

    .role-edit-title {
        font-size: 18px;
        margin: 0 0 22px;
        padding-bottom: 17px;
        border-bottom: 1px solid #e2e8f0;
    }

    .role-warning {
        padding: 15px;
        margin-bottom: 22px;
        border-radius: 9px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: 13px;
        line-height: 1.6;
    }

    .role-warning p { margin: 6px 0 0; }

    .role-edit-field { margin-bottom: 23px; }

    .role-edit-field label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
    }

    .role-edit-field input,
    .role-edit-field textarea,
    .role-edit-field select {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #0f172a;
        outline: none;
    }

    .role-edit-field textarea { resize: vertical; }

    .role-edit-field input:focus,
    .role-edit-field textarea:focus,
    .role-edit-field select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px #dbeafe;
    }

    .role-edit-field input:disabled,
    .role-edit-field select:disabled {
        background: #f1f5f9;
color: #64748b;
        cursor: not-allowed;
    }

    .role-edit-field .input-invalid { border-color: #dc2626; }

    .field-error {
        margin-top: 6px;
        font-size: 12px;
        color: #dc2626;
    }

    .role-edit-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }

    @media (max-width: 650px) {
        .role-edit-card { padding: 18px; }
    }
</style>
@endpush