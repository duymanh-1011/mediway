@extends('admin.layouts.app')
@section('admin-page', 'examination-rooms-edit')

@section(
    'title',
    'Sửa phòng khám - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Phòng khám</span>
    <span aria-hidden="true">/</span>
    <span>{{ $examinationRoom->room_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa phòng khám
            </h1>

            <p class="page-description">
                Cập nhật thông tin
                {{ $examinationRoom->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.examination-rooms.show',
                $examinationRoom
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.examination-rooms.update',
            $examinationRoom
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.examination-rooms._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.examination-rooms.show',
                        $examinationRoom
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
