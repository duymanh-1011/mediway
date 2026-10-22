@extends('admin.layouts.app')
@section('admin-page', 'disease-groups-create')

@section(
    'title',
    'Thêm nhóm bệnh - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Nhóm bệnh</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.disease-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm nhóm bệnh
            </h1>

            <p class="page-description">
                Thêm nhóm bệnh mới vào hệ thống Mediway.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.disease-groups.index'
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.disease-groups.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.disease-groups._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.disease-groups.index'
                    ) }}"
                    class="btn btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu nhóm bệnh
                </button>

            </div>

        </div>

    </form>

@endsection
