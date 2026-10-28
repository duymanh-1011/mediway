@extends('portal.layouts.app')

@section('title', $doctor->full_name . ' - Mediway')

@section('content')
    <style>
        .doctor-profile-page {
            background: #f7fafc;
            min-height: 100%;
        }

        .doctor-profile-page .portal-page-head {
            background: #f1f6fd;
            padding: 40px 0;
            border-bottom: 1px solid #dde8f2;
        }

        .doctor-profile-back {
            display: inline-flex;
            margin-bottom: 16px;
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        .doctor-profile-page h1 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: clamp(30px, 4vw, 48px);
        }

        .doctor-profile-specialty {
            margin: 0;
            color: #475569;
            font-size: 16px;
        }

        .doctor-profile-content {
            padding: 36px 0 64px;
        }

        .doctor-profile-grid {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        .doctor-profile-card,
        .doctor-profile-schedule {
            background: #fff;
            border: 1px solid #dce6ef;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        .doctor-profile-card h2,
        .doctor-profile-schedule h2 {
            margin: 0 0 14px;
            color: #0f172a;
            font-size: 20px;
        }

        .doctor-profile-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .doctor-profile-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .doctor-profile-date {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e8eef4;
        }

        .doctor-profile-date:first-of-type {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .doctor-profile-date-title {
            margin-bottom: 10px;
            color: #0f172a;
            font-weight: 800;
        }

        .doctor-profile-slot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 8px;
            padding: 12px;
            border: 1px solid #e4edf5;
            border-radius: 12px;
            background: #f8fbff;
        }

        .doctor-profile-slot-time {
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }

        .doctor-profile-slot a {
            padding: 9px 14px;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .doctor-profile-empty {
            color: #64748b;
            line-height: 1.6;
        }

        @media (max-width: 800px) {
            .doctor-profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .doctor-profile-page .portal-page-head {
                padding: 28px 0;
            }

            .doctor-profile-content {
                padding: 24px 0 44px;
            }

            .doctor-profile-card,
            .doctor-profile-schedule {
                padding: 16px;
            }

            .doctor-profile-slot {
                align-items: stretch;
                flex-direction: column;
            }

            .doctor-profile-slot a {
                text-align: center;
            }
        }
    </style>

    <div class="doctor-profile-page">
        <section class="portal-page-head">
            <div class="portal-shell">
                <a href="{{ route('patient.doctors.index') }}" class="doctor-profile-back">
                    ← Quay lại tra cứu
                </a>

                <h1>
                    {{ $doctor->full_name }}
                </h1>

                <p class="doctor-profile-specialty">
                    {{ $doctor->specialty->name }}
                </p>
            </div>
        </section>

        <section class="doctor-profile-content">
            <div class="portal-shell">
                <div class="doctor-profile-grid">
                    <aside class="doctor-profile-card">
                        <h2>
                            Chuyên khoa
                        </h2>

                        <p>
                            <a href="{{ route('patient.specialties.show', $doctor->specialty) }}"
                                class="doctor-profile-link">
                                {{ $doctor->specialty->name }}
                            </a>
                        </p>

                        @if (filled($doctor->specialty->description))
                            <p>
                                {{ $doctor->specialty->description }}
                            </p>
                        @endif

                        @if (filled($doctor->specialty->pre_visit_instructions))
                            <h2>
                                Hướng dẫn trước khám
                            </h2>

                            <p>
                                {{ $doctor->specialty->pre_visit_instructions }}
                            </p>
                        @endif
                    </aside>

                    <div class="doctor-profile-schedule">
                        <h2>
                            Lịch đang còn nhận đặt
                        </h2>

                        @forelse ($schedules as $schedule)
                            <div class="doctor-profile-date">
                                <div class="doctor-profile-date-title">
                                    {{ $schedule->work_date->format('d/m/Y') }}
                                    ·
                                    {{ $schedule->specialty->name }}
                                    ·
                                    {{ $schedule->examinationRoom->name }}
                                </div>

                                @foreach ($schedule->appointmentTimeSlots as $slot)
                                    @php
                                        $remaining = max(
                                            (int) $slot->max_patients
                                            - (int) $slot->booked_count,
                                            0
                                        );
                                    @endphp

                                    <div class="doctor-profile-slot">
                                        <div class="doctor-profile-slot-time">
                                            {{ substr((string) $slot->start_time, 0, 5) }}
                                            -
                                            {{ substr((string) $slot->end_time, 0, 5) }}

                                            · Còn {{ $remaining }} chỗ
                                        </div>

                                        <a href="{{ route('patient.appointments.create', $slot) }}">
                                            Đặt lịch
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <p class="doctor-profile-empty">
                                Bác sĩ hiện chưa có khung giờ còn nhận đặt trong phạm vi lịch mở.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection