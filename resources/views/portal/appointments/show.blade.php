@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/appointments-show.css')
@endpush

@section('title', 'Phiếu hẹn khám - Mediway')

@section('content')
    @php
        // Controller đã kiểm tra quyền sở hữu lịch hẹn.
        $appointment->loadMissing([
            'patient.detail',
            'patient.user',
            'doctor',
            'specialty',
            'examinationRoom',
            'appointmentTimeSlot.doctorSchedule',
            'appointmentStatus',
        ]);

        $patient = $appointment->patient;
        $detail = $patient?->detail;
        $doctor = $appointment->doctor;
        $room = $appointment->examinationRoom;
        $specialty = $appointment->specialty;
        $slot = $appointment->appointmentTimeSlot;
        $status = $appointment->appointmentStatus;

        $statusCode = strtoupper(
            trim((string) ($status?->name ?? ''))
        );

        $statusLabel = match ($statusCode) {
            'BOOKED' => 'Đã đặt lịch',
            'CHECKED_IN' => 'Đã tiếp nhận',
            'IN_EXAMINATION' => 'Đang khám',
            'COMPLETED' => 'Đã hoàn thành',
            'CANCELLED' => 'Đã hủy',
            default => $status?->description
                ?: ($status?->name ?: 'Chưa cập nhật'),
        };

        $statusTone = match ($statusCode) {
            'BOOKED' => 'booked',
            'CHECKED_IN', 'IN_EXAMINATION' => 'ongoing',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
            default => 'unknown',
        };

        $isCancelled = $statusCode === 'CANCELLED';
        $isBooked = $statusCode === 'BOOKED';

        $formatDate = static function ($value, string $format): string {
            if (blank($value)) {
                return 'Chưa cập nhật';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format($format);
            } catch (\Throwable $e) {
                return 'Chưa cập nhật';
            }
        };

        $startTime = $slot?->start_time
            ? substr((string) $slot->start_time, 0, 5)
            : null;

        $endTime = $slot?->end_time
            ? substr((string) $slot->end_time, 0, 5)
            : null;

        $timeLabel = $startTime && $endTime
            ? "$startTime – $endTime"
            : 'Chưa cập nhật';

        // Giới tính bệnh nhân
        $gender = mb_strtoupper(
            trim((string) ($patient?->gender ?? '')),
            'UTF-8'
        );

        $genderLabel = match ($gender) {
            'MALE', 'M', 'NAM' => 'Nam',
            'FEMALE', 'F', 'NỮ', 'NU' => 'Nữ',
            'OTHER', 'KHÁC', 'KHAC' => 'Khác',
            default => 'Chưa cập nhật',
        };

        // Che bớt số điện thoại để bảo vệ thông tin.
        $phone = preg_replace(
            '/\D+/',
            '',
            (string) ($patient?->user?->phone ?? '')
        );

        $maskedPhone = strlen($phone) >= 7
            ? substr($phone, 0, 3)
            . ' ••• •'
            . substr($phone, -3)
            : 'Chưa cập nhật';

        // Địa chỉ lấy từ hồ sơ bệnh nhân.
        $currentAddress = trim(
            (string) ($detail?->current_address ?? '')
        );

        $permanentAddress = trim(
            (string) ($detail?->permanent_address ?? '')
        );

        if ($currentAddress !== '') {
            $patientAddress = $currentAddress;
            $addressLabel = 'Địa chỉ hiện tại';
        } elseif ($permanentAddress !== '') {
            $patientAddress = $permanentAddress;
            $addressLabel = 'Địa chỉ thường trú';
        } else {
            $patientAddress = 'Chưa cập nhật';
            $addressLabel = 'Địa chỉ';
        }

        // Giữ nguyên chính sách hủy / đổi lịch từ backend.
        $canCancel = false;
        $canReschedule = false;

        if (
            $statusCode === 'BOOKED'
            && $appointment->cancelled_at === null
        ) {
            $policy = app(
                \App\Support\Portal\AppointmentChangePolicy::class
            );

            $canCancel = $policy->problem(
                $appointment,
                'cancel'
            ) === null;

            $canReschedule = $policy->problem(
                $appointment,
                'reschedule'
            ) === null;
        }
    @endphp



    <div class="mw-paper-page">
        <div class="mw-paper-wrap">

            <a class="mw-paper-back" href="{{ route('patient.appointments.index') }}">
                ← Quay lại lịch hẹn
            </a>

            <article class="mw-paper" aria-label="Phiếu hẹn khám">

                {{-- HEADER --}}
                <header class="mw-paper-head">

                    <p class="mw-paper-brand">
                        MEDIWAY · LỊCH KHÁM
                    </p>

                    <h1>Phiếu hẹn khám bệnh</h1>

                    <p>
                        Thông tin lịch khám đã đăng ký của bạn
                    </p>

                </header>

                <div class="mw-paper-body">

                    {{-- MA LICH HEN --}}
                    <div class="mw-paper-topline">

                        <div>
                            <span class="mw-paper-label">
                                Mã lịch hẹn
                            </span>

                            <div class="mw-paper-code">
                                {{ $appointment->appointment_code }}
                            </div>
                        </div>

                        <span class="mw-paper-status {{ $statusTone }}" role="status">
                            {{ $statusLabel }}
                        </span>

                    </div>

                    {{-- ===================================
                    THONG TIN BENH NHAN
                    =================================== --}}
                    <section class="mw-patient-panel" aria-labelledby="mw-patient-title">

                        <h2 class="mw-patient-title" id="mw-patient-title">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 21v-2a8 8 0 0 1 16 0v2" />
                            </svg>

                            Thông tin bệnh nhân
                        </h2>

                        <div class="mw-patient-facts">

                            <div class="mw-fact">
                                <small>Họ và tên</small>

                                <strong>
                                    {{
        $patient?->full_name
        ?: 'Chưa cập nhật'
                                    }}
                                </strong>
                            </div>

                            <div class="mw-fact">
                                <small>Mã bệnh nhân</small>

                                <strong>
                                    {{
        $patient?->patient_code
        ?: 'Chưa cập nhật'
                                    }}
                                </strong>
                            </div>

                            <div class="mw-fact">
                                <small>Ngày sinh</small>

                                <strong>
                                    {{
        $formatDate(
            $patient?->date_of_birth,
            'd/m/Y'
        )
                                    }}
                                </strong>
                            </div>

                            <div class="mw-fact">
                                <small>Giới tính</small>

                                <strong>
                                    {{ $genderLabel }}
                                </strong>
                            </div>

                            <div class="mw-fact">
                                <small>Số điện thoại</small>

                                <strong>
                                    {{ $maskedPhone }}
                                </strong>
                            </div>

                            {{-- DIA CHI BENH NHAN --}}
                            <div class="mw-fact mw-patient-address">

                                <small>
                                    {{ $addressLabel }}
                                </small>

                                <strong>
                                    {{ $patientAddress }}
                                </strong>

                            </div>

                        </div>
                    </section>

                    {{-- ===================================
                    THONG TIN BAC SI
                    =================================== --}}
                    <section class="mw-doctor-line" aria-label="Bác sĩ khám">

                        <div class="mw-doctor-avatar" aria-hidden="true">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="7" r="4" />
                                <path d="M4 21v-2a8 8 0 0 1 16 0v2" />
                            </svg>

                        </div>

                        <div>
                            <h2>
                                BS. {{
        $doctor?->full_name
        ?? 'Chưa cập nhật'
                                }}
                            </h2>

                            <p>
                                Chuyên khoa:
                                {{
        $specialty?->name
        ?? 'Chưa cập nhật'
                                }}
                            </p>
                        </div>
                    </section>

                    {{-- ===================================
                    PHONG KHAM
                    =================================== --}}
                    <section class="mw-room" aria-label="Địa điểm khám">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>

                        <div>
                            <strong>
                                {{
        $room?->name
        ?? 'Chưa có phòng khám'
                                }}
                            </strong>

                            <span>
                                Vui lòng kiểm tra địa điểm khám
                                khi đến bệnh viện.
                            </span>
                        </div>
                    </section>

                    {{-- ===================================
                    NGAY VA GIO KHAM
                    =================================== --}}
                    <section class="mw-visit-facts" aria-label="Ngày và giờ khám">

                        <div class="mw-visit-fact">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2" />
                                <path d="M7 3v4M17 3v4M3 10h18" />
                            </svg>

                            <div>
                                <small>Ngày khám</small>

                                <strong>
                                    {{
        $formatDate(
            $appointment->appointment_date,
            'd/m/Y'
        )
                                    }}
                                </strong>
                            </div>
                        </div>

                        <div class="mw-visit-fact">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 7v5l4 3" />
                            </svg>

                            <div>
                                <small>Giờ khám</small>

                                <strong>
                                    {{ $timeLabel }}
                                </strong>
                            </div>
                        </div>

                    </section>

                    {{-- ===================================
                    LY DO KHAM / TRIEU CHUNG
                    =================================== --}}
                    <section class="mw-reason" aria-labelledby="mw-reason-title">

                        <h2 id="mw-reason-title">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2" />
                                <path d="M12 8v10M7 13h10" />
                            </svg>

                            Lý do khám / Triệu chứng
                        </h2>

                        <p>
                            {{
        $appointment->reason_for_visit
        ?: 'Chưa cập nhật lý do khám.'
                            }}
                        </p>

                    </section>

                    {{-- ===================================
                    GHI CHU VA THOI DIEM DAT
                    =================================== --}}
                    <div class="mw-paper-notes">

                        <p>
                            <strong>Ghi chú:</strong>

                            {{
        $appointment->patient_notes
        ?: 'Không có ghi chú.'
                            }}
                        </p>

                        <p>
                            <strong>Đặt lịch lúc:</strong>

                            {{
        $formatDate(
            $appointment->booked_at,
            'H:i d/m/Y'
        )
                            }}
                        </p>

                    </div>

                    {{-- ===================================
                    TRANG THAI HUY LICH
                    =================================== --}}
                    @if ($isCancelled)

                        <div class="mw-cancel-box" role="status">

                            <h3>
                                Lịch khám này đã được hủy
                            </h3>

                            <p>
                                Bạn không thể hủy hoặc đổi lại
                                lịch hẹn đã hủy.
                            </p>

                            @if ($appointment->cancelled_at)
                                        <p>
                                            <strong>Hủy lúc:</strong>

                                            {{
                                $formatDate(
                                    $appointment->cancelled_at,
                                    'H:i d/m/Y'
                                )
                                                    }}
                                        </p>
                            @endif

                            @if (filled($appointment->cancellation_reason))
                                        <p>
                                            <strong>Lý do hủy:</strong>

                                            {{
                                $appointment->cancellation_reason
                                                    }}
                                        </p>
                            @endif

                        </div>

                    @elseif (!$canCancel && !$canReschedule)

                        <div class="mw-info-box" role="status">

                            <p>
                                Lịch hẹn hiện không đủ điều kiện
                                hủy hoặc đổi. Nếu cần hỗ trợ,
                                vui lòng liên hệ bệnh viện.
                            </p>

                        </div>

                    @endif

                    {{-- ===================================
                    CAC NUT CHUC NANG
                    =================================== --}}
                    <nav class="mw-paper-actions" aria-label="Thao tác lịch hẹn">

                        @if ($isBooked)

                            @if ($canReschedule)

                                <a class="mw-paper-button primary main"
                                    href="{{ route('patient.appointments.reschedule.form', $appointment) }}">
                                    Đổi lịch khám
                                </a>

                            @else

                                <span class="mw-paper-button disabled main" aria-disabled="true">
                                    Đổi lịch khám
                                </span>

                            @endif

                            @if ($canCancel)

                                <a class="mw-paper-button danger main"
                                    href="{{ route('patient.appointments.cancel.form', $appointment) }}">
                                    Hủy lịch khám
                                </a>

                            @else

                                <span class="mw-paper-button disabled main" aria-disabled="true">
                                    Hủy lịch khám
                                </span>

                            @endif

                        @endif

                        @if ($isCancelled)

                            <a class="mw-paper-button primary full" href="{{ route('patient.doctors.index') }}">
                                Đặt lịch khám mới
                            </a>

                        @else

                            <a class="mw-paper-button full" href="{{ route('patient.doctors.index') }}">
                                Tra cứu lịch khám khác
                            </a>

                        @endif

                        <a class="mw-paper-button full" href="{{ route('patient.appointments.index') }}">
                            Lịch sử lịch hẹn
                        </a>

                    </nav>

                </div>

                <footer class="mw-paper-footer">
                    MEDIWAY · Hệ thống quản lý lịch hẹn khám bệnh
                </footer>

            </article>
        </div>
    </div>
@endsection