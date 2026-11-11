@extends('admin.layouts.app')

@section('title', 'Lịch làm việc bác sĩ')

@section('breadcrumb')
    <strong aria-current="page">Lịch làm việc bác sĩ</strong>
@endsection

@section('content')
    <section class="admin-card">
        <h1 class="page-title">Lịch làm việc bác sĩ</h1>
        <form class="admin-filter" method="GET" action="{{ route('admin.doctor-schedules.month.index') }}">
            <div><label for="keyword">Tên hoặc mã bác sĩ</label>
            <input id="keyword" name="keyword" value="{{ $keyword }}" maxlength="100"></div>
            <div class="admin-actions">
                <button type="submit" class="admin-btn admin-btn-primary">Tìm kiếm</button>
                <a class="admin-btn admin-btn-secondary" href="{{ route('admin.doctor-schedules.month.index') }}">Xóa bộ lọc</a>
                <a class="admin-btn admin-btn-secondary" href="{{ route('admin.doctor-schedules.index') }}">Tra cứu lịch cũ</a>
            </div>
        </form>
    </section>
    <section class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>Mã bác sĩ</th><th>Họ và tên</th><th>Chuyên khoa</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse($doctors as $doctor)
                        <tr>
                            <td>{{ $doctor->doctor_code }}</td><td>{{ $doctor->full_name }}</td>
                            <td>{{ $doctor->specialty?->name ?? 'Chưa phân công' }}</td>
                            <td><a class="btn btn-primary" href="{{ route('admin.doctor-schedules.month.show', $doctor) }}">Xem lịch tháng</a></td>
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
