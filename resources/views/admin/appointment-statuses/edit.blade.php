@extends('admin.layouts.app')

@section(
    'title',
    'Sửa trạng thái lịch hẹn - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Cấu hình trạng thái /
        Trạng thái lịch hẹn /
        {{ $appointmentStatus->name }} /
        Sửa

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa trạng thái lịch hẹn
            </h1>

            <p class="page-description">
                Cập nhật cấu hình cho trạng thái
                {{ $appointmentStatus->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.appointment-statuses.show',
                $appointmentStatus
            ) }}"
            class="btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <div class="warning-box">

        <strong>Lưu ý:</strong>

        Tên trạng thái không thể thay đổi.
        Việc thay đổi thứ tự xử lý,
        khả năng chuyển tiếp hoặc trạng thái hoạt động
        có thể ảnh hưởng đến luồng đặt khám.

    </div>

    <form
        method="POST"
        action="{{ route(
            'admin.appointment-statuses.update',
            $appointmentStatus
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.appointment-statuses._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.appointment-statuses.show',
                        $appointmentStatus
                    ) }}"
                    class="btn btn-secondary"
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu thay đổi
                </button>

            </div>

        </div>

    </form>

@endsection

@push('styles')

<style>

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;

        gap: 20px;

        margin-bottom: 20px;
    }

    .warning-box {
        margin-bottom: 20px;

        padding: 14px 16px;

        border: 1px solid #fde68a;
        border-radius: 9px;

        background: #fffbeb;
        color: #92400e;

        font-family:
            "Times New Roman",
            Times,
            serif;

        line-height: 1.6;
    }

    .form-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 18px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;

        margin-bottom: 7px;

        color: #334155;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 14px;
        font-weight: 700;
    }

    .required {
        color: #dc2626;
    }

    .form-control {
        width: 100%;

        padding: 10px 12px;

        border: 1px solid #cbd5e1;
        border-radius: 8px;

        background: #ffffff;

        font-family:
            "Times New Roman",
            Times,
            serif;

        outline: none;
    }

    .form-control:focus {
        border-color: #3b82f6;

        box-shadow:
            0 0 0 3px
            rgba(59, 130, 246, 0.1);
    }

    .readonly-control {
        background: #f8fafc;
        color: #64748b;
        cursor: not-allowed;
    }

    textarea.form-control {
        resize: vertical;
    }

    .form-help {
        margin-top: 5px;

        color: #64748b;

        font-size: 13px;
    }

    .field-error {
        margin-top: 5px;

        color: #dc2626;

        font-size: 13px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;

        gap: 10px;

        padding-top: 18px;

        border-top: 1px solid #e2e8f0;
    }

    @media (max-width: 800px) {

        .form-grid {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush