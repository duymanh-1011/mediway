@extends('portal.layouts.app')
@section('title', 'Đơn thuốc - Mediway')
@section('content')
<section class="medical-page"><div class="portal-shell medical-shell">
    @include('portal.clinical._navigation')
    <header class="medical-hero"><div><p class="medical-hero__eyebrow">HỒ SƠ CỦA BẠN</p><h1>Đơn thuốc</h1><p>Thông tin thuốc và hướng dẫn được ghi nhận trong từng lần khám.</p></div><div class="medical-hero__symbol" aria-hidden="true">✚</div></header>
    <div class="medical-content patient-records">
    <div class="medical-section-head"><div><span class="medical-section-head__eyebrow">THÔNG TIN ĐIỀU TRỊ</span><h2>Đơn thuốc của bạn</h2><p>Sắp xếp theo ngày kê đơn, mới nhất trước.</p></div><span class="medical-count">{{ $prescriptions->total() }} đơn thuốc</span></div>
    {{-- Mỗi thẻ là một đơn thuốc, có mã đơn và trạng thái. --}}
    <div class="patient-records__list">
    @forelse ($prescriptions as $prescription)
        <article class="patient-record">
            <div class="patient-record__header"><div><span class="patient-record__label">ĐƠN THUỐC</span><h3>{{ $prescription->prescription_code }}</h3></div><span @class(['patient-record__badge', 'patient-record__badge--pending' => $prescription->status !== 'ACTIVE'])>{{ match ($prescription->status) { 'ACTIVE' => 'Đang hiệu lực', 'CANCELLED' => 'Đã hủy', 'INACTIVE' => 'Không còn hiệu lực', default => $prescription->status ?: 'Chưa cập nhật' } }}</span></div>
            <div class="patient-record__meta"><span>Ngày kê: <strong>{{ $prescription->prescribed_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</strong></span><span>Bác sĩ: <strong>{{ $prescription->doctor?->full_name ?? 'Chưa cập nhật' }}</strong></span></div>
            @if ($prescription->general_instructions)<div class="patient-record__content"><h4>Lời dặn</h4><p>{{ $prescription->general_instructions }}</p></div>@endif
            <div class="patient-record__content"><h4>Danh sách thuốc</h4><ul class="patient-medicine-list">
                {{-- Từng thuốc hiển thị liều và hướng dẫn nếu các thông tin đó có dữ liệu. --}}
                @forelse ($prescription->items as $item)
                    <li><div class="medical-medicine__title"><span class="medical-medicine__icon" aria-hidden="true">✚</span><strong>{{ $item->medicine?->name ?? 'Thuốc' }} @if ($item->medicine?->strength)<small>{{ $item->medicine->strength }}</small>@endif</strong></div><div class="medical-medicine__facts">
                        @if ($item->quantity !== null)<span>Số lượng <strong>{{ $item->quantity }} {{ $item->usage_unit }}</strong></span>@endif
                        @if ($item->dose_per_use)<span>Liều dùng <strong>{{ $item->dose_per_use }}</strong></span>@endif
                        @if ($item->times_per_day !== null)<span>Số lần <strong>{{ $item->times_per_day }} lần/ngày</strong></span>@endif
                        @if ($item->duration_days !== null)<span>Thời gian <strong>{{ $item->duration_days }} ngày</strong></span>@endif
                    </div>@if ($item->administration_time || $item->usage_method || $item->usage_instructions)<p class="medical-medicine__instructions">{{ $item->administration_time }} {{ $item->usage_method }} {{ $item->usage_instructions }}</p>@endif</li>
                @empty<li>Chưa có chi tiết thuốc.</li>@endforelse
            </ul></div>
            {{-- Chỉ hiện nút PDF khi tệp đơn thuốc có thể mở. --}}
            @if ($prescription->hasPdf())<div class="patient-record__links"><a href="{{ route('patient.prescriptions.document', $prescription->id) }}">Xem đơn thuốc PDF <span aria-hidden="true">↗</span></a></div>@endif
        </article>
    @empty
        <div class="patient-records__empty"><span aria-hidden="true">▧</span><h3>Chưa có đơn thuốc</h3><p>Đơn thuốc sẽ xuất hiện tại đây khi có dữ liệu trong hồ sơ của bạn.</p></div>
    @endforelse
    </div>
    {{ $prescriptions->links() }}
</div></div></section>
@endsection
