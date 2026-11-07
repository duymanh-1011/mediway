<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đơn thuốc {{ $record->prescription_code }} - Mediway</title>
    @vite(['resources/css/payment-receipt.css', 'resources/js/employee.js'])
</head>
<body>
    <div class="screen-wrap">
        <nav class="toolbar" aria-label="Thao tác đơn thuốc">
            <a href="{{ route('patient.results.visit', ['visit' => $record->medical_visit_id, 'tab' => 'don-thuoc']) }}">← Quay lại đơn thuốc lượt khám</a>
            @if ($record->hasPdf())<a href="{{ route('patient.prescriptions.document', $record->id) }}">Xem PDF gốc</a>@endif
            <button type="button" data-f08-print>In đơn thuốc</button>
        </nav>
        <main class="paper">
            <header class="document-head">
                <div class="brand-line"><span class="brand-icon" aria-hidden="true">✦</span><div><p class="brand-name">Mediway</p><p class="brand-sub">Hồ sơ điều trị</p></div></div>
                <div class="document-meta"><span class="meta-label">Mã đơn</span> <strong>{{ $record->prescription_code }}</strong><br><span class="meta-label">Mã lượt khám</span> <strong>{{ $record->medicalVisit->visit_code }}</strong></div>
            </header>
            <div class="document-title"><h1>ĐƠN THUỐC</h1><p>{{ $record->prescribed_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật thời gian kê đơn' }}</p></div>
            @if ($record->status !== 'ACTIVE')<p><strong>Trạng thái đơn thuốc:</strong> {{ \App\Models\AdminDisplay::label($record->status) }}</p>@endif
            <dl class="info-grid">
                <div><dt class="field-label">Bệnh nhân</dt><dd class="field-value">{{ $patient->full_name }} · {{ $patient->patient_code }}</dd></div>
                <div><dt class="field-label">Ngày sinh</dt><dd class="field-value">{{ $patient->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd></div>
                <div><dt class="field-label">Bác sĩ kê đơn</dt><dd class="field-value">{{ $record->doctor?->full_name ?? 'Chưa cập nhật' }}</dd></div>
                @if ($record->medicalVisit->diagnoses->isNotEmpty())
                    <div><dt class="field-label">Chẩn đoán</dt><dd class="field-value">{{ $record->medicalVisit->diagnoses->map(fn ($diagnosis) => $diagnosis->disease?->name ?: $diagnosis->diagnosis_description)->filter()->join('; ') }}</dd></div>
                @endif
            </dl>
            <section class="section" aria-labelledby="prescribed-medicines">
                <h2 class="section-title" id="prescribed-medicines">Thuốc được kê</h2>
                <table>
                    <thead><tr><th scope="col">STT</th><th scope="col">Thuốc và hướng dẫn sử dụng</th><th scope="col">Đơn vị</th><th scope="col">Số lượng</th></tr></thead>
                    <tbody>
                        @forelse ($record->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $item->medicine?->name ?? 'Thuốc' }} {{ $item->medicine?->strength }}</strong>
                                    @if ($item->medicine?->active_ingredient)<br>Hoạt chất: {{ $item->medicine->active_ingredient }}@endif
                                    @if ($item->dose_per_use)<br>Liều dùng: {{ $item->dose_per_use }}@endif
                                    @if ($item->times_per_day !== null) · {{ str_replace('.', ',', (string) (float) $item->times_per_day) }} lần/ngày @endif
                                    @if ($item->duration_days !== null) · {{ $item->duration_days }} ngày @endif
                                    @if ($instruction = collect([$item->administration_time, $item->usage_method, $item->usage_instructions])->filter()->unique()->join(' · '))<br>{{ $instruction }}@endif
                                </td>
                                <td>{{ $item->usage_unit ?: '—' }}</td>
                                <td>{{ $item->quantity !== null ? str_replace('.', ',', (string) (float) $item->quantity) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Chưa có chi tiết thuốc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            @if ($record->general_instructions)<section class="section"><h2 class="section-title">Lời dặn</h2><p>{{ $record->general_instructions }}</p></section>@endif
        </main>
    </div>
</body>
</html>
