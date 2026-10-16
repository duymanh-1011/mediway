@extends('admin.layouts.app')

@section(
    'title',
    'Sửa khung giờ khám - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Lịch & đặt khám /
        Lịch làm việc bác sĩ /
        #{{ $appointmentTimeSlot->doctor_schedule_id }} /
        Sửa khung giờ

    </div>

@endsection

@section('content')

    @php
        $doctorSchedule =
            $appointmentTimeSlot->doctorSchedule;
    @endphp


    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa khung giờ khám
            </h1>

            <p class="page-description">
                Cập nhật sức chứa, trạng thái
                hoặc thời gian của slot.
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


    @if ($appointmentsCount > 0)

        <div class="warning-box">

            Khung giờ này đã có
            <strong>{{ $appointmentsCount }}</strong>
            lịch hẹn.

            Không được thay đổi giờ bắt đầu/kết thúc
            và không được giảm sức chứa xuống dưới
            {{ $appointmentsCount }}.

        </div>

    @endif


    <form
        method="POST"
        action="{{ route(
            'admin.appointment-time-slots.update',
            $appointmentTimeSlot
        ) }}"
    >

        @csrf
        @method('PUT')

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

    .schedule-summary,
    .warning-box {
        margin-bottom: 20px;
        padding: 14px 16px;

        border-radius: 8px;

        line-height: 1.6;
    }

    .schedule-summary {
        border: 1px solid #bfdbfe;

        background: #eff6ff;
        color: #1e3a8a;
    }

    .warning-box {
        border: 1px solid #fde68a;

        background: #fffbeb;
        color: #92400e;
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