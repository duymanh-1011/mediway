@extends('admin.layouts.app')
@section('admin-page', 'medicines-edit')

@section(
    'title',
    'Sửa thuốc - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Thuốc</span>
    <span aria-hidden="true">/</span>
    <span>{{ $medicine->medicine_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Sửa thuốc
            </h1>

            <p class="page-description">
                Cập nhật thông tin
                {{ $medicine->name }}.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.medicines.show',
                $medicine
            ) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route(
            'admin.medicines.update',
            $medicine
        ) }}"
    >

        @csrf
        @method('PUT')

        <div class="card">

            @include('admin.medicines._form')

            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.medicines.show',
                        $medicine
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
