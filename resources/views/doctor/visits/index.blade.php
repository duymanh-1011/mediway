@extends('doctor.layout')
@section('title', 'Lượt khám của tôi')
@section('content')
<section class="employee-welcome"><div class="employee-welcome__copy">
    <p class="portal-eyebrow">KHU VỰC BÁC SĨ</p><h1>Xin chào, {{ $doctor->full_name }}</h1>
    <p>Theo dõi các lượt khám được phân công cho bạn.</p>
    <span class="employee-welcome__identity"><x-employee-icon name="shield" /> Bác sĩ · {{ $doctor->doctor_code }}</span>
</div><div class="employee-welcome__art" aria-hidden="true"><div class="employee-welcome__ring"></div><span><x-employee-icon name="reception" /></span><i></i></div></section>
<section class="employee-section"><div class="employee-section__heading"><div><p class="portal-eyebrow">KHÔNG GIAN LÀM VIỆC</p><h2>Lượt khám của tôi</h2><p>Chọn lượt khám để xem thông tin và hồ sơ liên quan.</p></div></div>
    <div class="tech-panel">
        @forelse($visits as $visit)
            <div class="employee-work"><span class="employee-work__icon"><x-employee-icon name="user" /></span><div>
                <h3>{{ $visit->patient?->full_name ?? 'Chưa cập nhật' }}</h3>
                <p>{{ $visit->visit_code }} · {{ $visit->specialty?->name ?? 'Chưa có chuyên khoa' }} · {{ $visit->examinationRoom?->name ?? 'Chưa có phòng' }}</p>
                <p>{{ $visit->checked_in_at?->format('d/m/Y H:i') ?? 'Chưa tiếp nhận' }} · {{ $visit->status?->description ?? 'Chưa cập nhật trạng thái' }}</p>
                <a class="employee-card__link" href="{{ route('doctor.visits.show', $visit->id) }}">Xem chi tiết <x-employee-icon name="arrow" /></a>
            </div></div>
        @empty
            <p>Chưa có lượt khám nào được phân công cho bạn.</p>
        @endforelse
        {{ $visits->links() }}
    </div>
</section>
@endsection
