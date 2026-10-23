@extends('admin.layouts.app')
@section('admin-page', 'personal-health-records-edit')

@section('title', 'Sửa chỉ số sức khỏe - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Sức khỏe</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chỉnh sửa</strong>
@endsection

@section('content')
<div class="hr-page">
    <div class="hr-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa chỉ số sức khỏe</h1>
            <p class="page-description">
                Bản ghi #{{ $record->id }} —
                @include('admin.layouts.partials.record-code', ['code' => $record->patient?->patient_code])
            </p>
        </div>

        <a href="{{ route('admin.personal-health-records.show', $record) }}"
           class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>

    <div class="hr-card">
        @if($errors->any())
            <div class="hr-alert">
                <strong>Vui lòng kiểm tra dữ liệu:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ \App\Models\AdminDisplay::text($error) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form novalidate method="POST"
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
                   class="admin-btn admin-btn-secondary">Hủy</a>

                <button type="submit"
                        class="admin-btn admin-btn-primary"
                        onclick="return confirm('Xác nhận cập nhật các chỉ số của lần đo này?');">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
