@extends('admin.layouts.app')

@section(
    'title',
    'Sửa phòng khám - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Phòng khám /
        {{ $examinationRoom->room_code }} /
        Sửa

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa phòng khám
            </h1>

            <p class="page-description">
                Cập nhật thông tin
                {{ $examinationRoom->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.examination-rooms.show',
                $examinationRoom
            ) }}"
            class="btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form
        method="POST"
        action="{{ route(
            'admin.examination-rooms.update',
            $examinationRoom
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.examination-rooms._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.examination-rooms.show',
                        $examinationRoom
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

        font-size: 13px;
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

        outline: none;
    }

    .form-control:focus {
        border-color: #3b82f6;

        box-shadow:
            0 0 0 3px
            rgba(59, 130, 246, 0.1);
    }

    textarea.form-control {
        resize: vertical;
    }

    .field-error {
        margin-top: 5px;

        color: #dc2626;

        font-size: 12px;
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