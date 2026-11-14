@extends('portal.layouts.app')
@section('title', 'Mua thuốc theo đơn - Mediway')
@section('content')
<section class="medical-page"><div class="portal-shell medical-shell">
    <a class="portal-back" href="{{ route('patient.prescriptions.show', $record) }}">← Quay lại đơn thuốc</a>
    <header class="medical-hero"><div><p class="medical-hero__eyebrow">ĐƠN THUỐC {{ $record->prescription_code }}</p><h1>Mua thuốc tại bệnh viện</h1><p>Kiểm tra thuốc và số tiền dự kiến trước khi xác nhận.</p></div></header>
    <div class="medical-content"><article class="patient-record">
        @if ($payable)
            <h2>Khoản thu tiền thuốc</h2>
            <p>Mã khoản thu: <strong>{{ $payable->payable_code }}</strong></p>
            <p>Trạng thái: <strong>{{ $payable->paymentStatus?->description ?: ($payable->paymentStatus?->name ?: 'Chưa có thông tin thanh toán') }}</strong></p>
            <p>Số tiền: <strong>{{ number_format((float) $payable->amount, 0, ',', '.') }} đ</strong></p>
            @if ($payable->paymentStatus?->name === 'PAID')
                <p>Vui lòng mang phiếu thu đến quầy thuốc để được hỗ trợ.</p><a href="{{ route('patient.results.visit', ['visit' => $record->medical_visit_id, 'tab' => 'giay-to']) }}">Xem phiếu thu</a>
            @elseif (in_array($payable->paymentStatus?->name, ['UNPAID', 'PENDING'], true))
                <p>Vui lòng đến thu ngân để được hỗ trợ thanh toán.</p><a href="{{ route('patient.payments.index').'#khoan-thu-'.$payable->id }}">Xem khoản cần thanh toán</a>
            @else
                <p>Vui lòng liên hệ nhân viên để kiểm tra trạng thái khoản thu.</p>
            @endif
        @elseif ($record->status !== 'ACTIVE' || $record->medicalVisit->status?->name !== 'COMPLETED')
            <p>Đơn thuốc chưa đủ điều kiện xác nhận mua. Vui lòng liên hệ nhân viên nếu cần hỗ trợ.</p>
        @elseif (! $priceValid)
            <p>Đơn thuốc chưa có đủ thông tin giá hợp lệ. Vui lòng liên hệ nhân viên để được hỗ trợ.</p>
        @else
            <h2>Bạn có muốn mua thuốc theo đơn tại bệnh viện không?</h2>
            <details class="clinical-visit-details" @if ($errors->has('confirm_purchase')) open @endif><summary>Có, tôi muốn mua thuốc tại bệnh viện</summary>
                <div class="clinical-visit-details__body"><h3>Thuốc theo đơn</h3>
                    <ul class="patient-medicine-list">@foreach($record->items as $item)
                        <li><strong>{{ $item->medicine?->name ?? 'Thuốc' }} {{ $item->medicine?->strength }}</strong>
                            <p>{{ $item->quantity }} {{ $item->usage_unit }} · Đơn giá {{ number_format((float) $item->unit_price, 0, ',', '.') }} đ · Thành tiền {{ number_format((float) $item->line_total, 0, ',', '.') }} đ</p></li>
                    @endforeach</ul>
                    <p>Tổng tiền dự kiến: <strong>{{ number_format((float) $record->pricedTotal(), 0, ',', '.') }} đ</strong></p>
                    <p>Xác nhận sẽ tạo khoản cần thanh toán; nhân viên sẽ hỗ trợ kiểm tra thuốc tại quầy.</p>
                    <form method="post" action="{{ route('patient.prescriptions.purchase.store', $record) }}">@csrf
                        <label><input type="checkbox" name="confirm_purchase" value="1" required> Tôi xác nhận muốn mua thuốc theo đơn tại bệnh viện.</label>
                        @error('confirm_purchase')<p role="alert">{{ $message }}</p>@enderror
                        <button class="portal-btn portal-btn--primary" type="submit">Xác nhận mua thuốc</button>
                    </form>
                </div>
            </details>
            <p><a href="{{ route('patient.prescriptions.show', $record) }}">Không mua tại bệnh viện</a></p>
        @endif
    </article></div>
</div></section>
@endsection
