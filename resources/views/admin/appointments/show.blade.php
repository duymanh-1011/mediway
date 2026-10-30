@extends('admin.layouts.app')
@section('admin-page', 'appointments-show')

@section(
    'title',
    'Chi tiết lịch hẹn - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">@include('admin.layouts.partials.record-code', ['code' => $appointment->appointment_code])</strong>
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
            $statusName === 'BOOKED'
            && $appointment->receptionUnavailableReason() === null;

    @endphp


    <div class="page-header">

        <div>

            <h1 class="page-title">
                @include('admin.layouts.partials.record-code', ['code' => $appointment->appointment_code])
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
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>


            @if ($canEdit)

                <a
                    href="{{ route(
                        'admin.appointments.edit',
                        $appointment
                    ) }}"
                    class="admin-btn admin-btn-secondary"
                >Sửa</a>

            @endif

        </div>

    </div>


    @if ($medicalVisit)

        <div class="notice-box">

            <div>

                Lịch hẹn này đã phát sinh lượt khám:

                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $medicalVisit->visit_code])
                </strong>

            </div>


            <div class="admin-u-e5a6d3c3">

                <a
                    href="{{ route(
                        'admin.visits.show',
                        $medicalVisit->id
                    ) }}"
                    class="admin-btn admin-btn-detail"
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
                    Tiếp nhận bệnh nhân
                </h3>

                <p class="checkin-description">
                    Xác nhận bệnh nhân đã đến bệnh viện.
                    Hệ thống sẽ tạo lượt khám và đưa bệnh nhân
                    vào hàng đợi phòng khám.
                </p>

            </div>


            <form novalidate
                method="POST"
                action="{{ route(
                    'admin.appointments.check-in',
                    $appointment
                ) }}"
                onsubmit="
                    return confirm(
                        'Xác nhận tiếp nhận bệnh nhân cho lịch hẹn này?'
                    );
                "
            >

                @csrf

                <button
                    type="submit"
                    class="admin-btn admin-btn-primary"
                >
                    Tiếp nhận bệnh nhân
                </button>

            </form>

        </div>

    @elseif (!$medicalVisit && $statusName === 'BOOKED')
        <div class="notice-box" role="status">{{ $appointment->receptionUnavailableReason() }}</div>
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
                    @include('admin.layouts.partials.record-code', ['code' => $appointment
                        ->patient
                        ?->patient_code
                        ?? '—'])
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

                    @include('admin.layouts.partials.record-code', ['code' => $appointment
                        ->doctor
                        ?->doctor_code
                        ?? '—'])

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

                    @include('admin.layouts.partials.record-code', ['code' => $appointment
                        ->examinationRoom
                        ?->room_code
                        ?? '—'])

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

                <strong>@include('admin.layouts.partials.status-badge', ['badgeCode' => $statusName ?? '—'])</strong>

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


            <form novalidate
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
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>

                    @enderror

                </div>


                <button
                    type="submit"
                    class="admin-btn admin-btn-danger"
                >
                    Hủy lịch hẹn
                </button>

            </form>

        </div>

    @endif

@endsection
