@extends('admin.layouts.app')

@section('title', 'Chi tiết chỉ số sức khỏe - Mediway')

@section('breadcrumb')
    Admin / Bệnh nhân / Sức khỏe / Chi tiết
@endsection

@section('content')
@php
    $metrics = [
        ['label' => 'Chiều cao', 'value' => $record->height_cm, 'unit' => 'cm'],
        ['label' => 'Cân nặng', 'value' => $record->weight_kg, 'unit' => 'kg'],
        ['label' => 'BMI', 'value' => $record->bmi, 'unit' => ''],
        ['label' => 'Huyết áp tâm thu', 'value' => $record->systolic_blood_pressure, 'unit' => 'mmHg'],
        ['label' => 'Huyết áp tâm trương', 'value' => $record->diastolic_blood_pressure, 'unit' => 'mmHg'],
        ['label' => 'Nhịp tim', 'value' => $record->heart_rate, 'unit' => 'lần/phút'],
        ['label' => 'Nhiệt độ cơ thể', 'value' => $record->body_temperature, 'unit' => '°C'],
        ['label' => 'SpO2', 'value' => $record->spo2, 'unit' => '%'],
        ['label' => 'Nhịp thở', 'value' => $record->respiratory_rate, 'unit' => 'lần/phút'],
        ['label' => 'Đường huyết', 'value' => $record->blood_glucose, 'unit' => ''],
    ];
@endphp

<div class="health-detail-page">
    <div class="hd-heading">
        <div>
            <h1 class="page-title">Chi tiết lần đo sức khỏe</h1>
            <p class="page-description">
                {{ $record->patient_code }} —
                {{ $record->recorded_at?->format('d/m/Y H:i') }}
            </p>
        </div>

        <div class="hd-actions">
            <a href="{{ route('admin.personal-health-records.index', ['patient_id' => $record->patient_id]) }}"
               class="hd-btn hd-light">
                ← Lịch sử sức khỏe
            </a>

            <a href="{{ route('admin.personal-health-records.edit', $record) }}"
               class="hd-btn hd-primary">
                Chỉnh sửa
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="hd-alert">{{ session('success') }}</div>
    @endif

    <div class="hd-grid">
        <section class="hd-card">
            <h2>Bệnh nhân</h2>

            <div class="hd-row">
                <span>Mã bệnh nhân</span>
                <strong>{{ $record->patient_code }}</strong>
            </div>

            <div class="hd-row">
                <span>Họ và tên</span>
                <strong>{{ $record->patient?->full_name ?? '-' }}</strong>
            </div>

            <div class="hd-row">
                <span>Ngày giờ đo</span>
                <strong>{{ $record->recorded_at?->format('d/m/Y H:i') }}</strong>
            </div>

            @if($record->patient)
                <a href="{{ route('admin.patients.show', $record->patient) }}"
                   class="hd-btn hd-light hd-patient-link">
                    Xem hồ sơ bệnh nhân
                </a>
            @endif
        </section>

        <section class="hd-card">
            <h2>Thông tin ghi nhận</h2>

            <div class="hd-row">
                <span>Ngày tạo dữ liệu</span>
                <strong>{{ $record->created_at?->format('d/m/Y H:i') ?? '-' }}</strong>
            </div>

            <div class="hd-row">
                <span>Cập nhật lần cuối</span>
                <strong>{{ $record->updated_at?->format('d/m/Y H:i') ?? '-' }}</strong>
            </div>

            <p class="hd-help">
                Ngày giờ đo có thể khác ngày giờ nhập vào hệ thống.
                Hai mốc thời gian được lưu riêng để bảo toàn ngữ cảnh.
            </p>
        </section>
    </div>

    <section class="hd-card hd-section">
        <h2>Các chỉ số sức khỏe</h2>

        <div class="hd-metrics">
            @foreach($metrics as $metric)
                <div class="hd-metric">
                    <div class="hd-metric-label">{{ $metric['label'] }}</div>

                    <div class="hd-metric-value">
                        @if($metric['value'] !== null)
                            {{ $metric['value'] }}
                            <small>{{ $metric['unit'] }}</small>
                        @else
                            <span class="hd-missing">Chưa ghi nhận</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="hd-card hd-section">
        <h2>Ghi chú lần đo</h2>
        <p class="hd-notes">{{ $record->notes ?: 'Chưa có ghi chú.' }}</p>
    </section>
</div>
@endsection

@push('styles')
<style>
.health-detail-page{min-width:0}
.hd-heading{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;margin-bottom:22px}
.hd-heading .page-description{color:#64748b;margin:7px 0 0}
.hd-actions{display:flex;gap:10px;flex-wrap:wrap}
.hd-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:19px}
.hd-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:24px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.hd-card h2{font-size:17px;color:#0f172a;margin:0 0 18px}
.hd-section{margin-bottom:19px}
.hd-row{display:flex;justify-content:space-between;gap:15px;align-items:start;padding:13px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.hd-row span{color:#64748b}
.hd-row strong{text-align:right;color:#0f172a}
.hd-row:last-of-type{border-bottom:0}
.hd-help{font-size:12px;color:#64748b;line-height:1.7;margin:17px 0 0}
.hd-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.hd-metric{border:1px solid #e2e8f0;background:#f8fafc;border-radius:11px;padding:18px;min-width:0}
.hd-metric-label{color:#64748b;font-size:12px;font-weight:700;margin-bottom:12px}
.hd-metric-value{color:#0f172a;font-size:25px;font-weight:800;overflow-wrap:anywhere}
.hd-metric-value small{font-size:12px;font-weight:600;color:#64748b}
.hd-missing{font-size:13px;font-weight:500;color:#94a3b8}
.hd-notes{white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;color:#475569;line-height:1.7;margin:0}
.hd-btn{display:inline-flex;align-items:center;justify-content:center;min-height:41px;padding:10px 16px;border:1px solid transparent;border-radius:9px;text-decoration:none;font-size:13px;font-weight:700}
.hd-primary{background:#2563eb;color:#fff}
.hd-light{background:#fff;border-color:#cbd5e1;color:#334155}
.hd-patient-link{margin-top:20px}
.hd-alert{padding:13px 15px;border-radius:9px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;margin-bottom:17px;font-size:13px}
@media(max-width:900px){.hd-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:720px){.hd-grid{grid-template-columns:1fr}}
@media(max-width:490px){.hd-card{padding:17px}.hd-metrics{grid-template-columns:1fr}}
</style>
@endpush