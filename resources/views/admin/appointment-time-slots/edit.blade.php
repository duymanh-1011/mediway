@extends('admin.layouts.app')
@section('admin-page', 'appointment-time-slots-edit')

@section(
    'title',
    'Sửa khung giờ khám - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <span>Lịch làm việc bác sĩ</span>
    <span aria-hidden="true">/</span>
    <span>#{{ $appointmentTimeSlot->doctor_schedule_id }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa khung giờ</strong>
@endsection

@section('content')

    @php
        $doctorSchedule =
            $appointmentTimeSlot->doctorSchedule;
    @endphp


    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa khung giờ khám
            </h1>

            <p class="page-description">
                Cập nhật sức chứa, trạng thái
                hoặc thời gian của khung giờ.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.doctor-schedules.show',
                $doctorSchedule
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>


    @if ($appointmentsCount > 0)

        <div class="warning-box">

            Khung giờ này đã có
            <strong>{{ $appointmentsCount }}</strong>
            lịch hẹn.

            Không được thay đổi giờ bắt đầu/kết thúc
            và không được giảm sức chứa xuống dưới
            {{ $appointmentsCount }}.

        </div>

    @endif


    <form novalidate
        method="POST"
        action="{{ route(
            'admin.appointment-time-slots.update',
            $appointmentTimeSlot
        ) }}"
    >

        @csrf
        @method('PUT')

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
                    class="btn btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu thay đổi
                </button>

            </div>

        </div>

    </form>

@endsection
