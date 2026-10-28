@extends('admin.layouts.app')
@section('admin-page', 'appointment-statuses-edit')

@section(
    'title',
    'Sửa trạng thái lịch hẹn - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Cấu hình trạng thái</span>
    <span aria-hidden="true">/</span>
    <span>Trạng thái lịch hẹn</span>
    <span aria-hidden="true">/</span>
    @include('admin.layouts.partials.status-badge', ['badgeCode' => $appointmentStatus->name])
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa trạng thái lịch hẹn
            </h1>

            <p class="page-description">
                Cập nhật cấu hình cho trạng thái
                {{ \App\Models\AdminDisplay::label($appointmentStatus->name) }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.appointment-statuses.show',
                $appointmentStatus
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <div class="warning-box">

        <strong>Lưu ý:</strong>

        Tên trạng thái không thể thay đổi.
        Việc thay đổi thứ tự xử lý,
        khả năng chuyển tiếp hoặc trạng thái hoạt động
        có thể ảnh hưởng đến luồng đặt khám.

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.appointment-statuses.update',
            $appointmentStatus
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.appointment-statuses._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.appointment-statuses.show',
                        $appointmentStatus
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
