@php
    $diagnoses = $visit?->diagnoses?->map(fn ($diagnosis) => $diagnosis->diagnosis_description ?: $diagnosis->disease?->name)->filter()->join('; ');
@endphp
<div class="medicine-info">
    <div><b>Họ và tên:</b> {{ $patientName }}</div>
    <div><b>Tuổi:</b> {{ $patient?->date_of_birth && $paidAt ? (int) $patient->date_of_birth->diffInYears($paidAt) : '—' }}</div>
    <div><b>Địa chỉ:</b> {{ $patient?->detail?->current_address ?: '—' }}</div>
    <div><b>Giới tính:</b> {{ $patient?->gender ? \App\Models\AdminDisplay::label($patient->gender) : '—' }}</div>
    <div><b>Chẩn đoán:</b> {{ $diagnoses ?: '—' }}</div>
    <div><b>Bác sĩ kê đơn:</b> {{ $prescription?->doctor?->full_name ?? '—' }}</div>
</div>
<div class="medicine-table-wrap"><table class="medicine-table">
    <colgroup><col class="medicine-col-index"><col><col class="medicine-col-unit"><col class="medicine-col-quantity"><col class="medicine-col-price"><col class="medicine-col-price"></colgroup>
    <thead><tr><th>STT</th><th>TÊN THUỐC / HÀM LƯỢNG</th><th>ĐVT</th><th>SỐ LƯỢNG</th><th>ĐƠN GIÁ</th><th>THÀNH TIỀN</th></tr></thead>
    <tbody>
    @foreach ($prescription->items as $item)
        <tr>
            <td data-label="STT" class="medicine-center">{{ $loop->iteration }}</td>
            <td data-label="Thuốc"><div><strong>{{ $item->medicine?->name ?? '—' }}@if ($item->medicine?->strength) · {{ $item->medicine->strength }}@endif</strong>@if ($item->usage_instructions)<div class="medicine-usage">{{ $item->usage_instructions }}</div>@endif</div></td>
            <td data-label="Đơn vị" class="medicine-center">{{ $item->usage_unit ?: '—' }}</td>
            <td data-label="Số lượng" class="medicine-center">{{ $item->quantity }}</td>
            <td data-label="Đơn giá" class="medicine-number">{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
            <td data-label="Thành tiền" class="medicine-number">{{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    <tr class="medicine-total"><td colspan="5">TỔNG TIỀN BỆNH NHÂN THANH TOÁN</td><td class="medicine-number">{{ $amountFormatted }} VNĐ</td></tr>
    </tbody>
</table></div>
<p class="medicine-words"><strong>Số tiền bằng chữ:</strong> {{ $amountWords }}.</p>
<section class="medicine-doctor-notes">
    <h3>LỜI DẶN CỦA BÁC SĨ</h3>
    <p>{{ $prescription?->general_instructions ?: 'Chưa có lời dặn riêng.' }}</p>
</section>
<section class="medicine-guidance" aria-label="Lưu ý sử dụng thuốc">
    <h3>LƯU Ý KHI SỬ DỤNG THUỐC</h3>
    <ul>
        <li>Dùng thuốc đúng liều lượng, thời điểm và cách dùng theo chỉ định của bác sĩ.</li>
        <li>Nếu có biểu hiện bất thường hoặc nghi ngờ tác dụng không mong muốn, ngừng dùng thuốc nghi ngờ và liên hệ bác sĩ hoặc cơ sở y tế để được hướng dẫn.</li>
        <li>Nếu khó thở, sưng mặt, môi hoặc lưỡi, ngất hoặc có dấu hiệu dị ứng nặng, cần đến cơ sở cấp cứu ngay.</li>
        <li>Không tự ý tăng, giảm liều hoặc kết hợp thêm thuốc khi chưa được nhân viên y tế có chuyên môn hướng dẫn.</li>
        <li>Bảo quản theo hướng dẫn trên bao bì và để xa tầm tay trẻ em.</li>
    </ul>
</section>
