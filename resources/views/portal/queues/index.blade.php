@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/queues-index.css')
@endpush

@section('title', 'Lịch khám và hàng đợi - Mediway')

@section('content')


    <div class="patient-queue-page">
        <section class="portal-page-head">
            <div class="portal-shell">
                <p class="portal-eyebrow">
                    PATIENT PORTAL
                </p>

                <h1>
                    Lịch khám và hàng đợi
                </h1>

                <p class="portal-lead">
                    Theo dõi lịch khám, trạng thái tiếp nhận và hàng đợi của bạn.
                    Patient Portal chỉ hiển thị thông tin và không thay đổi quy trình tiếp nhận.
                </p>
            </div>
        </section>

        <section class="queue-summary-section">
            <div class="portal-shell">
                <div class="queue-summary-grid">
                    <div class="queue-summary-card">
                        <div class="queue-summary-label">
                            Tổng lịch hẹn
                        </div>

                        <div class="queue-summary-value">
                            {{ $summary['appointment_count'] }}
                        </div>
                    </div>

                    <div class="queue-summary-card">
                        <div class="queue-summary-label">
                            Lịch hôm nay
                        </div>

                        <div class="queue-summary-value">
                            {{ $summary['today_count'] }}
                        </div>
                    </div>

                    <div class="queue-summary-card">
                        <div class="queue-summary-label">
                            Lượt hàng đợi
                        </div>

                        <div class="queue-summary-value">
                            {{ $summary['queue_count'] }}
                        </div>
                    </div>

                    <div class="queue-summary-card">
                        <div class="queue-summary-label">
                            Chưa hoàn tất
                        </div>

                        <div class="queue-summary-value">
                            {{ $summary['waiting_queue_count'] }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="queue-content-section">
            <div class="portal-shell">
                <section class="queue-section">
                    <div class="queue-section-head">
                        <h2>
                            Lịch khám của tôi
                        </h2>

                        <p>
                            Chỉ hiển thị các lịch hẹn thuộc hồ sơ bệnh nhân đang đăng nhập.
                        </p>
                    </div>

                    <div class="queue-card-list">
                        @forelse ($appointments as $appointment)
                                        @php
                                            $appointmentStatus =
                                                $appointment
                                                    ->appointmentStatus
                                                        ?->description
                                                ??
                                                $appointment
                                                    ->appointmentStatus
                                                        ?->name
                                                ??
                                                'Chưa cập nhật';
                                        @endphp

                                        <article class="patient-queue-card">
                                            <div class="patient-queue-top">
                                                <div>
                                                    <h3 class="queue-code">
                                                        {{ $appointment->appointment_code }}
                                                    </h3>

                                                    <p class="queue-subtitle">
                                                        {{
                            $appointment
                                ->appointment_date
                                    ?->format('d/m/Y')
                            ??
                            'Chưa cập nhật'
                                                                }}
                                                    </p>
                                                </div>

                                                <span class="queue-status">
                                                    {{ $appointmentStatus }}
                                                </span>
                                            </div>

                                            <div class="queue-detail-grid">
                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Bác sĩ
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $appointment->doctor?->full_name ?? 'Chưa cập nhật' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Chuyên khoa
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $appointment->specialty?->name ?? 'Chưa cập nhật' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Phòng khám
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $appointment->examinationRoom?->name ?? 'Chưa cập nhật' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Khung giờ
                                                    </span>

                                                    <span class="queue-info-value">
                                                        @if ($appointment->appointmentTimeSlot)
                                                                                    {{
                                                            substr(
                                                                (string) $appointment
                                                                    ->appointmentTimeSlot
                                                                    ->start_time,
                                                                0,
                                                                5
                                                            )
                                                                                                }}

                                                                                    -

                                                                                    {{
                                                            substr(
                                                                (string) $appointment
                                                                    ->appointmentTimeSlot
                                                                    ->end_time,
                                                                0,
                                                                5
                                                            )
                                                                                                }}
                                                        @else
                                                            Chưa cập nhật
                                                        @endif
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Lượt khám
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $appointment->medicalVisit?->visit_code ?? 'Chưa tiếp nhận' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Tiếp nhận lúc
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{
                            $appointment
                                ->medicalVisit
                                ?->checked_in_at
                                    ?->format('H:i d/m/Y')
                            ??
                            'Chưa tiếp nhận'
                                                                }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="queue-actions">
                                                <a href="{{ route('patient.appointments.show', $appointment) }}" class="queue-detail-link">
                                                    Xem chi tiết lịch hẹn
                                                </a>
                                            </div>
                                        </article>
                        @empty
                            <div class="queue-empty">
                                <h3>
                                    Chưa có lịch khám
                                </h3>

                                <p>
                                    Bạn chưa có lịch hẹn nào được ghi nhận.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="queue-section">
                    <div class="queue-section-head">
                        <h2>
                            Hàng đợi phòng khám
                        </h2>

                        <p>
                            Theo dõi số thứ tự và trạng thái của các lượt đã được tiếp nhận.
                        </p>
                    </div>

                    <div class="queue-card-list">
                        @forelse ($queues as $queue)
                                        @php
                                            $visit = $queue->medicalVisit;

                                            $appointment =
                                                $visit?->appointment_id
                                                ? $appointmentsById->get(
                                                    $visit->appointment_id
                                                )
                                                : null;

                                            $doctorName =
                                                $visit?->doctor?->full_name
                                                ??
                                                $appointment?->doctor?->full_name
                                                ??
                                                'Chưa cập nhật';

                                            $specialtyName =
                                                $visit?->specialty?->name
                                                ??
                                                $appointment?->specialty?->name
                                                ??
                                                'Chưa cập nhật';

                                            $roomName =
                                                $visit?->examinationRoom?->name
                                                ??
                                                $appointment?->examinationRoom?->name
                                                ??
                                                'Chưa cập nhật';

                                            $queueStatus =
                                                $queue
                                                    ->status
                                                        ?->description
                                                ??
                                                $queue
                                                    ->status
                                                        ?->name
                                                ??
                                                'Chưa cập nhật';

                                            $visitStatus =
                                                $visit
                                                    ?->status
                                                        ?->description
                                                ??
                                                $visit
                                                    ?->status
                                                        ?->name
                                                ??
                                                'Chưa cập nhật';

                                            $queueTypeLabel =
                                                match ($queue->queue_type) {
                                                    'INITIAL_EXAM' =>
                                                        'Khám ban đầu',

                                                    'RETURN_AFTER_SERVICE' =>
                                                        'Quay lại sau dịch vụ',

                                                    default =>
                                                        $queue->queue_type
                                                        ?: 'Chưa cập nhật',
                                                };

                                            $receptionLabel =
                                                match ($visit?->reception_source) {
                                                    'COUNTER' =>
                                                        'Tiếp nhận tại quầy',

                                                    'KIOSK' =>
                                                        'Tiếp nhận tại kiosk',

                                                    'RECEPTION' =>
                                                        'Tiếp nhận trực tiếp',

                                                    default =>
                                                        $visit?->appointment_id
                                                        ? 'Từ lịch hẹn'
                                                        : (
                                                            $visit?->reception_source
                                                            ?: 'Chưa cập nhật'
                                                        ),
                                                };
                                        @endphp

                                        <article class="patient-queue-card">
                                            <div class="patient-queue-top">
                                                <div>
                                                    <h3 class="queue-number">
                                                        Số thứ tự:
                                                        {{ $queue->queue_number }}
                                                    </h3>

                                                    <p class="queue-visit-code">
                                                        Mã lượt khám:
                                                        {{ $visit?->visit_code ?? 'Chưa cập nhật' }}
                                                    </p>
                                                </div>

                                                <span class="queue-status">
                                                    {{ $queueStatus }}
                                                </span>
                                            </div>

                                            <div class="queue-detail-grid">
                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Loại hàng đợi
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $queueTypeLabel }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Trạng thái lượt khám
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $visitStatus }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Nguồn tiếp nhận
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $receptionLabel }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Bác sĩ
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $doctorName }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Chuyên khoa
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $specialtyName }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Phòng khám
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $roomName }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Vào hàng đợi
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $queue->queued_at?->format('H:i d/m/Y') ?? 'Chưa cập nhật' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Được gọi
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $queue->called_at?->format('H:i d/m/Y') ?? 'Chưa được gọi' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Bắt đầu phục vụ
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $queue->service_started_at?->format('H:i d/m/Y') ?? 'Chưa bắt đầu' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Hoàn tất
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $queue->completed_at?->format('H:i d/m/Y') ?? 'Chưa hoàn tất' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Mã lịch hẹn
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{ $appointment?->appointment_code ?? 'Không có lịch hẹn' }}
                                                    </span>
                                                </div>

                                                <div class="queue-info-item">
                                                    <span class="queue-info-label">
                                                        Ngày hẹn
                                                    </span>

                                                    <span class="queue-info-value">
                                                        {{
                            $appointment
                                ?->appointment_date
                                    ?->format('d/m/Y')
                            ??
                            'Không áp dụng'
                                                                }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="queue-notice">
                                                Trạng thái hàng đợi được cập nhật bởi nhân viên y tế.
                                                Patient Portal không cho phép tự gọi số, bỏ qua lượt,
                                                check-in, bắt đầu phục vụ hoặc thay đổi trạng thái.
                                            </div>
                                        </article>
                        @empty
                            <div class="queue-empty">
                                <h3>
                                    Chưa có thông tin hàng đợi
                                </h3>

                                <p>
                                    Khi bạn được tiếp nhận và đưa vào hàng đợi phòng khám,
                                    thông tin sẽ xuất hiện tại đây.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </div>
@endsection