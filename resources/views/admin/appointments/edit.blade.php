@extends('admin.layouts.app')
@section('admin-page', 'appointments-edit')

@section(
    'title',
    'Sửa lịch hẹn - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <span>{{ $appointment->appointment_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa lịch hẹn
            </h1>

            <p class="page-description">
                {{ $appointment->appointment_code }}
            </p>

        </div>

        <a
            href="{{ route(
                'admin.appointments.show',
                $appointment
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>


    <form novalidate
        method="POST"
        action="{{ route(
            'admin.appointments.update',
            $appointment
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.appointments._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.appointments.show',
                        $appointment
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
