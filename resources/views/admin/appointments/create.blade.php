@extends('admin.layouts.app')

@section(
    'title',
    'Thêm lịch hẹn - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin /
        Lịch & đặt khám /
        Thêm lịch hẹn
    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm lịch hẹn
            </h1>

            <p class="page-description">
                Tạo lịch khám cho bệnh nhân.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.appointments.index'
            ) }}"
            class="btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>


    <form
        method="POST"
        action="{{ route(
            'admin.appointments.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.appointments._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.appointments.index'
                    ) }}"
                    class="btn btn-secondary"
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tạo lịch hẹn
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