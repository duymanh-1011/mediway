@extends('admin.layouts.app')

@section('title', 'Sửa trạng thái dịch vụ')

@section('content')

    <div class="status-edit-page">

        {{-- Breadcrumb --}}
        <div class="status-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.service-statuses.index') }}">
                Trạng thái dịch vụ
            </a>

            <span>/</span>

            <a href="{{ route(
        'admin.service-statuses.show',
        $status->id
    ) }}">
                {{ $status->name }}
            </a>

            <span>/</span>

            <strong>
                Sửa
            </strong>

        </div>


        {{-- Header --}}
        <div class="status-edit-header">

            <div>

                <div class="status-module-label">
                    CẤU HÌNH TRẠNG THÁI
                </div>

                <h1>
                    Sửa trạng thái dịch vụ
                </h1>

                <p>
                    Cập nhật mô tả, quyền chuyển tiếp
                    và trạng thái sử dụng.
                </p>

            </div>

            <div class="status-edit-badge">
                <span></span>
                Chỉnh sửa có kiểm soát
            </div>

        </div>


        {{-- Back --}}
        <div class="status-back-wrapper">

            <a href="{{ route(
        'admin.service-statuses.show',
        $status->id
    ) }}" class="status-back-btn">
                ← Quay lại chi tiết
            </a>

        </div>


        <div class="status-edit-layout">

            {{-- Form --}}
            <div class="status-form-card">

                <div class="status-card-header">

                    <div>

                        <h2>
                            Thông tin trạng thái
                        </h2>

                        <p>
                            Chỉ các trường được phép mới có thể chỉnh sửa.
                        </p>

                    </div>

                </div>


                <form method="POST" action="{{ route(
        'admin.service-statuses.update',
        $status->id
    ) }}">

                    @csrf
                    @method('PATCH')


                    <div class="status-form-body">

                        {{-- Name --}}
                        <div class="status-form-group">

                            <label>
                                Tên trạng thái
                            </label>

                            <input type="text" class="status-control status-control-readonly" value="{{ $status->name }}"
                                readonly>

                            <div class="status-help">
                                Tên trạng thái là khóa nghiệp vụ
                                và không được thay đổi tùy tiện.
                            </div>

                        </div>


                        {{-- Description --}}
                        <div class="status-form-group">

                            <label for="description">
                                Mô tả
                            </label>

                            <textarea id="description" name="description" rows="4" maxlength="255" class="status-textarea
                                            @error('description')
                                                status-control-error
                                            @enderror" placeholder="Nhập mô tả trạng thái...">{{ old(
        'description',
        $status->description
    ) }}</textarea>

                            <div class="status-help">
                                Tối đa 255 ký tự.
                            </div>

                            @error('description')

                                <div class="status-error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        {{-- Allows Transition --}}
                        <div class="status-form-group">

                            <label for="allows_transition">
                                Cho phép chuyển tiếp
                                <span class="required">*</span>
                            </label>

                            <select id="allows_transition" name="allows_transition" class="status-control
                                            @error('allows_transition')
                                                status-control-error
                                            @enderror">

                                <option value="">
                                    -- Chọn quyền chuyển trạng thái --
                                </option>

                                <option value="1" @selected(
                                    old(
                                        'allows_transition',
                                        $status->allows_transition
                                        ? '1'
                                        : '0'
                                    ) === '1'
                                )>
                                    Cho phép
                                </option>

                                <option value="0" @selected(
                                    old(
                                        'allows_transition',
                                        $status->allows_transition
                                        ? '1'
                                        : '0'
                                    ) === '0'
                                )>
                                    Không cho phép
                                </option>

                            </select>

                            <div class="status-help">
                                Xác định trạng thái này có cho phép
                                tiếp tục chuyển sang bước nghiệp vụ
                                tiếp theo hay không.
                            </div>

                            @error('allows_transition')

                                <div class="status-error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        {{-- Usage Status --}}
                        <div class="status-form-group">

                            <label>
                                Trạng thái sử dụng
                            </label>

                            <select name="is_active" class="status-control">

                                <option value="">
                                    Tất cả
                                </option>

                                <option value="1" @selected(request('is_active') === '1')>
                                    Đang sử dụng
                                </option>

                                <option value="0" @selected(request('is_active') === '0')>
                                    Ngừng sử dụng
                                </option>

                            </select>

                        </div>


                        {{-- Actions --}}
                        <div class="status-form-actions">

                            <a href="{{ route(
        'admin.service-statuses.show',
        $status->id
    ) }}" class="status-btn status-btn-secondary">
                                Hủy
                            </a>

                            <button type="submit" class="status-btn status-btn-primary">
                                Lưu thay đổi
                            </button>

                        </div>

                </form>

            </div>


            {{-- Side --}}
            <div class="status-edit-side">

                {{-- Policy --}}
                <div class="status-policy-card">

                    <div class="status-policy-icon">
                        !
                    </div>

                    <div>

                        <strong>
                            Chính sách dữ liệu
                        </strong>

                        <p>
                            Không đổi tên trạng thái tùy tiện.
                            Không thay đổi thứ tự xử lý tại màn hình này.
                            Không hard-delete trạng thái đã được sử dụng.
                        </p>

                    </div>

                </div>


                {{-- Current --}}
                <div class="status-current-card">

                    <div class="status-current-header">
                        Trạng thái hiện tại
                    </div>


                    <div class="status-current-item">

                        <span>
                            Tên
                        </span>

                        <strong class="status-current-name">
                            {{ $status->name }}
                        </strong>

                    </div>


                    <div class="status-current-item">

                        <span>
                            Chuyển tiếp
                        </span>

                        @if ($status->allows_transition)

                            <strong class="text-success">
                                Cho phép
                            </strong>

                        @else

                            <strong class="text-muted">
                                Không cho phép
                            </strong>

                        @endif

                    </div>


                    
