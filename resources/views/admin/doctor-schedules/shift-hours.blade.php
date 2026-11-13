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
            <p class="page-description">Giờ này áp dụng khi xếp ca mới hoặc nhập ca mới từ Excel. Các ca đã xếp giữ giờ đã lưu.</p>
        </div>
        <a class="admin-btn admin-btn-secondary" href="{{ route('admin.doctor-schedules.month.index') }}">Quay lại</a>
    </div>

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
