@extends('admin.layouts.app')
@section('admin-page', 'diseases-edit')

@section(
    'title',
    'Sửa bệnh - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Danh mục bệnh</span>
    <span aria-hidden="true">/</span>
    <span>@include('admin.layouts.partials.record-code', ['code' => $disease->disease_code])</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.disease-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa bệnh
            </h1>

            <p class="page-description">
                Cập nhật thông tin
                {{ $disease->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.diseases.show',
                $disease
            ) }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.diseases.update',
            $disease
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include(
                'admin.diseases._form'
            )

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.diseases.show',
                        $disease
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
