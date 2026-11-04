@extends('admin.layouts.app')
@section('admin-page', 'prescriptions-create')
@section('title', 'Tạo đơn thuốc - Mediway')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a><span aria-hidden="true">/</span>
    <a href="{{ route('admin.visits.show', $visit) }}">Lượt khám {{ $visit->visit_code }}</a><span aria-hidden="true">/</span>
    <strong aria-current="page">Tạo đơn thuốc</strong>
@endsection
@section('content')
<div class="page-header"><div><h1 class="page-title">Tạo đơn thuốc</h1><p class="page-description">Nhập đơn thuốc cho lượt khám {{ $visit->visit_code }}.</p></div><a class="admin-btn admin-btn-secondary" href="{{ route('admin.visits.show', $visit) }}">← Quay lại</a></div>
<div class="card section-spacing"><h2 class="section-title">Thông tin lượt khám</h2><div class="detail-row"><span>Bệnh nhân</span><strong>{{ $visit->patient?->patient_code }} - {{ $visit->patient?->full_name }}</strong></div><div class="detail-row"><span>Bác sĩ kê đơn</span><strong>{{ $visit->doctor?->doctor_code }} - {{ $visit->doctor?->full_name }}</strong></div></div>
@if ($medicines->isEmpty())
    <div class="alert alert-error">Chưa có thuốc đang hoạt động. Vui lòng kiểm tra danh mục thuốc.</div>
@else
    <form method="POST" action="{{ route('admin.visits.prescription.store', $visit) }}" novalidate data-prescription-form data-whole-units="{{ json_encode(\App\Models\Medicine::WHOLE_QUANTITY_UNITS, JSON_UNESCAPED_UNICODE) }}">@csrf
        <div class="card section-spacing"><h2 class="section-title">Thuốc trong đơn</h2><p class="form-help">Nhập chỉ định và chọn đơn vị đã có giá trong danh mục thuốc. Giá được lưu tại thời điểm kê đơn; khoản thu chỉ tạo khi bệnh nhân xác nhận mua. Các mục có dấu * là bắt buộc.</p>
            <div data-prescription-items>
                @php($oldItems = old('items'))
                @foreach (is_array($oldItems) && count($oldItems) ? $oldItems : [[]] as $index => $item)
                    @include('admin.prescriptions._medicine-fields', ['index' => $index, 'item' => is_array($item) ? $item : []])
                @endforeach
            </div>
            <button type="button" class="admin-btn admin-btn-secondary" data-add-prescription-item>Thêm thuốc</button>
            <template data-prescription-template>@include('admin.prescriptions._medicine-fields', ['index' => '__INDEX__', 'item' => []])</template>
            <p class="form-help" data-prescription-limit aria-live="polite"></p>
        </div>
        <div class="card section-spacing"><h2 class="section-title">Hướng dẫn chung</h2><div class="form-group"><label for="general-instructions">Lời dặn cho bệnh nhân</label><textarea id="general-instructions" name="general_instructions" class="form-control" rows="3" maxlength="5000">{{ is_string(old('general_instructions')) ? old('general_instructions') : '' }}</textarea>@error('general_instructions')<p class="field-error">{{ $message }}</p>@enderror</div><div class="form-group"><label for="doctor-notes">Ghi chú của bác sĩ</label><textarea id="doctor-notes" name="doctor_notes" class="form-control" rows="3" maxlength="5000">{{ is_string(old('doctor_notes')) ? old('doctor_notes') : '' }}</textarea>@error('doctor_notes')<p class="field-error">{{ $message }}</p>@enderror</div><label><input type="checkbox" name="confirm_create" value="1" @checked(old('confirm_create') === '1') required> Tôi đã kiểm tra thông tin thuốc và xác nhận tạo đơn.</label>@error('confirm_create')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div class="header-actions"><a href="{{ route('admin.visits.show', $visit) }}" class="admin-btn admin-btn-secondary">Hủy</a><button type="submit" class="admin-btn admin-btn-primary">Lưu đơn thuốc</button></div>
    </form>
@endif
@endsection
@push('scripts')<script src="{{ asset('js/admin-prescription.js') }}" defer></script>@endpush
