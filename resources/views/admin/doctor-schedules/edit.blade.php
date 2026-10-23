@extends('admin.layouts.app')
@section('admin-page', 'doctor-schedules-edit')

@section(
    'title',
    'Sửa lịch làm việc bác sĩ - Mediway'
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
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa lịch làm việc bác sĩ
            </h1>

            <p class="page-description">
                Cập nhật lịch làm việc đã được phân công.
            </p>

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
            'admin.doctor-schedules.update',
            $doctorSchedule
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.doctor-schedules._form'
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
                >Cập nhật</button>

            </div>

        </div>

    </form>

@endsection
