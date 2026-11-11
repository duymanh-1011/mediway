@extends('employee.layout')
@section('title', 'Chi tiết tiếp nhận trực tiếp')
@section('content')
<section class="employee-section reception-work">
    <a class="employee-card__link" href="{{ route('employee.reception.index') }}">← Quay lại tiếp đón</a>
    <div class="employee-section__heading"><div><p class="portal-eyebrow">TIẾP NHẬN TRỰC TIẾP</p><h1>Thông tin lượt khám</h1><p>Kiểm tra thông tin tiếp nhận và trạng thái phí khám.</p></div></div>
    <section class="employee-card"><h2>{{ $visit->visit_code }}</h2><dl class="employee-facts">
        <div><dt>Bệnh nhân</dt><dd>{{ $visit->patient?->full_name ?? '—' }}</dd></div><div><dt>Mã bệnh nhân</dt><dd>{{ $visit->patient?->patient_code ?? '—' }}</dd></div>
        <div><dt>Bác sĩ</dt><dd>{{ $visit->doctor?->full_name ?? '—' }}</dd></div><div><dt>Chuyên khoa</dt><dd>{{ $visit->specialty?->name ?? '—' }}</dd></div>
        <div><dt>Phòng khám</dt><dd>{{ $visit->examinationRoom?->name ?? '—' }}</dd></div><div><dt>Tiếp nhận lúc</dt><dd>{{ $visit->checked_in_at?->format('H:i d/m/Y') ?? '—' }}</dd></div>
        <div><dt>Trạng thái</dt><dd>{{ \App\Models\AdminDisplay::label($visit->status?->name) }}</dd></div>
    </dl>
    @php($fee = $visit->payables->firstWhere('payment_type', 'EXAMINATION_FEE'))
    @if($fee?->paymentStatus?->name === 'PAID')<p>Phí khám đã được thanh toán.</p>@elseif($fee && in_array($fee->paymentStatus?->name, ['UNPAID', 'PENDING'], true))<p>Bệnh nhân cần thanh toán phí khám trước khi được gọi.</p>@else<p>Không xác định được trạng thái phí khám. Vui lòng liên hệ thu ngân để kiểm tra.</p>@endif
    <a class="portal-btn portal-btn--secondary" href="{{ route('employee.reception.slip', $visit) }}">Xem phiếu khám</a>
    </section>
</section>
@endsection
