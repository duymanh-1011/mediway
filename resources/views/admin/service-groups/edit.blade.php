@extends('admin.layouts.app')
@section('admin-page', 'service-groups-edit')

@section(
    'title',
    'Sửa nhóm dịch vụ - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Nhóm dịch vụ</span>
    <span aria-hidden="true">/</span>
    <span>@include('admin.layouts.partials.record-code', ['code' => $serviceGroup->service_group_code])</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa nhóm dịch vụ
            </h1>

            <p class="page-description">
                Cập nhật thông tin
                {{ $serviceGroup->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.service-groups.show',
                $serviceGroup
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.service-groups.update',
            $serviceGroup
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.service-groups._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.service-groups.show',
                        $serviceGroup
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
