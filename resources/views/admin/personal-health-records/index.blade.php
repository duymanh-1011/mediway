@extends('admin.layouts.app')

@section('title', 'Lịch sử sức khỏe - Mediway')

@section('breadcrumb')
    Admin / Bệnh nhân / Lịch sử sức khỏe
@endsection

@section('content')
<div class="health-list-page">
    <div class="health-list-heading">
        <div>
            <h1 class="page-title">Lịch sử sức khỏe</h1>
            <p class="page-description">
                Tra cứu các lần ghi nhận chỉ số sức khỏe của bệnh nhân.
            </p>
        </div>

        <a href="{{ route('admin.personal-health-records.create') }}"
           class="hl-btn hl-primary">
            + Thêm lần đo
        </a>
    </div>

    @if(session('success'))
        <div class="hl-alert hl-success">{{ session('success') }}</div>
    @endif

    <div class="hl-card">
        <form action="{{ route('admin.personal-health-records.index') }}"
              method="GET"
              class="hl-filters">

            <div class="hl-field hl-search">
                <label for="search">Tìm kiếm bệnh nhân</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Mã BN, họ tên hoặc số điện thoại..."
                >
            </div>

            <div class="hl-field">
                <label for="date_from">Từ ngày</label>
                <input type="date" id="date_from" name="date_from"
                       value="{{ $dateFrom }}">
            </div>

            <div class="hl-field">
                <label for="date_to">Đến ngày</label>
                <input type="date" id="date_to" name="date_to"
                       value="{{ $dateTo }}">
            </div>

            @if($patientId)
                <input type="hidden" name="patient_id"
                       value="{{ $patientId }}">
            @endif

            <button type="submit" class="hl-btn hl-primary">
                Lọc
            </button>

            <a href="{{ route('admin.personal-health-records.index') }}"
               class="hl-btn hl-light">
                Đặt lại
            </a>
        </form>

        @if($errors->any())
            <div class="hl-alert hl-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="hl-table-wrap">
            <table class="hl-table">
                <thead>
                    <tr>
                        <th>Thời điểm đo</th>
                        <th>Mã BN</th>
                        <th>Bệnh nhân</th>
                        <th>Chiều cao</th>
                        <th>Cân nặng</th>
                        <th>BMI</th>
                        <th>Huyết áp</th>
                        <th>Nhịp tim</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->recorded_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>{{ $record->patient_code }}</strong>
                            </td>
                            <td>{{ $record->patient?->full_name ?? '-' }}</td>
                            <td>
                                {{ $record->height_cm !== null ? $record->height_cm . ' cm' : '-' }}
                            </td>
                            <td>
                                {{ $record->weight_kg !== null ? $record->weight_kg . ' kg' : '-' }}
                            </td>
                            <td>
                                @if($record->bmi !== null)
                                    <span class="hl-bmi">{{ $record->bmi }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($record->systolic_blood_pressure !== null
                                    && $record->diastolic_blood_pressure !== null)
                                    {{ $record->systolic_blood_pressure }}/{{ $record->diastolic_blood_pressure }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                {{ $record->heart_rate ?? '-' }}
                            </td>
                            <td>
                                <div class="hl-actions">
                                    <a href="{{ route('admin.personal-health-records.show', $record) }}"
                                       class="hl-action hl-view">
                                        Chi tiết
                                    </a>
                                    <a href="{{ route('admin.personal-health-records.edit', $record) }}"
                                       class="hl-action hl-edit">
                                        Sửa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="hl-empty">
                                Không tìm thấy bản ghi sức khỏe phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="hl-footer">
            <span>
                Hiển thị {{ $records->count() }} /
                {{ $records->total() }} lần đo
            </span>

            <div class="hl-pages">
                @if($records->onFirstPage())
                    <span class="hl-btn hl-light hl-disabled">Trước</span>
                @else
                    <a href="{{ $records->previousPageUrl() }}"
                       class="hl-btn hl-light">Trước</a>
                @endif

                <span>
                    Trang {{ $records->currentPage() }} /
                    {{ $records->lastPage() }}
                </span>

                @if($records->hasMorePages())
                    <a href="{{ $records->nextPageUrl() }}"
                       class="hl-btn hl-light">Sau</a>
                @else
                    <span class="hl-btn hl-light hl-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.health-list-page{min-width:0}
.health-list-heading{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;margin-bottom:22px}
.health-list-heading .page-description{margin:7px 0 0;color:#64748b}
.hl-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:22px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.hl-filters{display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin-bottom:22px}
.hl-field{display:flex;flex-direction:column;gap:7px;min-width:145px}
.hl-search{flex:1;min-width:220px}
.hl-field label{font-size:12px;font-weight:700;color:#475569}
.hl-field input{width:100%;min-height:41px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#0f172a}
.hl-btn{display:inline-flex;justify-content:center;align-items:center;min-height:41px;padding:10px 15px;border:1px solid transparent;border-radius:9px;text-decoration:none;font-family:inherit;font-weight:700;font-size:13px;cursor:pointer;white-space:nowrap}
.hl-primary{background:#2563eb;color:#fff}
.hl-light{background:#fff;border-color:#cbd5e1;color:#334155}
.hl-table-wrap{overflow-x:auto}
.hl-table{width:100%;min-width:1000px;border-collapse:collapse;text-align:left}
.hl-table th{padding:14px 12px;background:#f8fafc;color:#64748b;font-size:12px;white-space:nowrap}
.hl-table td{padding:15px 12px;border-bottom:1px solid #edf2f7;color:#334155;font-size:13px}
.hl-table tbody tr:hover{background:#fafcff}
.hl-table tbody tr:last-child td{border-bottom:0}
.hl-bmi{display:inline-block;background:#eff6ff;color:#1d4ed8;padding:6px 9px;border-radius:7px;font-weight:700}
.hl-actions{display:flex;gap:7px}
.hl-action{padding:7px 10px;border-radius:7px;text-decoration:none;font-size:12px;font-weight:700}
.hl-view{background:#eff6ff;color:#1d4ed8}
.hl-edit{background:#f1f5f9;color:#334155}
.hl-empty{text-align:center;padding:40px!important;color:#64748b!important}
.hl-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:20px;color:#64748b;font-size:12px}
.hl-pages{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.hl-disabled{opacity:.45;cursor:default}
.hl-alert{padding:13px 15px;border-radius:9px;margin-bottom:16px;font-size:13px}
.hl-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.hl-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
@media(max-width:700px){.hl-card{padding:14px}.hl-filters{flex-direction:column;align-items:stretch}.hl-field{width:100%}}
</style>
@endpush