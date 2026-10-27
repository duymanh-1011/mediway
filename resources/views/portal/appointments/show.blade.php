@extends('portal.layouts.app')

@section('title', 'Chi tiết lịch hẹn - Mediway')

@section('content')
    <section class="portal-page-head">
        <div class="portal-shell">
            <p class="portal-eyebrow">
                LỊCH HẸN CỦA BẠN
            </p>

            <h1>
                Chi tiết lịch hẹn
            </h1>

            <p class="portal-lead">
                Theo dõi thông tin lịch khám đã đặt.
            </p>
        </div>
    </section>

    <section class="portal-section portal-section--tint">
        <div class="portal-shell">



            <div class="portal-grid portal-grid--3">

                <article class="portal-card">
                    <p class="portal-eyebrow">
                        MÃ LỊCH HẸN
                    </p>

                    <h3>
                        {{ $appointment->appointment_code }}
                    </h3>

                    <p>
                        <strong>Trạng thái:</strong>

                        <x-portal.badge type="info">
                            {{ $appointment->appointmentStatus->description ?? $appointment->appointmentStatus->name ?? '—' }}
                        </x-portal.badge>
                    </p>

                    <p>
                        <strong>Đặt lúc:</strong>
                        {{ $appointment->booked_at?->format('d/m/Y H:i') ?? '—' }}
                    </p>
                </article>

                <article class="portal-card">
                    <p class="portal-eyebrow">
                        BÁC SĨ
                    </p>

                    <h3>
                        {{ $appointment->doctor->full_name }}
                    </h3>

                    <p>
                        <strong>Chuyên khoa:</strong>
                        {{ $appointment->specialty->name }}
                    </p>

                    <p>
                        <strong>Phòng khám:</strong>
                        {{ $appointment->examinationRoom->name }}
                    </p>
                </article>

                <article class="portal-card">
                    <p class="portal-eyebrow">
                        THỜI GIAN
                    </p>

                    <h3>
                        {{ $appointment->appointment_date->format('d/m/Y') }}
                    </h3>

                    <p>
                        <strong>Khung giờ:</strong>
                        {{ substr($appointment->appointmentTimeSlot->start_time, 0, 5) }}
                        -
                        {{ substr($appointment->appointmentTimeSlot->end_time, 0, 5) }}
                    </p>
                </article>

            </div>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-shell">

            <div class="portal-card">

                <div class="portal-section__heading">
                    <p class="portal-eyebrow">
                        THÔNG TIN KHÁM
                    </p>

                    <h2>
                        Nội dung lịch hẹn
                    </h2>
                </div>

                <p>
                    <strong>Lý do khám:</strong>
                </p>

                <p>
                    {{ $appointment->reason_for_visit }}
                </p>

                <p>
                    <strong>Ghi chú:</strong>
                </p>

                <p>
                    {{ $appointment->patient_notes ?: 'Không có ghi chú.' }}
                </p>

                <div class="portal-actions">

                    <x-portal.button :href="route('patient.doctors.index')" variant="secondary">
                        Tra cứu lịch khác
                    </x-portal.button>

                </div>

            </div>
        </div>
    </section>
@endsection