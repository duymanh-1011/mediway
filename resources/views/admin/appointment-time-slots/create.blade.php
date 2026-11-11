@extends('admin.layouts.app')
@section('admin-page', 'appointment-time-slots-create')

@section(
    'title',
    'Thêm khung giờ khám - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <span>Lịch làm việc bác sĩ</span>
    <span aria-hidden="true">/</span>
    <span>#{{ $doctorSchedule->id }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm khung giờ</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm khung giờ khám
            </h1>

            <p class="page-description">
                Tạo khung giờ 30 phút nằm trong lịch làm việc của bác sĩ.
            </p>
            @php
                // Lịch cũ có thể không chia hết thành các khoảng 30 phút.
                $minutes = (int) \Carbon\Carbon::parse($doctorSchedule->start_time)->diffInMinutes(\Carbon\Carbon::parse($doctorSchedule->end_time));
                $remainingMinutes = $minutes % 30;
            @endphp
            @if ($remainingMinutes > 0)
                <p>Thời gian làm việc còn lẻ {{ $remainingMinutes }} phút, không đủ để tạo thêm một khung 30 phút.</p>
            @endif

        </div>

        <a
            href="{{ route(
                'admin.doctor-schedules.show',
                $doctorSchedule
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>


    <form novalidate
        method="POST"
        action="{{ route(
            'admin.doctor-schedules.time-slots.store',
            $doctorSchedule
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.appointment-time-slots._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.doctor-schedules.show',
                        $doctorSchedule
                    ) }}"
                    class="admin-btn admin-btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="admin-btn admin-btn-primary"
                >Lưu</button>

            </div>

        </div>

    </form>

@endsection
