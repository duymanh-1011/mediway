@extends('admin.layouts.app')
@section('admin-page', 'specialties-edit')

@section(
    'title',
    'Sửa chuyên khoa - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Chuyên khoa</span>
    <span aria-hidden="true">/</span>
    <span>{{ $specialty->specialty_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa chuyên khoa
            </h1>

            <p class="page-description">
                Cập nhật thông tin chuyên khoa
                {{ $specialty->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.specialties.show',
                $specialty
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.specialties.update',
            $specialty
        ) }}"
        enctype="multipart/form-data"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.specialties._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.specialties.show',
                        $specialty
                    ) }}"
                    class="btn btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu thay đổi
                </button>

            </div>

        </div>

    </form>

@endsection
