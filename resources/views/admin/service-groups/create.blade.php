@extends('admin.layouts.app')
@section('admin-page', 'service-groups-create')

@section(
    'title',
    'Thêm nhóm dịch vụ - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Nhóm dịch vụ</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm nhóm dịch vụ
            </h1>

            <p class="page-description">
                Thêm nhóm dịch vụ mới vào hệ thống Mediway.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.service-groups.index'
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.service-groups.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.service-groups._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.service-groups.index'
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
