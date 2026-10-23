@extends('admin.layouts.app')
@section('admin-page', 'medical-services-create')

@section(
    'title',
    'Thêm dịch vụ y tế - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Dịch vụ y tế</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm dịch vụ y tế
            </h1>

            <p class="page-description">
                Thêm dịch vụ y tế mới vào hệ thống Mediway.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.medical-services.index'
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.medical-services.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.medical-services._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.medical-services.index'
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
