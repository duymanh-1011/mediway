@extends('doctor.layout')
@section('title', 'Kê đơn thuốc')
@section('breadcrumb', 'Kê đơn thuốc')
@section('content')
@php
    $oldItems = old('items');
    $rows = is_array($oldItems) && count($oldItems) > 0 ? array_slice($oldItems, 0, 30) : [[]];
    $units = $medicines->flatMap(fn ($medicine) => array_keys($medicine->salePrices()))->unique()->values();
@endphp
<section class="tech-page tech-detail-page doctor-visit" data-doctor-return="don-thuoc" data-visit-id="{{ $record->id }}">
    <a class="portal-btn portal-btn--secondary tech-back" href="{{ route('doctor.visits.show', $record->id) }}#don-thuoc">← Quay lại lượt khám</a>
    <section class="tech-panel tech-detail-panel doctor-visit__section"><p class="portal-eyebrow">ĐƠN THUỐC</p><h1>Kê đơn thuốc</h1>
        <p>{{ $record->patient?->full_name ?? 'Chưa cập nhật' }} · {{ $record->visit_code }}</p>
        <p>Chọn thuốc, đơn vị có giá và nhập đầy đủ hướng dẫn sử dụng.</p>
        <form class="tech-form doctor-prescription-form" method="post" action="{{ route('doctor.visits.prescriptions.store', $record->id) }}" data-doctor-prescription>
            @csrf
            <div data-prescription-rows>
                @foreach($rows as $index => $values)
                    @include('doctor.visits._prescription-item', ['index' => $index, 'values' => is_array($values) ? $values : []])
                @endforeach
            </div>
            <template data-prescription-template>@include('doctor.visits._prescription-item', ['index' => '__INDEX__', 'values' => []])</template>
            <button type="button" class="portal-btn portal-btn--secondary" data-prescription-add>+ Thêm thuốc</button>
            <label for="prescription-instructions">Lời dặn chung<textarea id="prescription-instructions" name="general_instructions" maxlength="5000" rows="3">{{ old('general_instructions') }}</textarea></label>
            <label for="prescription-notes">Ghi chú của bác sĩ (bệnh nhân có thể xem)<textarea id="prescription-notes" name="doctor_notes" maxlength="5000" rows="2">{{ old('doctor_notes') }}</textarea></label>
            <p>Vui lòng kiểm tra kỹ. Sau khi tạo đơn, bác sĩ không thể sửa hoặc hủy đơn, thay đổi nội dung khám, chẩn đoán hay chỉ định thêm dịch vụ trong trang này.</p>
            <label class="doctor-prescription-confirm"><input type="checkbox" name="confirm_create" value="1" @checked(in_array(old('confirm_create'), ['1', 1, true, 'yes', 'on'], true)) required @error('confirm_create') aria-invalid="true" aria-describedby="prescription-confirm-error" @enderror> Tôi đã kiểm tra thông tin thuốc và xác nhận tạo đơn.</label>
            @error('confirm_create')<p id="prescription-confirm-error" class="doctor-prescription-error" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="portal-btn portal-btn--primary">Lưu đơn thuốc</button>
        </form>
    </section>
</section>
@endsection
