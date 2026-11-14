@extends('doctor.layout')
@section('title', 'Danh sách lượt khám')
@section('content')
<section class="employee-welcome"><div class="employee-welcome__copy">
    <p class="portal-eyebrow">KHU VỰC BÁC SĨ</p><h1>Xin chào, {{ $doctor->full_name }}</h1>
    <p>Theo dõi các lượt khám trong hệ thống.</p>
    <span class="employee-welcome__identity"><x-employee-icon name="shield" /> Bác sĩ · {{ $doctor->doctor_code }}</span>
</div><div class="employee-welcome__art" aria-hidden="true"><div class="employee-welcome__ring"></div><span><x-employee-icon name="reception" /></span><i></i></div></section>
<section class="employee-section"><div class="employee-section__heading"><div><p class="portal-eyebrow">KHÔNG GIAN LÀM VIỆC</p><h2>Danh sách lượt khám</h2><p>Chọn lượt khám để xem thông tin và hồ sơ liên quan.</p></div></div>
    <form method="get" action="{{ route('doctor.visits.index') }}" class="employee-card workspace-filter workspace-filter--spaced" aria-label="Lọc lượt khám">
        <label class="workspace-filter__field workspace-filter__field--search" for="visit-search">Tìm lượt khám
            <input id="visit-search" name="q" type="search" maxlength="100" value="{{ old('q', $filters['q'] ?? '') }}" placeholder="Tên, mã bệnh nhân hoặc mã lượt khám">
        </label>
        <label class="workspace-filter__field" for="visit-from">Từ ngày
            <input id="visit-from" type="date" name="date_from" value="{{ old('date_from', $filters['date_from'] ?? '') }}">
        </label>
        <label class="workspace-filter__field" for="visit-to">Đến ngày
            <input id="visit-to" type="date" name="date_to" value="{{ old('date_to', $filters['date_to'] ?? '') }}">
        </label>
        <label class="workspace-filter__field" for="visit-status">Trạng thái
            <select id="visit-status" name="status">
                <option value="">Tất cả</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}" @selected((string) old('status', $filters['status'] ?? '') === (string) $status->id)>
                        {{ $status->description ?: $status->name }}{{ $status->is_active ? '' : ' (ngừng sử dụng)' }}
                    </option>
                @endforeach
            </select>
        </label>
        <x-workspace-filter-actions :reset-url="route('doctor.visits.index')" />
    </form>
    <div class="tech-panel">
        @forelse($visits as $visit)
            <article class="employee-work doctor-visit-list__item"><span class="employee-work__icon" aria-hidden="true"><x-employee-icon name="user" /></span><div class="doctor-visit-list__info">
                <h3>{{ $visit->patient?->full_name ?? 'Chưa cập nhật' }}</h3>
                <dl class="doctor-visit-list__facts">
                    <div><dt>Mã lượt khám</dt><dd>{{ $visit->visit_code }}</dd></div>
                    <div><dt>Chuyên khoa</dt><dd>{{ $visit->specialty?->name ?? 'Chưa có chuyên khoa' }}</dd></div>
                    <div><dt>Phòng khám</dt><dd>{{ $visit->examinationRoom?->name ?? 'Chưa có phòng' }}</dd></div>
                    <div><dt>Tiếp nhận</dt><dd>{{ $visit->checked_in_at?->format('d/m/Y H:i') ?? 'Chưa tiếp nhận' }}</dd></div>
                </dl>
                <a class="employee-card__link" href="{{ route('doctor.visits.show', $visit->id) }}">Xem chi tiết <x-employee-icon name="arrow" /></a>
            </div>@include('doctor.visits._status', ['visit' => $visit])</article>
        @empty
            <p>{{ $hasVisits ? 'Không tìm thấy lượt khám phù hợp bộ lọc.' : 'Chưa có lượt khám nào.' }}</p>
        @endforelse
        {{ $visits->links() }}
    </div>
</section>
@endsection
