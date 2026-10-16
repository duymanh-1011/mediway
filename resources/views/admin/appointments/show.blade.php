@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết lịch hẹn - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin /
        Lịch & đặt khám /
        {{ $appointment->appointment_code }}
    </div>

@endsection


@section('content')

    @php

        $statusName =
            $appointment
                ->appointmentStatus
                ?->name;

        $canEdit =
            !$medicalVisit
            &&
            !in_array(
                $statusName,
                [
                    'CANCELLED',
                    'COMPLETED',
                ],
                true
            );

        $canCancel =
            $canEdit;

        $canCheckIn =
            !$medicalVisit
            &&
            $statusName === 'BOOKED';

    @endphp


    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $appointment->appointment_code }}
            </h1>

            <p class="page-description">
                Chi tiết lịch hẹn bệnh nhân.
            </p>

        </div>


        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.appointments.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>


            @if ($canEdit)

                <a
                    href="{{ route(
                        'admin.appointments.edit',
                        $appointment
                    ) }}"
                    class="btn btn-primary"
                >
                    Sửa lịch
                </a>

            @endif

        </div>

    </div>


    @if ($medicalVisit)

        <div class="notice-box">

            <div>

                Lịch hẹn này đã phát sinh lượt khám:

                <strong>
                    {{ $medicalVisit->visit_code }}
                </strong>

            </div>


            <div style="margin-top: 10px;">

                <a
                    href="{{ route(
                        'admin.visits.show',
                        $medicalVisit->id
                    ) }}"
                    class="btn btn-primary"
                >
                    Xem lượt khám
                </a>

            </div>

        </div>

    @endif


    @if ($canCheckIn)

        <div class="checkin-card">

            <div>

                <h3 class="section-title">
                    Check-in bệnh nhân
                </h3>

                <p class="checkin-description">
                    Xác nhận bệnh nhân đã đến bệnh viện.
                    Hệ thống sẽ tạo lượt khám và đưa bệnh nhân
                    vào hàng đợi phòng khám.
                </p>

            </div>


            <form
                method="POST"
                action="{{ route(
                    'admin.appointments.check-in',
                    $appointment
                ) }}"
                onsubmit="
                    return confirm(
                        'Xác nhận check-in bệnh nhân cho lịch hẹn này?'
                    );
                "
            >

                @csrf

                <button
                    type="submit"
                    class="btn-checkin"
                >
                    Check-in bệnh nhân
                </button>

            </form>

        </div>

    @endif


    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin bệnh nhân
            </h3>


            <div class="detail-row">

                <span>
                    Mã bệnh nhân
                </span>

                <strong>
                    {{ $appointment
                        ->patient
                        ?->patient_code
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Họ tên
                </span>

                <strong>
                    {{ $appointment
                        ->patient
                        ?->full_name
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Ngày sinh
                </span>

                <strong>
                    {{ $appointment
                        ->patient
                        ?->date_of_birth
                        ?->format('d/m/Y')
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Giới tính
                </span>

                <strong>
                    {{ $appointment
                        ->patient
                        ?->gender
                        ?? '—' }}
                </strong>

            </div>

        </div>


        <div class="card">

            <h3 class="section-title">
                Thông tin khám
            </h3>


            <div class="detail-row">

                <span>
                    Ngày khám
                </span>

                <strong>
                    {{ $appointment
                        ->appointment_date
                        ?->format('d/m/Y')
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Khung giờ
                </span>

                <strong>

                    {{ substr(
                        $appointment
                            ->appointmentTimeSlot
                            ?->start_time
                            ?? '',
                        0,
                        5
                    ) }}

                    -

                    {{ substr(
                        $appointment
                            ->appointmentTimeSlot
                            ?->end_time
                            ?? '',
                        0,
                        5
                    ) }}

                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Bác sĩ
                </span>

                <strong>

                    {{ $appointment
                        ->doctor
                        ?->doctor_code
                        ?? '—' }}

                    -

                    {{ $appointment
                        ->doctor
                        ?->full_name
                        ?? '—' }}

                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Chuyên khoa
                </span>

                <strong>
                    {{ $appointment
                        ->specialty
                        ?->name
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Phòng khám
                </span>

                <strong>

                    {{ $appointment
                        ->examinationRoom
                        ?->room_code
                        ?? '—' }}

                    -

                    {{ $appointment
                        ->examinationRoom
                        ?->name
                        ?? '—' }}

                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Trạng thái
                </span>

                <strong>
                    {{ $statusName ?? '—' }}
                </strong>

            </div>

        </div>

    </div>


    <div class="card information-card">

        <h3 class="section-title">
            Nội dung đặt khám
        </h3>


        <div class="content-section">

            <strong>
                Lý do khám
            </strong>

            <p>
                {{ $appointment->reason_for_visit }}
            </p>

        </div>


        <div class="content-section">

            <strong>
                Ghi chú bệnh nhân
            </strong>

            <p>
                {{ $appointment->patient_notes ?? '—' }}
            </p>

        </div>


        <div class="content-section">

            <strong>
                Đặt lúc
            </strong>

            <p>
                {{ $appointment
                    ->booked_at
                    ?->format('d/m/Y H:i')
                    ?? '—' }}
            </p>

        </div>

    </div>


    @if ($statusName === 'CANCELLED')

        <div class="card cancellation-card">

            <h3 class="section-title">
                Thông tin hủy lịch
            </h3>


            <div class="detail-row">

                <span>
                    Thời gian hủy
                </span>

                <strong>
                    {{ $appointment
                        ->cancelled_at
                        ?->format('d/m/Y H:i')
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Lý do
                </span>

                <strong>
                    {{ $appointment
                        ->cancellation_reason
                        ?? '—' }}
                </strong>

            </div>

        </div>

    @endif


    @if ($canCancel)

        <div class="card cancellation-form">

            <h3 class="section-title danger-title">
                Hủy lịch hẹn
            </h3>

            <p class="warning-text">
                Hủy lịch sẽ chuyển trạng thái sang
                CANCELLED, không xóa dữ liệu lịch sử.
            </p>


            <form
                method="POST"
                action="{{ route(
                    'admin.appointments.cancel',
                    $appointment
                ) }}"
                onsubmit="
                    return confirm(
                        'Bạn có chắc muốn hủy lịch hẹn này?'
                    );
                "
            >

                @csrf


                <div class="form-group">

                    <label>
                        Lý do hủy
                    </label>

                    <textarea
                        name="cancellation_reason"
                        class="form-control"
                        rows="3"
                        required
                    >{{ old('cancellation_reason') }}</textarea>


                    @error('cancellation_reason')

                        <div class="field-error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                <button
                    type="submit"
                    class="btn-danger"
                >
                    Hủy lịch hẹn
                </button>

            </form>

        </div>

    @endif

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


    .header-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }


    .notice-box {
        margin-bottom: 20px;
        padding: 14px 16px;

        border: 1px solid #fde68a;
        border-radius: 8px;

        background: #fffbeb;
        color: #92400e;
    }


    .checkin-card {
        margin-bottom: 20px;
        padding: 18px 20px;

        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;

        border: 1px solid #bbf7d0;
        border-radius: 10px;

        background: #f0fdf4;
    }


    .checkin-card .section-title {
        color: #166534;
    }


    .checkin-description {
        margin: 0;

        color: #475569;
        line-height: 1.6;
    }


    .btn-checkin {
        padding: 10px 18px;

        border: none;
        border-radius: 8px;

        background: #16a34a;
        color: #ffffff;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 16px;
        font-weight: 700;

        cursor: pointer;
        white-space: nowrap;
    }


    .btn-checkin:hover {
        opacity: 0.9;
    }


    .detail-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 20px;
        margin-bottom: 20px;
    }


    .section-title {
        margin-top: 0;
        margin-bottom: 15px;
    }


    .detail-row {
        display: grid;

        grid-template-columns:
            140px minmax(0, 1fr);

        gap: 15px;

        padding: 11px 0;

        border-bottom: 1px solid #f1f5f9;
    }


    .detail-row:last-child {
        border-bottom: none;
    }


    .detail-row span {
        color: #64748b;
    }


    .information-card,
    .cancellation-card,
    .cancellation-form {
        margin-top: 20px;
    }


    .content-section {
        margin-bottom: 18px;
    }


    .content-section:last-child {
        margin-bottom: 0;
    }


    .content-section p {
        margin-bottom: 0;
        line-height: 1.6;
    }


    .danger-title {
        color: #b91c1c;
    }


    .warning-text {
        color: #64748b;
    }


    .form-group {
        margin-bottom: 15px;
    }


    .form-group label {
        display: block;
        margin-bottom: 6px;

        font-weight: 700;
    }


    .form-control {
        width: 100%;

        padding: 10px 12px;

        border: 1px solid #cbd5e1;
        border-radius: 8px;

        font-family:
            "Times New Roman",
            Times,
            serif;
    }


    .field-error {
        margin-top: 5px;
        color: #dc2626;
    }


    .btn-danger {
        padding: 9px 15px;

        border: none;
        border-radius: 8px;

        background: #dc2626;
        color: #ffffff;

        font-family:
            "Times New Roman",
            Times,
            serif;

        cursor: pointer;
    }


    @media (max-width: 800px) {

        .page-header {
            flex-direction: column;
        }


        .detail-grid {
            grid-template-columns: 1fr;
        }


        .checkin-card {
            flex-direction: column;
            align-items: flex-start;
        }

    }

</style>

@endpush
