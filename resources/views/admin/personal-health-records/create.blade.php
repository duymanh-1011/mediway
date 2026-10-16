@extends('admin.layouts.app')

@section('title', 'Thêm chỉ số sức khỏe - Mediway')

@section('breadcrumb')
    Admin / Bệnh nhân / Sức khỏe / Thêm mới
@endsection

@section('content')
<div class="hr-page">
    <div class="hr-heading">
        <div>
            <h1 class="page-title">Thêm chỉ số sức khỏe</h1>
            <p class="page-description">
                Ghi nhận thêm một lần đo sức khỏe cho bệnh nhân.
            </p>
        </div>

        <a href="{{ route('admin.personal-health-records.index') }}"
           class="hr-btn hr-light">
            ← Lịch sử sức khỏe
        </a>
    </div>

    <div class="hr-card">
        @if($errors->any())
            <div class="hr-alert">
                <strong>Vui lòng kiểm tra dữ liệu:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('admin.personal-health-records.store') }}">
            @csrf

            @include('admin.personal-health-records._form')

            <div class="hr-note">
                Mỗi lần ghi nhận sẽ tạo một bản ghi mới.
                Các lần đo trước đó được giữ nguyên để theo dõi lịch sử sức khỏe.
            </div>

            <div class="hr-actions">
                <a href="{{ route('admin.personal-health-records.index') }}"
                   class="hr-btn hr-light">
                    Hủy bỏ
                </a>

                <button type="submit" class="hr-btn hr-primary">
                    Lưu chỉ số sức khỏe
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
@include('admin.personal-health-records._styles')
@endpush