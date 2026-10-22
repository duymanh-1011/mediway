@extends('admin.layouts.app')
@section('admin-page', 'examination-rooms-create')

@section(
    'title',
    'Thêm phòng khám - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Phòng khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm phòng khám
            </h1>

            <p class="page-description">
                Thêm phòng khám mới vào hệ thống Mediway.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.examination-rooms.index'
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.examination-rooms.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.examination-rooms._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.examination-rooms.index'
                    ) }}"
                    class="btn btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu phòng khám
                </button>

            </div>

        </div>

    </form>

@endsection
