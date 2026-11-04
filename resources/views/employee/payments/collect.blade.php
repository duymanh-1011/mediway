@extends('employee.layout')
@section('title', 'Ghi nhận thanh toán')
@section('content')
<section class="portal-shell" style="max-width:760px;padding:24px">
    <a href="{{ route('employee.payments.show', array_merge(['payable' => $payable->id], $backQuery)) }}">← Quay lại khoản thu</a>
    <h1>Ghi nhận thanh toán</h1>
    <p>{{ $payable->payable_code }} · {{ $payable->medicalVisit?->visit_code }}</p>
    <p>Bệnh nhân: {{ $payable->medicalVisit?->patient?->full_name }} · {{ $payable->medicalVisit?->patient?->patient_code }}</p>
    <p>Nội dung: {{ $payable->description }}</p>
    <p>Tổng phải thu: <strong>{{ number_format($payable->amount, 0, ',', '.') }} đ</strong> · Đã thu: 0 đ · Còn lại: {{ number_format($payable->amount, 0, ',', '.') }} đ</p>
    <p>Người thu: {{ $employee->full_name }} · {{ $employee->employee_code }}</p>
    @if ($errors->any()) <div role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    <form method="post" action="{{ route('employee.payments.collect.store', array_merge(['payable' => $payable->id], $backQuery)) }}">
        @csrf
        <p><label>Phương thức <select name="payment_method"><option value="CASH">Tiền mặt</option><option value="QR" disabled>QR — chưa hỗ trợ xác minh</option></select></label></p>
        <p><label>Mã tham chiếu <input name="provider_transaction_code" maxlength="120" value="{{ old('provider_transaction_code') }}"></label></p>
        <p><label>Nội dung thanh toán <input name="payment_content" maxlength="255" value="{{ old('payment_content') }}"></label></p>
        <p><label>Ghi chú <textarea name="notes">{{ old('notes') }}</textarea></label></p>
        <p><label><input type="checkbox" name="confirm_payment" value="1" required> Tôi xác nhận đã nhận đủ tiền.</label></p>
        <button class="portal-btn portal-btn--primary" type="submit">Xác nhận thu tiền</button>
    </form>
</section>
@endsection
