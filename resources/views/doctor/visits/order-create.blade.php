@extends('doctor.layout')
@section('title', 'Chỉ định dịch vụ')
@section('breadcrumb', 'Chỉ định dịch vụ')
@section('content')
<section class="tech-page tech-detail-page doctor-visit" data-doctor-return="dich-vu" data-visit-id="{{ $record->id }}">
    <a class="portal-btn portal-btn--secondary tech-back" href="{{ route('doctor.visits.show', $record->id) }}#dich-vu">← Quay lại lượt khám</a>
    <section class="tech-panel tech-detail-panel doctor-visit__section">
        <p class="portal-eyebrow">CHỈ ĐỊNH DỊCH VỤ</p><h1>Chọn dịch vụ</h1>
        <dl class="tech-facts doctor-order-facts">
            <div><dt>Bệnh nhân</dt><dd>{{ $record->patient?->full_name ?? 'Chưa cập nhật' }} · {{ $record->patient?->patient_code ?? '—' }}</dd></div>
            <div><dt>Mã lượt khám</dt><dd>{{ $record->visit_code }}</dd></div>
            <div><dt>Bác sĩ phụ trách</dt><dd>{{ $record->doctor?->full_name ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Chuyên khoa</dt><dd>{{ $record->specialty?->name ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Phòng khám</dt><dd>{{ $record->examinationRoom?->name ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Trạng thái</dt><dd>{{ $record->status?->description ?? 'Chưa cập nhật' }}</dd></div>
        </dl>
        @if(! $record->canOrderServices())
            <div class="portal-alert portal-alert--info">Lượt khám chưa ở trạng thái cho phép chỉ định dịch vụ.</div>
        @else
            <p>Chọn dịch vụ từ danh mục. Hệ thống ghi nhận chi phí theo giá tại thời điểm chỉ định.</p>
            <form class="tech-form doctor-order-form" method="post" action="{{ route('doctor.visits.orders.store', $record->id) }}" data-service-order-picker>
                @csrf
                <div class="doctor-order-form__details">
                    <label for="order-reason">Lý do chỉ định<textarea id="order-reason" name="reason" rows="3" maxlength="5000" placeholder="Nhập lý do thực hiện dịch vụ">{{ old('reason') }}</textarea></label>
                    <label for="order-instructions">Hướng dẫn thực hiện<textarea id="order-instructions" name="instructions" rows="3" maxlength="5000" placeholder="Nhập hướng dẫn cho người thực hiện">{{ old('instructions') }}</textarea></label>
                </div>
                <fieldset class="doctor-order-services"><legend>Danh mục dịch vụ <span aria-hidden="true">*</span></legend>
                    @if($services->isNotEmpty())
                        <div class="doctor-order-summary" data-selection-summary hidden role="status">Đã chọn: <strong id="selected-count">0</strong> dịch vụ</div>
                        <div data-service-search-field hidden><label for="doctor-service-search">Tìm dịch vụ<input id="doctor-service-search" type="search" autocomplete="off" placeholder="Nhập tên, mã hoặc nhóm dịch vụ" aria-controls="doctor-service-list"></label></div>
                        <div id="doctor-service-list" class="doctor-order-list">
                    @endif
                    @forelse($services as $service)
                        <label class="doctor-order-option service-item"><input class="service-checkbox" type="checkbox" name="medical_service_ids[]" value="{{ $service->id }}" @checked(in_array((string) $service->id, array_map('strval', array_filter((array) old('medical_service_ids', []), 'is_scalar')), true))>
                            <span><strong class="service-name">{{ $service->name }}</strong><small class="service-code">{{ $service->service_code }} · {{ $service->serviceGroups->pluck('name')->join(', ') ?: 'Chưa phân nhóm' }}</small>
                                @if($service->unit || $service->estimated_duration_minutes)<small>@if($service->unit)Đơn vị: {{ $service->unit }}@endif @if($service->estimated_duration_minutes) · Thời gian dự kiến: {{ $service->estimated_duration_minutes }} phút @endif</small>@endif
                                @if($service->preparation_instructions)<small>Chuẩn bị: {{ $service->preparation_instructions }}</small>@endif
                            </span>
                            <span>{{ number_format((float) $service->price, 0, ',', '.') }} đ</span>
                        </label>
                    @empty<p>Chưa có dịch vụ đang hoạt động trong danh mục.</p>@endforelse
                    @if($services->isNotEmpty())</div><p data-service-search-empty role="status" hidden>Không tìm thấy dịch vụ phù hợp.</p>@endif
                </fieldset>
                @if($services->isNotEmpty())<button class="portal-btn portal-btn--primary" type="submit">Tạo chỉ định</button>@endif
            </form>
        @endif
    </section>
</section>
@endsection
