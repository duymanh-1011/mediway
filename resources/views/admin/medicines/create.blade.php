@extends('admin.layouts.app')
@section('admin-page', 'medicines-create')

@section(
    'title',
    'Thêm thuốc - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Thuốc</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Thêm thuốc
            </h1>

            <p class="page-description">
                Thêm thuốc mới vào danh mục Mediway.
            </p>

        </div>

        <a
            href="{{ route('admin.medicines.index') }}"
            class="admin-btn admin-btn-secondary"
        >
            ← Quay lại
        </a>

    </div>

    <form novalidate
        method="POST"
        action="{{ route('admin.medicines.store') }}"
    >

        @csrf

        <div class="card">

            @include('admin.medicines._form')

            <div class="form-actions">

                <a
                    href="{{ route('admin.medicines.index') }}"
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
