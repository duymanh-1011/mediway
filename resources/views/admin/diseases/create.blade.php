@extends('admin.layouts.app')
@section('admin-page', 'diseases-create')

@section(
    'title',
    'Thêm bệnh - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Danh mục bệnh</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.disease-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm bệnh
            </h1>

            <p class="page-description">
                Thêm bệnh mới vào danh mục Mediway.
            </p>

        </div>

        <a
            href="{{ $backUrl }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.diseases.store'
        ) }}"
    >

        @csrf

        <div class="card">

            @include(
                'admin.diseases._form'
            )

            <div class="form-actions">

                <a
                    href="{{ $backUrl }}"
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
