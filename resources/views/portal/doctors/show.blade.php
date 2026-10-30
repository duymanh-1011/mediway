@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/doctors-show.css')
@endpush

@section('title', $doctor->full_name . ' - Mediway')

@section('content')


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