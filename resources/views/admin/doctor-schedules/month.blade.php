@extends('admin.layouts.app')

@section('title', 'Lịch tháng của bác sĩ')

@section('breadcrumb')
    <a href="{{ route('admin.doctor-schedules.month.index') }}">Lịch làm việc bác sĩ</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lịch tháng</strong>
@endsection

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Lịch làm việc · {{ $doctor->full_name }}</h1>
            <p class="page-description">{{ $doctor->doctor_code }}{{ $doctor->specialty ? ' · '.$doctor->specialty->name : '' }}</p>
        </div>
        <div class="header-actions">
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.doctor-schedules.month.index', ['month' => $month]) }}">Danh sách bác sĩ</a>
        </div>
    </div>

    <section class="admin-card">
        <form class="admin-filter-form" data-filter-fields="1" method="GET" action="{{ route('admin.doctor-schedules.month.show', $doctor) }}">
            <div class="admin-filter-field">
                <label for="month">Tháng làm việc</label>
                <input id="month" name="month" type="month" value="{{ $month }}" required>
            </div>
            <div class="admin-filter-actions"><button type="submit" class="admin-btn admin-btn-primary">Xem tháng</button></div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Nhập lịch từ Excel</h2>
        <p class="form-help">Điền mã phòng khám cho từng ca làm. Chọn GIỮ NGUYÊN nếu muốn giữ ca đã xếp.</p>
        <form class="admin-filter-form" data-filter-fields="{{ $rooms->count() > 1 ? '2' : '1' }}" data-schedule-upload method="POST" enctype="multipart/form-data" action="{{ route('admin.doctor-schedules.month.import.apply', $doctor) }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <div class="admin-filter-field">
                <span id="workbook-label" class="form-label">Tệp lịch tháng {{ \Illuminate\Support\Carbon::parse($month.'-01')->format('m/Y') }}</span>
                <div class="schedule-upload-picker" role="group" aria-labelledby="workbook-label">
                    <input id="workbook" name="workbook" type="file" accept=".xlsx" hidden>
                    <button type="button" class="admin-btn admin-btn-secondary" data-select-workbook aria-describedby="workbook-name">Chọn tệp Excel</button>
                    <span id="workbook-name" aria-live="polite">Chưa chọn tệp</span>
                </div>
            </div>
            @if($rooms->count() > 1)
                <div class="admin-filter-field">
                    <label for="import-room">Phòng mặc định cho tệp mẫu cũ</label>
                    <select id="import-room" name="room_id" data-lookup-native>
                        <option value="">Tự chọn nếu chỉ có một phòng phù hợp</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected((int) old('room_id') === $room->id)>{{ $room->room_code }} · {{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="admin-filter-actions">
                <button class="admin-btn admin-btn-primary" type="submit" data-apply-workbook disabled>Xác nhận nhập lịch</button>
                <a class="admin-btn admin-btn-secondary" href="{{ route('admin.doctor-schedules.month.template', ['month' => $month]) }}">Tải mẫu Excel</a>
            </div>
        </form>
    </section>

    <section class="card admin-list-panel">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Phân công theo ngày'])
        <div class="admin-list-table-wrap">
            <table class="admin-table admin-list-table schedule-month-table" data-mobile-records>
                <thead><tr><th scope="col">Ngày</th><th scope="col">Ca sáng</th><th scope="col">Ca chiều</th></tr></thead>
                <tbody>
                    @foreach($days as $date => $day)
                        <tr>
                            <td>
                                <span class="whitespace-nowrap font-semibold">{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</span>
                                @foreach($day['legacy_schedules'] as $savedSchedule)
                                    <div class="mt-1">
                                        <a href="{{ route('admin.doctor-schedules.show', $savedSchedule) }}" class="admin-btn admin-btn-detail">
                                            {{ substr($savedSchedule->start_time, 0, 5) }}–{{ substr($savedSchedule->end_time, 0, 5) }}
                                        </a>
                                        @if($savedSchedule->examinationRoom)
                                            <span class="schedule-shift-room">{{ $savedSchedule->examinationRoom->name }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            @foreach($day['shifts'] as $code => $cell)
                                <td>
                                    @php($prefix = 'shift-'.$date.'-'.$code)
                                    <details>
                                        <summary class="cursor-pointer">
                                            <span class="admin-badge {{ ['WORKING' => 'admin-badge-success', 'OFF' => 'admin-badge-warning', 'UNCONFIGURED' => 'admin-badge-neutral'][$cell['state']] }}">{{ ['WORKING' => 'Làm việc', 'OFF' => 'Nghỉ', 'UNCONFIGURED' => 'Chưa cấu hình'][$cell['state']] }}</span>
                                            @if($cell['state'] === 'WORKING' && $cell['schedule']->examinationRoom)
                                                <span class="schedule-shift-room">{{ $cell['schedule']->examinationRoom->room_code }} · {{ $cell['schedule']->examinationRoom->name }}</span>
                                            @endif
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-schedule-upload]');
            const input = form.querySelector('#workbook');
            const filename = form.querySelector('#workbook-name');
            const confirm = form.querySelector('[data-apply-workbook]');
            const sync = () => {
                const file = input.files[0];
                filename.textContent = file?.name ?? 'Chưa chọn tệp';
                confirm.disabled = !file;
            };
            form.querySelector('[data-select-workbook]').addEventListener('click', () => input.click());
            input.addEventListener('change', sync);
            window.addEventListener('pageshow', sync);
            sync();
        })();
    </script>
@endpush
