@extends('admin.layouts.app')
@section('admin-page', 'doctor-schedules-create')

@section(
    'title',
    'Thêm lịch làm việc bác sĩ - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <span>Lịch làm việc bác sĩ</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm lịch làm việc bác sĩ
            </h1>

            <p class="page-description">
                Phân lịch bác sĩ theo chuyên khoa,
                phòng khám, ngày và khung giờ.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.doctor-schedules.index'
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>


    <form novalidate
        method="POST"
        action="{{ route(
            'admin.doctor-schedules.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.doctor-schedules._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.doctor-schedules.index'
                    ) }}"
                    class="btn btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu lịch làm việc
                </button>

            </div>

        </div>

    </form>

@endsection
