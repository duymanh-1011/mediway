@extends('admin.layouts.app')

@section('title', 'Lịch làm việc bác sĩ')

@section('breadcrumb')
    <strong aria-current="page">Lịch làm việc bác sĩ</strong>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Lịch làm việc bác sĩ</h1>
        <div class="header-actions">
            <a class="admin-btn admin-btn-primary" href="{{ route('admin.doctor-schedules.month.shift-hours.edit') }}">Cấu hình giờ làm việc</a>
        </div>
    </div>
    @component('admin.layouts.partials.filter-panel', ['title' => 'Bộ lọc bác sĩ', 'description' => 'Lọc theo họ tên hoặc mã bác sĩ.'])
        <form class="admin-filter-form" data-filter-fields="1" method="GET" action="{{ route('admin.doctor-schedules.month.index') }}">
            @if($month)
                <input type="hidden" name="month" value="{{ $month }}">
            @endif
            <div class="admin-filter-field">
                <label for="keyword">Tên hoặc mã bác sĩ</label>
                <input type="text" id="keyword" name="keyword" value="{{ $keyword }}" maxlength="100">
            </div>
            @include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.doctor-schedules.month.index', ['month' => $month])])
        </form>
    @endcomponent
    <section class="card admin-list-panel">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách bác sĩ'])
        <div class="admin-list-table-wrap">
            <table class="admin-table admin-list-table schedule-doctor-table" data-mobile-records>
                <thead>
                    <tr>
                        <th scope="col">Mã bác sĩ</th>
                        <th scope="col">Họ và tên</th>
                        <th scope="col">Chuyên khoa</th>
                        <th scope="col" class="schedule-doctor-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $doctor)
                        <tr>
                            <td>@include('admin.layouts.partials.record-code', ['code' => $doctor->doctor_code])</td><td>{{ $doctor->full_name }}</td>
                            <td>{{ $doctor->specialty?->name ?? 'Chưa phân công' }}</td>
                            <td class="schedule-doctor-actions">
                                <a class="admin-btn admin-btn-detail" href="{{ route('admin.doctor-schedules.month.show', [$doctor, 'month' => $month]) }}">Xem lịch làm việc bác sĩ</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Không tìm thấy bác sĩ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $doctors->links('admin.layouts.partials.pagination') }}
    </section>
@endsection
