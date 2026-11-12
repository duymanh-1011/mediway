@extends('admin.layouts.app')

@section('title', 'Xem trước lịch từ Excel')

@section('breadcrumb')
    <a href="{{ route('admin.doctor-schedules.month.index') }}">Lịch làm việc bác sĩ</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Xem trước lịch Excel</strong>
@endsection

@section('content')
    <section class="admin-card">
        <a class="btn btn-secondary" href="{{ route('admin.doctor-schedules.month.show', [$doctor, 'month' => $draft['month']]) }}">← Lịch tháng</a>
        <h1 class="page-title">Xem trước lịch từ Excel</h1>
        <p>{{ $doctor->full_name }} · {{ $draft['month'] }}</p>
        <p>Chọn chuyên khoa và phòng cho các ca Làm việc cần phân công. Những ngày không có trong file giữ nguyên lịch hiện tại.</p>
        @foreach($errors->all() as $error)<p class="field-error" role="alert">{{ $error }}</p>@endforeach
    </section>
    <section class="admin-card">
        <form method="POST" action="{{ route('admin.doctor-schedules.month.import.assignments', [$doctor, $token]) }}">
            @csrf
            @method('PUT')
            <div class="table-responsive">
                <table class="admin-table">
                    <thead><tr><th>Ngày</th><th>Ca</th><th>Hiện tại</th><th>Trong file</th><th>Phân công</th><th>Kết quả kiểm tra</th></tr></thead>
                    <tbody>
                        @foreach($draft['rows'] as $index => $row)
                            @php($labels = ['WORKING' => 'Làm việc', 'OFF' => 'Nghỉ', 'UNCONFIGURED' => 'Chưa cấu hình'])
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                <td>{{ $row['code'] === 'MORNING' ? 'Sáng' : 'Chiều' }}</td>
                                <td>{{ $labels[$row['before']] }}</td>
                                <td>{{ $labels[$row['state']] }}</td>
                                <td>
                                    @if($row['state'] === 'WORKING')
                                        @include('admin.doctor-schedules._month-assignment', [
                                            'prefix' => 'preview-'.$index, 'specialtyName' => "assignments[$index][specialty_id]",
                                            'roomName' => "assignments[$index][room_id]", 'specialtyId' => $row['specialty_id'], 'roomId' => $row['room_id'],
                                        ])
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @forelse($issues[$index] ?? [] as $issue)
                                        <p class="field-error">{{ $issue }}</p>
                                    @empty
                                        Hợp lệ
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(collect($draft['rows'])->contains('state', 'WORKING'))
                <div class="mt-4"><button class="btn btn-secondary" type="submit">Kiểm tra phân công</button></div>
            @endif
        </form>
        @if($issues === [])
            <form method="POST" action="{{ route('admin.doctor-schedules.month.import.confirm', [$doctor, $token]) }}">
                @csrf
                <input type="hidden" name="revision" value="{{ $revision }}">
                <div class="mt-4"><button class="btn btn-primary" type="submit">Xác nhận nhập lịch</button></div>
            </form>
        @endif
    </section>
@endsection
