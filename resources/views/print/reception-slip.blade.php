@php($patient = $visit->patient)

<x-print.document
    class="reception-slip"
    :title="'Phiếu khám '.$visit->visit_code"
    heading="PHIẾU KHÁM BỆNH"
    brand-subtitle="Tiếp nhận bệnh nhân"
    :identifiers="['Mã bệnh nhân' => $patient?->patient_code, 'Mã lượt khám' => $visit->visit_code]"
    :back-url="$backUrl"
    back-label="Quay lại tiếp nhận"
    print-label="In phiếu khám"
>
    <x-print.fields :values="[
        'Họ và tên' => $patient?->full_name,
        'Ngày sinh' => $patient?->date_of_birth?->format('d/m/Y'),
        'Giới tính' => $patient?->gender ? \App\Models\AdminDisplay::label($patient->gender) : null,
        'Địa chỉ' => $patient?->detail?->current_address ?: $patient?->detail?->permanent_address,
        'Số điện thoại' => $patient?->user?->phone,
        'Lý do khám' => $visit->reason_for_visit ?? $visit->appointment?->reason_for_visit ?? $visit->clinicalNote?->reason_for_visit,
        'Khám tại' => $visit->examinationRoom?->name,
        'Phí khám' => $fee ? number_format((float) $fee->amount, 0, ',', '.').' đ' : null,
    ]" fallback="Chưa ghi nhận" />
    @if ($fee && in_array($fee->paymentStatus?->name, ['UNPAID', 'PENDING'], true))
        <p class="print-document-text">Vui lòng thanh toán phí khám trước khi vào phòng khám.</p>
    @endif

    <div class="reception-slip-end">
        <section class="print-section reception-slip-number" aria-label="Số thứ tự khám">
            <h2 class="print-heading">Số thứ tự khám</h2>
            <strong @class(['reception-slip-number__value' => $queueNumber !== null])>{{ $queueNumber ?? 'Chưa cấp' }}</strong>
        </section>
        <footer class="reception-slip-footer">
            <p><time datetime="{{ $visit->checked_in_at->toIso8601String() }}">Hà Nội, ngày {{ $visit->checked_in_at->format('d') }} tháng {{ $visit->checked_in_at->format('m') }} năm {{ $visit->checked_in_at->format('Y') }} lúc {{ $visit->checked_in_at->format('H:i') }}</time></p>
            <p>Người tiếp đón<br><strong>{{ filled($receptionistName) ? $receptionistName : 'Chưa ghi nhận' }}</strong></p>
        </footer>
    </div>
</x-print.document>
