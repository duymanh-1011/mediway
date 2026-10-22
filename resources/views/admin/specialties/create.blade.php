@extends('admin.layouts.app')
@section('admin-page', 'specialties-create')

@section(
    'title',
    'Thêm chuyên khoa - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Chuyên khoa</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm chuyên khoa
            </h1>

            <p class="page-description">
                Thêm chuyên khoa mới vào danh mục Mediway.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.specialties.index'
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.specialties.store'
        ) }}"
        enctype="multipart/form-data"
    >

        @csrf

        <div class="card">

            @include(
                'admin.specialties._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.specialties.index'
                    ) }}"
                    class="btn btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu chuyên khoa
                </button>

            </div>

        </div>

    </form>

@endsection
