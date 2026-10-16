@extends('admin.layouts.app')

@section(
    'title',
    'Thêm khung giờ khám - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Lịch & đặt khám /
        Lịch làm việc bác sĩ /
        #{{ $doctorSchedule->id }} /
        Thêm khung giờ

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm khung giờ khám
            </h1>

            <p class="page-description">
                Tạo slot khám thuộc lịch làm việc của bác sĩ.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.doctor-schedules.show',
                $doctorSchedule
            ) }}"
            class="btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>


    <form
        method="POST"
        action="{{ route(
            'admin.doctor-schedules.time-slots.store',
            $doctorSchedule
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.appointment-time-slots._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.doctor-schedules.show',
                        $doctorSchedule
                    ) }}"
                    class="btn btn-secondary"
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu khung giờ
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

    .schedule-summary {
        margin-bottom: 20px;
        padding: 14px 16px;

        border: 1px solid #bfdbfe;
        border-radius: 8px;

        background: #eff6ff;
        color: #1e3a8a;

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

    .field-error {
        margin-top: 5px;

        color: #dc2626;

        font-size: 13px;
    }

    .slot-error {
        margin-bottom: 18px;
        padding: 13px 15px;

        border: 1px solid #fecaca;
        border-radius: 8px;

        background: #fef2f2;
        color: #b91c1c;
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