@extends('admin.layouts.app')
@section('admin-page', 'medical-services-edit')

@section(
    'title',
    'Sửa dịch vụ y tế - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Dịch vụ y tế</span>
    <span aria-hidden="true">/</span>
    <span>{{ $medicalService->service_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa dịch vụ y tế
            </h1>

            <p class="page-description">
                Cập nhật thông tin
                {{ $medicalService->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.medical-services.show',
                $medicalService
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.medical-services.update',
            $medicalService
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.medical-services._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.medical-services.show',
                        $medicalService
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
