@extends('admin.layouts.app')
@section('admin-page', 'patient-medical-history-index')
@section('title', 'Lịch sử khám - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lịch sử khám</strong>
@endsection

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Lịch sử khám</h1>
            <p class="page-description">Tra cứu các lượt khám của bệnh nhân.</p>
        </div>
    </div>

    @component('admin.layouts.partials.filter-panel', ['title' => 'Bộ lọc lịch sử khám', 'description' => 'Lọc theo từ khóa, ngày khám và trạng thái.'])
        <form data-filter-fields="4" action="{{ route('admin.patient-medical-history.index') }}" method="GET" class="admin-filter-form">
            <div class="admin-filter-field">
                <label for="history_q">Tìm kiếm</label>
                <input id="history_q" name="history_q" maxlength="150" value="{{ request('history_q') }}" placeholder="Mã bệnh nhân, họ tên, mã lượt khám, phòng khám...">
                @error('history_q')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="admin-filter-field">
                <label for="visit_status_id">Trạng thái</label>
                <select id="visit_status_id" name="visit_status_id">
                    <option value="">Tất cả</option>
                    @foreach($visitStatuses as $status)
                        <option value="{{ $status->id }}" @selected((string) request('visit_status_id') === (string) $status->id)>{{ \App\Models\AdminDisplay::label($status->name) }}</option>
                    @endforeach
                </select>
                @error('visit_status_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="admin-filter-field">
                <label for="date_from">Từ ngày</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
                @error('date_from')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="admin-filter-field">
                <label for="date_to">Đến ngày</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
                @error('date_to')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            @include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.patient-medical-history.index')])
        </form>
    @endcomponent

    <section class="admin-card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách lượt khám'])
        <div class="admin-table-scroll">
            <table class="admin-table patient-medical-history-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th scope="col">Ngày khám</th>
                        <th scope="col">Mã lượt khám</th>
                        <th scope="col">Bệnh nhân</th>
                        <th scope="col">Bác sĩ</th>
                        <th scope="col">Chuyên khoa</th>
                        <th scope="col">Phòng khám</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $visits, 'index' => $loop->index])
                            <td>{{ $visit->checked_in_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>@include('admin.layouts.partials.record-code', ['code' => $visit->visit_code])</td>
                            <td>
                                @if($visit->patient)
                                    <a href="{{ route('admin.patients.show', $visit->patient) }}#history">{{ $visit->patient->full_name }}</a>
                                @else
                                    Chưa cập nhật
                                @endif
                            </td>
                            <td>{{ $visit->doctor?->full_name ?? 'Chưa cập nhật' }}</td>
                            <td>{{ $visit->specialty?->name ?? 'Chưa cập nhật' }}</td>
                            <td>{{ $visit->examinationRoom?->name ?? 'Chưa cập nhật' }}</td>
                            <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $visit->status?->name])</td>
                            <td>
                                <div class="admin-actions admin-table-actions">
                                    <a class="admin-btn admin-btn-detail" href="{{ route('admin.visits.show', [$visit, 'from_medical_history' => '1']) }}">Chi tiết</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="admin-empty">Không tìm thấy lượt khám phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $visits->links() }}
    </section>
@endsection
