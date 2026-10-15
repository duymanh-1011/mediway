@extends('admin.layouts.app')

@section('title', 'Sửa chỉ số sức khỏe - Mediway')

@section('breadcrumb')
    Admin / Bệnh nhân / Sức khỏe / Chỉnh sửa
@endsection

@section('content')
<div class="hr-page">
    <div class="hr-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa chỉ số sức khỏe</h1>
            <p class="page-description">
                Bản ghi #{{ $record->id }} —
                {{ $record->patient?->patient_code }}
            </p>
        </div>

        <a href="{{ route('admin.personal-health-records.show', $record) }}"
           class="hr-btn hr-light">
            ← Chi tiết lần đo
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
              action="{{ route('admin.personal-health-records.update', $record) }}">
            @csrf
            @method('PUT')

            @include('admin.personal-health-records._form')

            <div class="hr-note">
                Việc chỉnh sửa chỉ áp dụng cho lần đo này.
                Bệnh nhân liên kết không thể thay đổi.
                Trước khi sử dụng thực tế, các lần sửa chỉ số sức khỏe
                phải được ghi nhật ký để truy vết.
            </div>

            <div class="hr-actions">
                <a href="{{ route('admin.personal-health-records.show', $record) }}"
                   class="hr-btn hr-light">
                    Hủy bỏ
                </a>

                <button type="submit"
                        class="hr-btn hr-primary"
                        onclick="return confirm('Xác nhận cập nhật các chỉ số của lần đo này?');">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
@include('admin.personal-health-records._styles')
@endpush