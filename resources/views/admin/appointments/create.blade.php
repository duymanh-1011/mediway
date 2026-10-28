@extends('admin.layouts.app')
@section('admin-page', 'appointments-create')

@section(
    'title',
    'Thêm lịch hẹn - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm lịch hẹn</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm lịch hẹn
            </h1>

            <p class="page-description">
                Tạo lịch khám cho bệnh nhân.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.appointments.index'
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>


    <form novalidate
        method="POST"
        action="{{ route(
            'admin.appointments.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.appointments._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.appointments.index'
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
