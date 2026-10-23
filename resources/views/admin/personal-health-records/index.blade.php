@extends('admin.layouts.app')
@section('admin-page', 'personal-health-records-index')

@section('title', 'Lịch sử sức khỏe - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lịch sử sức khỏe</strong>
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
           class="admin-btn admin-btn-primary">Thêm lần đo</a>
    </div>

    @if(session('success'))
        <div class="hl-alert hl-success">{{ session('success') }}</div>
    @endif

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc hồ sơ sức khỏe',
        'description' => 'Lọc theo bệnh nhân và khoảng thời gian.',
    ])
        <form action="{{ route('admin.personal-health-records.index') }}"
              method="GET"
              class="hl-filters admin-filter-form">

            <div class="hl-field hl-search admin-filter-field">
                <label for="search">Tìm kiếm bệnh nhân</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Mã BN, họ tên hoặc số điện thoại..."
                >
@error('search')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="hl-field admin-filter-field">
                <label for="date_from">Từ ngày</label>
                <input type="date" id="date_from" name="date_from"
                       value="{{ $dateFrom }}">
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="hl-field admin-filter-field">
                <label for="date_to">Đến ngày</label>
                <input type="date" id="date_to" name="date_to"
                       value="{{ $dateTo }}">
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            @if($patientId)
                <input type="hidden" name="patient_id"
                       value="{{ $patientId }}">
            @endif





@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.personal-health-records.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="hl-card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách lần đo sức khỏe'])


        @if($errors->any())
            <div class="hl-alert hl-error">
                @foreach($errors->all() as $error)
                    <div>{{ \App\Models\AdminDisplay::text($error) }}</div>
                @endforeach
            </div>
        @endif

        <div class="hl-table-wrap">
            <table class="hl-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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
                            @include('admin.layouts.partials.row-number', ['rows' => $records, 'index' => $loop->index])
                            <td>{{ $record->recorded_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>@include('admin.layouts.partials.record-code', ['code' => $record->patient_code])</strong>
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
                            <td><div class="admin-actions admin-table-actions">
                                <div class="hl-actions">
                                    <a href="{{ route('admin.personal-health-records.show', $record) }}"
                                       class="admin-btn admin-btn-detail">Chi tiết</a>
                                    <a href="{{ route('admin.personal-health-records.edit', $record) }}"
                                       class="admin-btn admin-btn-secondary">Sửa</a>
                                </div>
                            </div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="hl-empty">
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
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
                @else
                    <a href="{{ $records->previousPageUrl() }}"
                       class="admin-btn admin-btn-secondary">Trước</a>
                @endif

                <span>
                    Trang {{ $records->currentPage() }} /
                    {{ $records->lastPage() }}
                </span>

                @if($records->hasMorePages())
                    <a href="{{ $records->nextPageUrl() }}"
                       class="admin-btn admin-btn-secondary">Sau</a>
                @else
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
