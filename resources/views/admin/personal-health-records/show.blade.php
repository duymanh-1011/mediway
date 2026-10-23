@extends('admin.layouts.app')
@section('admin-page', 'personal-health-records-show')

@section('title', 'Chi tiết chỉ số sức khỏe - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Sức khỏe</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chi tiết</strong>
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
                @include('admin.layouts.partials.record-code', ['code' => $record->patient_code]) —
                {{ $record->recorded_at?->format('d/m/Y H:i') }}
            </p>
        </div>

        <div class="hd-actions">
            <a href="{{ route('admin.personal-health-records.index', ['patient_id' => $record->patient_id]) }}"
               class="admin-btn admin-btn-secondary">← Quay lại</a>

            <a href="{{ route('admin.personal-health-records.edit', $record) }}"
               class="admin-btn admin-btn-secondary">Sửa</a>
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
                <strong>@include('admin.layouts.partials.record-code', ['code' => $record->patient_code])</strong>
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
                   class="admin-btn admin-btn-detail">
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
