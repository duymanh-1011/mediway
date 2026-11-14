@extends('doctor.layout')
@section('title', 'Tổng quan')

@section('content')
    @php
        $examination = $workspace === 'examination';
        $position = $examination ? 'Bác sĩ khám bệnh' : 'Bác sĩ thực hiện dịch vụ';
        $work = $examination
            ? ['title' => 'Phòng khám và hàng đợi', 'description' => 'Xem phòng khám và theo dõi lượt chờ theo từng phòng.', 'url' => route('doctor.examination.rooms.index'), 'icon' => 'reception']
            : ['title' => 'Nhóm dịch vụ', 'description' => 'Mở nhóm dịch vụ để nhận chỉ định, nhập kết quả và xác nhận hoàn tất.', 'url' => route('doctor.services.index'), 'icon' => 'services'];
    @endphp
    <x-workspace-overview
        :name="$doctor->full_name" :position="$position" :code="$doctor->doctor_code" code-label="Mã bác sĩ"
        profile-title="Thông tin bác sĩ" :profile-url="route('doctor.profile')"
        :facts="['Mã bác sĩ' => $doctor->doctor_code, 'Vị trí công việc' => $position, 'Chuyên khoa' => $doctor->specialty?->name ?? 'Chưa cập nhật', 'Trạng thái phiên' => 'Đang đăng nhập']"
        :work="[
            'heading' => 'Lịch làm việc',
            'description' => 'Xem ngày làm việc, ca và phòng được phân công.',
            'url' => route('doctor.schedule'),
            'icon' => 'calendar',
        ]"
        :welcome-icon="$work['icon']"
    />

    <section class="employee-section" aria-labelledby="doctor-work-title">
        <div class="employee-section__heading"><h2 id="doctor-work-title">Chức năng</h2></div>
        <a class="employee-card workspace-work-card doctor-dashboard-action" href="{{ $work['url'] }}">
            <span class="employee-card__icon"><x-employee-icon :name="$work['icon']" /></span>
            <div class="workspace-work-card__content">
                <h3>{{ $work['title'] }}</h3>
                <p>{{ $work['description'] }}</p>
            </div>
        </a>
    </section>
@endsection
