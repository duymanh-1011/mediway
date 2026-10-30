
@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/appointments-index.css')
@endpush

@section('title', 'Lịch sử lịch hẹn - Mediway')

@section('content')
    @php
        $totalAppointments = method_exists($appointments, 'total')
            ? $appointments->total()
            : $appointments->count();

        $graceMinutes = max(
            0,
            min(
                1440,
                (int) config(
                    'appointments_auto_cancel.grace_minutes',
                    30
                )
            )
        );
    @endphp



    <div class="ah-page">

        <section class="portal-page-head">
            <div class="portal-shell">
                <p class="portal-eyebrow">
                    LỊCH KHÁM CỦA BẠN
                </p>

                <h1>Lịch sử lịch hẹn</h1>

                <p class="portal-lead">
                    Theo dõi lịch khám, bác sĩ,
                    thời gian và trạng thái lịch hẹn.
                </p>
            </div>
        </section>

        <section class="ah-content">
            <div class="portal-shell">

                <div class="ah-heading">
                    <div>
                        <h2>Danh sách lịch hẹn</h2>
                        <p>
                            Các lịch hẹn thuộc tài khoản bệnh nhân
                            đang đăng nhập.
                        </p>
                    </div>

                    <span class="ah-count">
                        {{ $totalAppointments }} lịch hẹn
                    </span>
                </div>

                <div class="ah-list">

                    @forelse ($appointments as $appointment)
                                    @php
                                        $statusName = strtoupper(
                                            (string) (
                                                $appointment->appointmentStatus?->name
                                                ?? 'UNKNOWN'
                                            )
                                        );

                                        $statusLabel = match ($statusName) {
                                            'BOOKED' => 'Đã đặt lịch',
                                            'CHECKED_IN' => 'Đã tiếp nhận',
                                            'IN_EXAMINATION', 'IN_PROGRESS' => 'Đang khám',
                                            'COMPLETED' => 'Đã hoàn tất',
                                            'CANCELLED' => 'Đã hủy',
                                            default =>
                                                $appointment->appointmentStatus?->description
                                                ?? 'Chưa xác định',
                                        };

                                        $tone = match ($statusName) {
                                            'BOOKED' => 'booked',
                                            'CHECKED_IN',
                                            'IN_EXAMINATION',
                                            'IN_PROGRESS' => 'progress',
                                            'COMPLETED' => 'done',
                                            'CANCELLED' => 'cancel',
                                            default => 'other',
                                        };

                                        $slot = $appointment->appointmentTimeSlot;

                                        $isAutoNoShow =
                                            $statusName === 'CANCELLED'
                                            && str_starts_with(
                                                (string) $appointment->cancellation_reason,
                                                '[AUTO_NO_SHOW]'
                                            );

                                        $startsAt = null;
                                        $endsAt = null;

                                        if ($appointment->appointment_date && $slot) {
                                            $dateValue = $appointment
                                                ->appointment_date
                                                ->format('Y-m-d');

                                            $timezone = config(
                                                'app.timezone',
                                                'Asia/Ho_Chi_Minh'
                                            );

                                            $startsAt =
                                                \Illuminate\Support\Carbon::parse(
                                                    $dateValue . ' ' .
                                                    substr((string) $slot->start_time, 0, 8),
                                                    $timezone
                                                );

                                            $endsAt =
                                                \Illuminate\Support\Carbon::parse(
                                                    $dateValue . ' ' .
                                                    substr((string) $slot->end_time, 0, 8),
                                                    $timezone
                                                );
                                        }

                                        $hasMedicalVisit =
                                            $statusName === 'BOOKED'
                                            && $appointment->medicalVisit()->exists();

                                        $isOverduePending =
                                            $statusName === 'BOOKED'
                                            && !$hasMedicalVisit
                                            && $endsAt !== null
                                            && $endsAt->copy()
                                                ->addMinutes($graceMinutes)
                                                ->lessThanOrEqualTo(now());

                                        $hasStarted =
                                            $statusName === 'BOOKED'
                                            && $startsAt !== null
                                            && $startsAt->lessThanOrEqualTo(now());

                                        $canRequestChange =
                                            $statusName === 'BOOKED'
                                            && !$hasStarted
                                            && !$hasMedicalVisit;

                                        if ($isAutoNoShow) {
                                            $statusLabel = 'Tự động hủy · Vắng khám';
                                        } elseif ($isOverduePending) {
                                            $statusLabel = 'Quá hạn · Chờ xử lý';
                                            $tone = 'overdue';
                                        }

                                        $autoCancelReason = $isAutoNoShow
                                            ? trim(substr(
                                                (string) $appointment->cancellation_reason,
                                                strlen('[AUTO_NO_SHOW]')
                                            ))
                                            : null;
                                    @endphp

                                    <article class="ah-card">

                                        <div class="ah-card-head">
                                            <div>
                                                <span class="ah-kicker">
                                                    Mã lịch hẹn
                                                </span>

                                                <h3 class="ah-code">
                                                    {{ $appointment->appointment_code }}
                                                </h3>

                                                <p class="ah-booked">
                                                    Đặt lúc
                                                    {{
                        $appointment->booked_at
                                ?->format('H:i d/m/Y')
                        ?? 'Chưa cập nhật'
                                                    }}
                                                </p>
                                            </div>

                                            <span
                                                class="ah-status ah-status--{{ $tone }}"
                                            >
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <div class="ah-card-body">

                                            <div class="ah-doctor">
                                                <span class="ah-avatar" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         stroke-width="1.8"
                                                         stroke-linecap="round"
                                                         stroke-linejoin="round">
                                                        <circle cx="12" cy="7" r="4"/>
                                                        <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                                                    </svg>
                                                </span>

                                                <div class="ah-doctor-content">
                                                    <span class="ah-kicker">
                                                        Bác sĩ phụ trách
                                                    </span>

                                                    <strong class="ah-doctor-name">
                                                        {{
                        $appointment->doctor?->full_name
                        ?? 'Chưa cập nhật'
                                                        }}
                                                    </strong>

                                                    <p class="ah-doctor-line">
                                                        <strong>Chuyên khoa:</strong>
                                                        {{
                        $appointment->specialty?->name
                        ?? 'Chưa cập nhật'
                                                        }}
                                                    </p>

                                                    <p class="ah-doctor-line">
                                                        <strong>Phòng khám:</strong>
                                                        {{
                        $appointment->examinationRoom?->name
                        ?? 'Chưa cập nhật'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="ah-schedule">

                                                <div class="ah-visit-item">
                                                    <svg viewBox="0 0 24 24"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         stroke-width="1.8"
                                                         stroke-linecap="round">
                                                        <rect x="3" y="5"
                                                              width="18" height="16"
                                                              rx="2"/>
                                                        <path d="M7 3v4m10-4v4M3 10h18"/>
                                                    </svg>

                                                    <span class="ah-visit-label">
                                                        Ngày khám
                                                    </span>

                                                    <strong class="ah-visit-value">
                                                        {{
                        $appointment->appointment_date
                                ?->format('d/m/Y')
                        ?? 'Chưa cập nhật'
                                                        }}
                                                    </strong>
                                                </div>

                                                <div class="ah-visit-item">
                                                    <svg viewBox="0 0 24 24"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         stroke-width="1.8"
                                                         stroke-linecap="round">
                                                        <circle cx="12" cy="12" r="9"/>
                                                        <path d="M12 7v5l3 2"/>
                                                    </svg>

                                                    <span class="ah-visit-label">
                                                        Khung giờ
                                                    </span>

                                                    <strong class="ah-visit-value">
                                                        @if ($slot)
                                                                                            {{
                                                            substr(
                                                                (string) $slot->start_time,
                                                                0,
                                                                5
                                                            )
                                                                                            }}
                                                                                            –
                                                                                            {{
                                                            substr(
                                                                (string) $slot->end_time,
                                                                0,
                                                                5
                                                            )
                                                                                            }}
                                                        @else
                                                            Chưa cập nhật
                                                        @endif
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- THÔNG BÁO TỰ ĐỘNG HỦY --}}
                                        @if ($isAutoNoShow)
                                                            <div
                                                                class="ah-notice ah-notice--auto"
                                                                role="status"
                                                            >
                                                                <strong>
                                                                    Lịch đã được hệ thống tự động hủy
                                                                    do vắng khám
                                                                </strong>

                                                                <div>
                                                                    Thời gian hủy:
                                                                    {{
                                            $appointment->cancelled_at
                                                    ?->format('H:i d/m/Y')
                                            ?? 'Chưa ghi nhận'
                                                                    }}
                                                                </div>

                                                                @if (filled($autoCancelReason))
                                                                    <div>
                                                                        Lý do: {{ $autoCancelReason }}
                                                                    </div>
                                                                @endif

                                                                <div>
                                                                    Lịch sử được giữ lại để bệnh nhân
                                                                    và bệnh viện đối chiếu.
                                                                </div>
                                                            </div>

                                        @elseif ($isOverduePending)

                                            <div
                                                class="ah-notice ah-notice--overdue"
                                                role="status"
                                            >
                                                <strong>Lịch khám đã quá hạn</strong>

                                                Lịch đã qua giờ kết thúc và thời gian
                                                chờ {{ $graceMinutes }} phút nhưng chưa
                                                được hệ thống xử lý hủy tự động.

                                                <div>
                                                    Nếu bạn đã tới bệnh viện và được
                                                    tiếp nhận, vui lòng liên hệ quầy
                                                    tiếp nhận để kiểm tra lại.
                                                </div>
                                            </div>
                                        @endif

                                        <div class="ah-card-footer">
                                            <p class="ah-hint">
                                                @if ($isAutoNoShow)
                                                    Lịch vắng khám đã tự động hủy.
                                                    Bạn có thể đặt lịch khám mới.

                                                @elseif ($statusName === 'CANCELLED')
                                                    Lịch đã hủy, không thể tiếp tục
                                                    hủy hoặc đổi lịch.

                                                @elseif ($isOverduePending)
                                                    Đang chờ hệ thống xử lý lịch quá hạn.

                                                @elseif ($hasStarted)
                                                    Giờ khám đã bắt đầu.
                                                    Không thể tự đổi hoặc hủy lịch này.

                                                @elseif ($statusName === 'COMPLETED')
                                                    Lượt khám đã hoàn tất.

                                                @elseif ($canRequestChange)
                                                    Có thể yêu cầu hủy hoặc đổi lịch
                                                    nếu đáp ứng chính sách của bệnh viện.

                                                @else
                                                    Xem chi tiết để theo dõi
                                                    trạng thái lịch khám.
                                                @endif
                                            </p>

                                            <div class="ah-actions">

                                                <a
                                                    class="ah-btn ah-btn--primary"
                                                    href="{{ route('patient.appointments.show', $appointment) }}"
                                                >
                                                    Xem chi tiết
                                                </a>

                                                @if ($canRequestChange)
                                                    <a
                                                        class="ah-btn"
                                                        href="{{ route('patient.appointments.reschedule.form', $appointment) }}"
                                                    >
                                                        Đổi lịch
                                                    </a>

                                                    <a
                                                        class="ah-btn ah-btn--danger"
                                                        href="{{ route('patient.appointments.cancel.form', $appointment) }}"
                                                    >
                                                        Hủy lịch
                                                    </a>
                                                @endif

                                            </div>
                                        </div>
                                    </article>

                    @empty

                        <div class="ah-empty">
                            <h3>Bạn chưa có lịch hẹn nào</h3>

                            <p>
                                Hãy tra cứu bác sĩ và chọn khung giờ
                                phù hợp để đặt lịch khám.
                            </p>

                            <div style="margin-top:18px;">
                                <a
                                    class="ah-btn ah-btn--primary"
                                    href="{{ route('patient.doctors.index') }}"
                                >
                                    Tra cứu bác sĩ
                                </a>
                            </div>
                        </div>

                    @endforelse
                </div>

                {{-- PHÂN TRANG --}}
                @if (
                        $appointments instanceof \Illuminate\Pagination\LengthAwarePaginator
                        && $appointments->hasPages()
                    )
                        @php
                            $pager = $appointments->withQueryString();

                            $current = $pager->currentPage();
                            $last = $pager->lastPage();

                            $firstVisible = max(1, $current - 2);
                            $lastVisible = min($last, $current + 2);
                        @endphp

                        <div class="ah-pagination">
                            <div class="ah-pagination-info">
                                <strong>
                                    Trang {{ $current }} / {{ $last }}
                                </strong>

                                <span>
                                    Hiển thị
                                    {{ $pager->firstItem() ?? 0 }}
                                    –
                                    {{ $pager->lastItem() ?? 0 }}
                                    trên {{ $pager->total() }} lịch hẹn
                                </span>
                            </div>

                            <nav
                                class="ah-pages"
                                aria-label="Phân trang lịch hẹn"
                            >
                                @if ($pager->onFirstPage())
                                    <span
                                        class="ah-page-link ah-page-link--disabled"
                                        aria-disabled="true"
                                    >
                                        ‹ Trước
                                    </span>
                                @else
                                    <a
                                        class="ah-page-link"
                                        rel="prev"
                                        href="{{ $pager->previousPageUrl() }}"
                                    >
                                        ‹ Trước
                                    </a>
                                @endif

                                @if ($firstVisible > 1)
                                    <a
                                        class="ah-page-link"
                                        href="{{ $pager->url(1) }}"
                                    >
                                        1
                                    </a>

                                    @if ($firstVisible > 2)
                                        <span
                                            class="ah-dots"
                                            aria-hidden="true"
                                        >
                                            …
                                        </span>
                                    @endif
                                @endif

                                @for (
                                        $page = $firstVisible;
                                        $page <= $lastVisible;
                                        $page++
                                    )
                                        @if ($page === $current)
                                            <span
                                                class="ah-page-link ah-page-link--active"
                                                aria-current="page"
                                            >
                                                {{ $page }}
                                            </span>
                                        @else
                                            <a
                                                class="ah-page-link"
                                                href="{{ $pager->url($page) }}"
                                            >
                                                {{ $page }}
                                            </a>
                                        @endif
                                @endfor

                                @if ($lastVisible < $last)
                                    @if ($lastVisible < $last - 1)
                                        <span
                                            class="ah-dots"
                                            aria-hidden="true"
                                        >
                                            …
                                        </span>
                                    @endif

                                    <a
                                        class="ah-page-link"
                                        href="{{ $pager->url($last) }}"
                                    >
                                        {{ $last }}
                                    </a>
                                @endif

                                @if ($pager->hasMorePages())
                                    <a
                                        class="ah-page-link"
                                        rel="next"
                                        href="{{ $pager->nextPageUrl() }}"
                                    >
                                        Sau ›
                                    </a>
                                @else
                                    <span
                                        class="ah-page-link ah-page-link--disabled"
                                        aria-disabled="true"
                                    >
                                        Sau ›
                                    </span>
                                @endif
                            </nav>
                        </div>

                @elseif (
                        method_exists($appointments, 'hasPages')
                        && $appointments->hasPages()
                    )
                        <div class="ah-pagination">
                            {{ $appointments->links() }}
                        </div>
                @endif

            </div>
        </section>
    </div>
@endsection