<div class="status-form-group">

    <label for="is_active">
        Trạng thái sử dụng
        <span class="required">*</span>
    </label>

    <select
        id="is_active"
        name="is_active"
        class="status-control
            @error('is_active')
                status-control-error
            @enderror"
    >

        <option value="">
            -- Chọn trạng thái sử dụng --
        </option>

        <option
            value="1"
            @selected(
                old(
                    'is_active',
                    $status->is_active
                        ? '1'
                        : '0'
                ) === '1'
            )
        >
            Đang sử dụng
        </option>

        <option
            value="0"
            @selected(
                old(
                    'is_active',
                    $status->is_active
                        ? '1'
                        : '0'
                ) === '0'
            )
        >
            Ngừng sử dụng
        </option>

    </select>

    <div class="status-help">
        Chọn trạng thái này còn được sử dụng
        trong hệ thống hay tạm ngừng sử dụng.
    </div>

    @error('is_active')
        <div class="status-error">
            {{ $message }}
        </div>
    @enderror

</div>

</div>


                </div>

            </div>

        </div>

    </div>


    <style>
        .status-edit-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;
            color: var(--text);
        }

        .status-edit-page * {
            box-sizing: border-box;
        }


        /* =========================
                   BREADCRUMB
                ========================= */

        .status-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .status-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .status-breadcrumb a:hover {
            color: var(--primary);
        }

        .status-breadcrumb strong {
            color: #334155;
        }


        /* =========================
                   HEADER
                ========================= */

        .status-edit-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;
        }

        .status-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .status-edit-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;
        }

        .status-edit-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .status-edit-badge {
            display: flex;
            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border: 1px solid #fde68a;
            border-radius: 999px;

            background: #fffbeb;

            color: #b45309;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .status-edit-badge span {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #f59e0b;
        }


        /* =========================
                   BACK
                ========================= */

        .status-back-wrapper {
            margin-bottom: 20px;
        }

        .status-back-btn {
            color: #475569;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;
        }

        .status-back-btn:hover {
            color: var(--primary);
        }


        /* =========================
                   LAYOUT
                ========================= */

        .status-edit-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 2fr) minmax(280px, .75fr);

            gap: 22px;

            align-items: start;
        }

        .status-edit-side {
            display: grid;

            gap: 18px;
        }


        /* =========================
                   CARDS
                ========================= */

        .status-form-card,
        .status-current-card {
            overflow: hidden;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .status-card-header {
            padding: 18px 20px;

            border-bottom: 1px solid var(--border);
        }

        .status-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .status-card-header p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;
        }


        /* =========================
                   FORM
                ========================= */

        .status-form-body {
            display: grid;

            gap: 20px;

            padding: 20px;
        }

        .status-form-group label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 12px;
            font-weight: 700;
        }

        .required {
            color: #dc2626;
        }

        .status-control,
        .status-textarea {
            display: block;

            width: 100%;

            border: 1px solid #cbd5e1;
            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #0f172a;

            font-family: inherit;
            font-size: 13px;

            transition: .2s;
        }

        .status-control {
            height: 42px;

            padding: 0 12px;
        }

        .status-textarea {
            min-height: 105px;

            padding: 11px 12px;

            resize: vertical;

            line-height: 1.6;
        }

        .status-control:hover,
        .status-textarea:hover {
            border-color: #94a3b8;
        }

        .status-control:focus,
        .status-textarea:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        .status-control-readonly {
            border-color: #e2e8f0;

            background: #f8fafc;

            color: #64748b;

            cursor: not-allowed;
        }

        .status-control-error {
            border-color: #ef4444;

            background: #fffafa;
        }

        .status-control-error:focus {
            border-color: #ef4444;

            box-shadow:
                0 0 0 3px rgba(239, 68, 68, .12);
        }

        .status-help {
            margin-top: 6px;

            color: #94a3b8;

            font-size: 11px;

            line-height: 1.5;
        }

        .status-error {
            margin-top: 6px;

            color: #dc2626;

            font-size: 11px;
            font-weight: 650;

            line-height: 1.5;
        }


        /* =========================
                   ACTIONS
                ========================= */

        .status-form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;

            gap: 10px;

            padding: 16px 20px;

            border-top: 1px solid var(--border);

            background: #fbfdff;
        }

        .status-btn {
            display: inline-flex;

            height: 40px;

            align-items: center;
            justify-content: center;

            padding: 0 16px;

            border-radius: 9px;

            font-family: inherit;
            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;
        }

        .status-btn-primary {
            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }

        .status-btn-primary:hover {
            background: var(--primary-dark);
        }

        .status-btn-secondary {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #475569;
        }

        .status-btn-secondary:hover {
            background: #f8fafc;
        }


        /* =========================
                   POLICY
                ========================= */

        .status-policy-card {
            display: flex;
            align-items: flex-start;

            gap: 12px;

            padding: 16px;

            border: 1px solid #fde68a;
            border-radius: 12px;

            background: #fffbeb;
        }

        .status-policy-icon {
            display: flex;

            width: 30px;
            height: 30px;

            flex: 0 0 30px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #f59e0b;

            color: #ffffff;

            font-size: 12px;
            font-weight: 800;
        }

        .status-policy-card strong {
            display: block;

            margin-bottom: 5px;

            color: #92400e;

            font-size: 12px;
        }

        .status-policy-card p {
            margin: 0;

            color: #78350f;

            font-size: 11px;

            line-height: 1.65;
        }


        /* =========================
                   CURRENT
                ========================= */

        .status-current-header {
            padding: 16px 18px;

            border-bottom: 1px solid var(--border);

            color: #0f172a;

            font-size: 13px;
            font-weight: 750;
        }

        .status-current-item {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 12px;

            padding: 14px 18px;

            border-bottom: 1px solid #edf2f7;

            font-size: 12px;
        }

        .status-current-item:last-child {
            border-bottom: none;
        }

        .status-current-item span {
            color: #64748b;
        }

        .status-current-item strong {
            color: #0f172a;

            text-align: right;
        }

        .status-current-name {
            color: #1d4ed8 !important;

            font-family:
                Consolas,
                Monaco,
                monospace;
        }

        .text-success {
            color: #15803d !important;
        }

        .text-danger {
            color: #b91c1c !important;
        }

        .text-muted {
            color: #64748b !important;
        }


        /* =========================
                   RESPONSIVE
                ========================= */

        @media (max-width: 1000px) {

            .status-edit-layout {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 768px) {

            .status-edit-header {
                flex-direction: column;
            }

            .status-edit-header h1 {
                font-size: 24px;
            }

            .status-form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .status-btn {
                width: 100%;
            }

        }
    </style>

@endsection