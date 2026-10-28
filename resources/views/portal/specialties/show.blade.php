@extends('portal.layouts.app')

@section('title', $specialty->name . ' - Mediway')

@section('content')
    <style>
        .specialty-page {
            background: #f7fafc;
        }

        .specialty-page .portal-page-head {
            padding: 40px 0;
            background: #f1f6fd;
            border-bottom: 1px solid #dde8f2;
        }

        .specialty-back {
            display: inline-flex;
            margin-bottom: 14px;
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        .specialty-page h1 {
            margin: 0 0 10px;
            color: #0f172a;
            font-size: clamp(30px, 4vw, 48px);
        }

        .specialty-page-head-description {
            max-width: 800px;
            margin: 0;
            color: #64748b;
            line-height: 1.7;
        }

        .specialty-content {
            padding: 36px 0 64px;
        }

        .specialty-section {
            margin-bottom: 30px;
        }

        .specialty-section h2 {
            margin: 0 0 16px;
            color: #0f172a;
            font-size: 24px;
        }

        .specialty-notice {
            margin-bottom: 28px;
            padding: 18px;
            background: #eef7fb;
            border: 1px solid #d8eaf3;
            border-radius: 14px;
            color: #475569;
            line-height: 1.7;
        }

        .specialty-doctors {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .specialty-doctor-card {
            padding: 18px;
            background: #fff;
            border: 1px solid #dce6ef;
            border-radius: 14px;
        }

        .specialty-doctor-card a {
            color: #0f172a;
            text-decoration: none;
            font-weight: 800;
        }

        .specialty-schedules {
            display: grid;
            gap: 14px;
        }

        .specialty-schedule-card {
            padding: 18px;
            background: #fff;
            border: 1px solid #dce6ef;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
        }

        .specialty-schedule-title {
            margin-bottom: 12px;
            color: #0f172a;
            font-weight: 800;
        }

        .specialty-slot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid #edf2f7;
        }

        .specialty-slot:first-of-type {
            border-top: 0;
        }

        .specialty-slot a {
            padding: 8px 13px;
            border-radius: 9px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .specialty-empty {
            color: #64748b;
        }

        @media (max-width: 850px) {
            .specialty-doctors {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 560px) {
            .specialty-doctors {
                grid-template-columns: 1fr;
            }

            .specialty-slot {
                align-items: stretch;
                flex-direction: column;
            }

            .specialty-slot a {
                text-align: center;
            }
        }
    </style>

    <div class="specialty-page">
        <section class="portal-page-head">
            <div class="portal-shell">
                <a href="{{ route('patient.doctors.index') }}" class="specialty-back">
                    ← Quay lại tra cứu
                </a>

                <h1>
                    {{ $specialty->name }}
                </h1>

                @if (filled($specialty->description))
                    <p class="specialty-page-head-description">
                        {{ $specialty->description }}
                    </p>
                @endif
            </div>
        </section>

        <section class="specialty-content">
            <div class="portal-shell">
                @if (filled($specialty->pre_visit_instructions))
                    <div class="specialty-notice">
                        <strong>
                            Hướng dẫn trước khám:
                        </strong>

                        {{ $specialty->pre_visit_instructions }}
                    </div>
                @endif

                <div class="specialty-section">
                    <h2>
                        Bác sĩ
                    </h2>

                    @if ($doctors->isNotEmpty())
                        <div class="specialty-doctors">
                            @foreach ($doctors as $doctor)
                                <div class="specialty-doctor-card">
                                    <a href="{{ route('patient.doctors.show', $doctor) }}">
                                        {{ $doctor->full_name }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="specialty-empty">
                            Hiện chưa có bác sĩ đang hoạt động cho chuyên khoa này.
                        </p>
                    @endif
                </div>

                <div class="specialty-section">
                    <h2>
                        Lịch còn nhận đặt
                    </h2>

                    @if ($schedules->isNotEmpty())
                        <div class="specialty-schedules">
                            @foreach ($schedules as $schedule)
                                <div class="specialty-schedule-card">
                                    <div class="specialty-schedule-title">
                                        <a href="{{ route('patient.doctors.show', $schedule->doctor) }}">
                                            {{ $schedule->doctor->full_name }}
                                        </a>

                                        · {{ $schedule->work_date->format('d/m/Y') }}
                                        · {{ $schedule->examinationRoom->name }}
                                    </div>

                                    @foreach ($schedule->appointmentTimeSlots as $slot)
                                        @php
                                            $remaining = max(
                                                (int) $slot->max_patients
                                                - (int) $slot->booked_count,
                                                0
                                            );
                                        @endphp

                                        <div class="specialty-slot">
                                            <div>
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
                            @endforeach
                        </div>
                    @else
                        <p class="specialty-empty">
                            Chuyên khoa hiện chưa có lịch còn nhận đặt.
                        </p>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection