@extends('doctor.layout')
@section('title', 'Chỉ định dịch vụ')
@section('breadcrumb', 'Chỉ định dịch vụ')
@section('content')
<section class="tech-page tech-detail-page">
    <a class="portal-btn portal-btn--secondary tech-back" href="{{ route('doctor.visits.show', $record->id) }}">← Quay lại lượt khám</a>
    <section class="tech-panel tech-detail-panel">
        <p class="portal-eyebrow">CHỈ ĐỊNH DỊCH VỤ</p><h1>Chọn dịch vụ</h1>
        <p>{{ $record->patient?->full_name ?? 'Chưa cập nhật' }} · {{ $record->visit_code }} · {{ $record->status?->description ?? 'Chưa cập nhật' }}</p>
        @if(! $record->canOrderServices())
            <div class="portal-alert portal-alert--info">Lượt khám chưa ở trạng thái cho phép chỉ định dịch vụ.</div>
        @else
            <p>Chọn dịch vụ từ danh mục. Hệ thống ghi nhận chi phí theo giá tại thời điểm chỉ định.</p>
            <form class="tech-form doctor-order-form" method="post" action="{{ route('doctor.visits.orders.store', $record->id) }}">
                @csrf
                <label for="order-reason">Lý do chỉ định<textarea id="order-reason" name="reason" rows="3" maxlength="5000">{{ old('reason') }}</textarea></label>
                <label for="order-instructions">Hướng dẫn thực hiện<textarea id="order-instructions" name="instructions" rows="2" maxlength="5000">{{ old('instructions') }}</textarea></label>
                <fieldset class="doctor-order-services"><legend>Danh mục dịch vụ <span aria-hidden="true">*</span></legend>
                    @forelse($services as $service)
                        <label class="doctor-order-option"><input type="checkbox" name="medical_service_ids[]" value="{{ $service->id }}" @checked(in_array((string) $service->id, array_map('strval', (array) old('medical_service_ids', [])), true))>
                            <span><strong>{{ $service->name }}</strong><small>{{ $service->service_code }} · {{ $service->serviceGroups->pluck('name')->join(', ') ?: 'Chưa phân nhóm' }}</small></span>
                            <span>{{ number_format((float) $service->price, 0, ',', '.') }} đ</span>
                        </label>
                    @empty<p>Chưa có dịch vụ đang hoạt động trong danh mục.</p>@endforelse
                </fieldset>
                @if($services->isNotEmpty())<button class="portal-btn portal-btn--primary" type="submit">Tạo chỉ định</button>@endif
            </form>
        @endif
    </section>
</section>
@endsection
