@extends('admin.layouts.app')

@section('title', 'Giờ làm việc mặc định - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.doctor-schedules.month.index') }}">Lịch làm việc bác sĩ</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Giờ làm việc mặc định</strong>
@endsection

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Giờ làm việc mặc định</h1>
            <p class="page-description">Giờ làm việc áp dụng cho ca mới và cập nhật các ca sáng, chiều đã phân từ hôm nay. Hệ thống sẽ báo các ca cần giữ giờ cũ để bảo vệ lịch hẹn, lượt đang khám và phân công phòng.</p>
        </div>
        <a class="admin-btn admin-btn-secondary" href="{{ route('admin.doctor-schedules.month.index') }}">Quay lại</a>
    </div>

    @if (session('shift_hours_skipped_count'))
        <div class="alert alert-error" role="status">
            <p><strong>{{ session('shift_hours_skipped_count') }} ca vẫn dùng giờ cũ</strong></p>
            <ul class="admin-alert-list">
                @foreach (session('shift_hours_skipped', []) as $shift)
                    <li>{{ $shift['date'] }} · {{ $shift['doctor'] }} · {{ $shift['shift'] }}. {{ $shift['reason'] }}</li>
                @endforeach
            </ul>
            @if (session('shift_hours_skipped_count') > 10)
                <p>Đang hiển thị 10 ca đầu tiên.</p>
            @endif
        </div>
    @endif

    <form class="form-grid" method="POST" action="{{ route('admin.doctor-schedules.month.shift-hours.update') }}">
        @csrf
        @method('PUT')
        @foreach(['morning' => ['MORNING', 'Ca sáng'], 'afternoon' => ['AFTERNOON', 'Ca chiều']] as $key => [$code, $label])
            <section class="admin-card schedule-hours-group" aria-labelledby="{{ $key }}-shift-title">
                <h2 id="{{ $key }}-shift-title">{{ $label }}</h2>
                @foreach(['start' => 'Giờ bắt đầu', 'end' => 'Giờ kết thúc'] as $edge => $timeLabel)
                    @php($field = $key.'_'.$edge)
                    <div class="form-group">
                        <label for="{{ $field }}">{{ $timeLabel }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="time" required value="{{ old($field, substr($shiftHours[$code][$edge], 0, 5)) }}" @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @error($field)<p id="{{ $field }}-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </section>
        @endforeach
        <div class="form-actions schedule-hours-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Lưu giờ làm việc</button>
        </div>
    </form>
@endsection
