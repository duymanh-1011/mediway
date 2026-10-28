@extends('portal.layouts.app')

@section('title', 'Lịch sử lịch hẹn - Mediway')

@section('content')
    <style>
        .appointment-history-page {
            background: #f7fafc;
        }

        .appointment-history-page .portal-page-head {
            padding: 40px 0;
            background: #f1f6fd;
            border-bottom: 1px solid #dde8f2;
        }

        .appointment-history-page h1 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: clamp(30px, 4vw, 48px);
        }

        .appointment-history-page .portal-lead {
            color: #64748b;
        }

        .appointment-history-content {
            padding: 36px 0 64px;
        }

        .appointment-history-list {
            display: grid;
            gap: 16px;
        }

        .appointment-history-card {
            padding: 20px;
            background: #fff;
            border: 1px solid #dce6ef;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
        }

        .appointment-history-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
        }

        .appointment-history-code {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
        }

        .appointment-history-date {
            color: #64748b;
            font-size: 14px;
        }

        .appointment-history-status {
            display: inline-flex;
            padding: 6px 11px;
            border-radius: 999px;
            background: #eef7fb;
            color: #0b6b8a;
            font-size: 12px;
            font-weight: 800;
        }

        .appointment-history-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .appointment-history-item {
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .appointment-history-label {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .appointment-history-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
        }

        .appointment-history-actions {
            margin-top: 16px;
        }

        .appointment-history-actions a {
            display: inline-flex;
            padding: 9px 14px;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .appointment-history-actions a:hover {
            background: #1d4ed8;
        }

        .appointment-history-empty {
            padding: 28px;
            background: #fff;
            border: 1px dashed #cbd8e6;
            border-radius: 16px;
            color: #64748b;
            text-align: center;
        }

        .appointment-history-pagination {
            margin-top: 24px;
        }

        @media (max-width: 900px) {
            .appointment-history-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .appointment-history-grid {
                grid-template-columns: 1fr;
            }

            .appointment-history-top {
                flex-direction: column;
            }

            .appointment-history-card {
                padding: 16px;
            }
        }
    </style>

    <div class="appointment-history-page">
        <section class="portal-page-head">
            <div class="portal-shell">
                <p class="portal-eyebrow">
                    PATIENT PORTAL
                </p>

                <h1>
                    Lịch sử lịch hẹn
                </h1>

                <p class="portal-lead">
                    Theo dõi các lịch hẹn đã đặt và trạng thái của riêng bạn.
                </p>
            </div>
        </section>

        <section class="appointment-history-content">
            <div class="portal-shell">
                <div class="appointment-history-list">
                    @forelse ($appointments as $appointment)
                        @php
                            $statusName =
                                $appointment
                                    ->appointmentStatus
                                        ?->name
                                ?? 'UNKNOWN';

                            $statusLabel = match ($statusName) {
                                'BOOKED' => 'Đã đặt',
                                'CHECKED_IN' => 'Đã tiếp nhận',
                                'IN_EXAMINATION' => 'Đang khám',
                                'COMPLETED' => 'Hoàn tất',
                                'CANCELLED' => 'Đã hủy',
                                default => $statusName,
                            };
                        @endphp

                        <article class="appointment-history-card">
                            <div class="appointment-history-top">
                                <div>
                                    <h2 class="appointment-history-code">
                                        {{ $appointment->appointment_code }}
                                    </h2>

                                    <div class="appointment-history-date">
                                        Đặt lúc
                                        {{ $appointment->booked_at?->format('H:i d/m/Y') ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>

                                <span class="appointment-history-status">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="appointment-history-grid">
                                <div class="appointment-history-item">
                                    <span class="appointment-history-label">
                                        Ngày khám
                                    </span>

                                    <span class="appointment-history-value">
                                        {{ $appointment->appointment_date?->format('d/m/Y') ?? 'Chưa cập nhật' }}
                                    </span>
                                </div>

                                <div class="appointment-history-item">
                                    <span class="appointment-history-label">
                                        Khung giờ
                                    </span>

                                    <span class="appointment-history-value">
                                        @if ($appointment->appointmentTimeSlot)
                                            {{ substr((string) $appointment->appointmentTimeSlot->start_time, 0, 5) }}
                                            -
                                            {{ substr((string) $appointment->appointmentTimeSlot->end_time, 0, 5) }}
                                        @else
                                            Chưa cập nhật
                                        @endif
                                    </span>
                                </div>

                                <div class="appointment-history-item">
                                    <span class="appointment-history-label">
                                        Bác sĩ
                                    </span>

                                    <span class="appointment-history-value">
                                        {{ $appointment->doctor?->full_name ?? 'Chưa cập nhật' }}
                                    </span>
                                </div>

                                <div class="appointment-history-item">
                                    <span class="appointment-history-label">
                                        Chuyên khoa
                                    </span>

                                    <span class="appointment-history-value">
                                        {{ $appointment->specialty?->name ?? 'Chưa cập nhật' }}
                                    </span>
                                </div>

                                <div class="appointment-history-item">
                                    <span class="appointment-history-label">
                                        Phòng khám
                                    </span>

                                    <span class="appointment-history-value">
                                        {{ $appointment->examinationRoom?->name ?? 'Chưa cập nhật' }}
                                    </span>
                                </div>
                            </div>

                            <div class="appointment-history-actions">
                                <a href="{{ route('patient.appointments.show', $appointment) }}">
                                    Xem chi tiết
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="appointment-history-empty">
                            Bạn chưa có lịch hẹn nào.
                        </div>
                    @endforelse
                </div>

                @if ($appointments->hasPages())
                    <div class="appointment-history-pagination">
                        {{ $appointments->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection