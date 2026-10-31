@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/specialties-show.css')
@endpush

@section('title', $specialty->name . ' - Mediway')

@section('content')


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