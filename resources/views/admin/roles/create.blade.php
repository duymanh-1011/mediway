@extends('admin.layouts.app')

@section('title', 'Thêm vai trò - Mediway')

@section('breadcrumb')
    <div class="breadcrumb">
        Admin / Hệ thống /
        <a href="{{ route('admin.roles.index') }}">Vai trò</a>
        / Thêm mới
    </div>
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

    <form action="{{ route('admin.roles.store') }}" method="POST">
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
                    <div class="field-error">{{ $message }}</div>
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
                    <div class="field-error">{{ $message }}</div>
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
                    <div class="field-error">{{ $message }}</div>
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

@push('styles')
<style>
    .role-form-page { max-width: 960px; margin: 0 auto; }

    .role-page-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 22px;
    }

    .role-page-heading .page-description { margin-bottom: 0; }

    .role-form-card { padding: 28px; }

    .role-card-heading {
        padding-bottom: 18px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }

    .role-card-heading h2 {
        font-size: 18px;
        margin: 0 0 8px;
    }

    .role-card-heading p {
        color: #64748b;
        font-size: 13px;
        margin: 0;
    }

    .role-form-group { margin-bottom: 22px; }

    .role-form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
    }

    .required { color: #dc2626; }

    .role-form-group input,
    .role-form-group textarea,
    .role-form-group select {
        display: block;
        width: 100%;
        padding: 11px 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #0f172a;
        outline: none;
    }

    .role-form-group textarea {
        resize: vertical;
        min-height: 110px;
    }

    .role-form-group input:focus,
    .role-form-group textarea:focus,
    .role-form-group select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px #dbeafe;
    }

    .role-form-group .input-invalid { border-color: #dc2626; }

    .role-form-group small {
        display: block;
        color: #64748b;
        font-size: 12px;
        margin-top: 7px;
    }
.field-error {
        color: #dc2626;
        font-size: 12px;
        margin-top: 6px;
    }

    .role-info-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 24px;
        font-size: 13px;
        color: #1e40af;
    }

    .role-info-box p {
        margin: 6px 0 0;
        line-height: 1.6;
    }

    .role-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        border-top: 1px solid #e2e8f0;
        padding-top: 22px;
    }

    @media (max-width: 650px) {
        .role-page-heading {
            flex-direction: column;
            align-items: flex-start;
        }

        .role-form-card { padding: 18px; }
    }
</style>
@endpush