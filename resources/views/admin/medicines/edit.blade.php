@extends('admin.layouts.app')

@section(
    'title',
    'Sửa thuốc - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Thuốc /
        {{ $medicine->medicine_code }} /
        Sửa

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa thuốc
            </h1>

            <p class="page-description">
                Cập nhật thông tin
                {{ $medicine->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.medicines.show',
                $medicine
            ) }}"
            class="btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form
        method="POST"
        action="{{ route(
            'admin.medicines.update',
            $medicine
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include('admin.medicines._form')

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.medicines.show',
                        $medicine
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

        font-family:
            "Times New Roman",
            Times,
            serif;

        color: #334155;
        font-size: 14px;
        font-weight: 700;
    }

    .required {
        color: #dc2626;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;

        font-family:
            "Times New Roman",
            Times,
            serif;

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