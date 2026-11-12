@extends('admin.layouts.app')

@section('title', 'Lịch tháng của bác sĩ')

@section('breadcrumb')
    <a href="{{ route('admin.doctor-schedules.month.index') }}">Lịch làm việc bác sĩ</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lịch tháng</strong>
@endsection

@section('content')
    <section class="admin-card">
        <a class="btn btn-secondary" href="{{ route('admin.doctor-schedules.month.index') }}">← Danh sách bác sĩ</a>
        <h1 class="page-title">Lịch tháng · {{ $doctor->full_name }}</h1>
        <form class="admin-filter" method="GET" action="{{ route('admin.doctor-schedules.month.show', $doctor) }}">
            <div><label for="month">Tháng làm việc</label>
            <input id="month" name="month" type="month" value="{{ $month }}" required></div>
            <div><button type="submit" class="btn btn-primary">Xem tháng</button></div>
        </form>
    </section>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="admin-card">
        <h2>Nhập lịch từ Excel</h2>
        <form class="admin-filter" method="POST" enctype="multipart/form-data" action="{{ route('admin.doctor-schedules.month.import.preview', $doctor) }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <div><label for="workbook">File lịch tháng {{ \Illuminate\Support\Carbon::parse($month.'-01')->format('m/Y') }}</label>
            <input id="workbook" name="workbook" type="file" accept=".xlsx" required></div>
            <div><button class="btn btn-primary" type="submit">Xem trước</button>
            <a class="btn btn-secondary" href="{{ route('admin.doctor-schedules.month.template') }}">Tải mẫu Excel</a></div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Phân công theo ngày</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>Ngày</th><th>Ca sáng</th><th>Ca chiều</th><th>Lịch cũ</th></tr></thead>
                <tbody>
                    @foreach($days as $date => $day)
                        <tr>
                            <td class="whitespace-nowrap font-semibold">{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</td>
                            @foreach($day['shifts'] as $code => $cell)
                                <td>
                                    @php($prefix = 'shift-'.$date.'-'.$code)
                                    <details>
                                        <summary class="cursor-pointer">
                                            <span class="admin-badge {{ ['WORKING' => 'admin-badge-success', 'OFF' => 'admin-badge-warning', 'UNCONFIGURED' => 'admin-badge-neutral'][$cell['state']] }}">{{ ['WORKING' => 'Làm việc', 'OFF' => 'Nghỉ', 'UNCONFIGURED' => 'Chưa cấu hình'][$cell['state']] }}</span>
                                        </summary>
                                        <p class="my-2">{{ substr($cell['start'], 0, 5) }}–{{ substr($cell['end'], 0, 5) }}</p>
                                        @if($cell['state'] === 'WORKING')
                                            <p class="my-2"><a href="{{ route('admin.doctor-schedules.show', $cell['schedule']) }}">Xem khung giờ và lượt đăng ký</a></p>
                                        @endif
                                        <form method="POST" action="{{ route('admin.doctor-schedules.month.update', $doctor) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="date" value="{{ $date }}">
                                            <input type="hidden" name="shift" value="{{ $code }}">
                                            <input type="hidden" name="revision" value="{{ $cell['revision'] }}">
                                            <div class="form-group"><label for="{{ $prefix }}-state">Trạng thái</label>
                                            <select id="{{ $prefix }}-state" name="state" required>
                                                <option value="" @selected($cell['state'] === 'UNCONFIGURED') disabled>Chọn trạng thái</option>
                                                <option value="WORKING" @selected($cell['state'] === 'WORKING')>Làm việc</option>
                                                <option value="OFF" @selected($cell['state'] === 'OFF')>Nghỉ</option>
                                            </select></div>
                                            @include('admin.doctor-schedules._month-assignment', [
                                                'prefix' => $prefix, 'specialtyName' => 'specialty_id', 'roomName' => 'room_id',
                                                'specialtyId' => $cell['schedule']?->specialty_id, 'roomId' => $cell['schedule']?->examination_room_id,
                                            ])
                                            <button type="submit" class="btn btn-primary">Lưu ca</button>
                                        </form>
                                    </details>
                                </td>
                            @endforeach
                            <td>
                                @forelse($day['legacy_schedules'] as $legacy)
                                    <div><a href="{{ route('admin.doctor-schedules.show', $legacy) }}">{{ substr($legacy->start_time, 0, 5) }}–{{ substr($legacy->end_time, 0, 5) }}</a>
                                        · {{ $legacy->examinationRoom?->name }}</div>
                                @empty
                                    —
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
