
@extends('admin.layouts.app')
@section('admin-page', 'doctors-show')

@section('title', 'Chi tiết bác sĩ - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bác sĩ</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chi tiết</strong>
@endsection

@section('content')
@php
    $detail = $doctor->detail;
    $user = $doctor->user;

    $personal = [
        'Số CCCD' => $detail?->citizen_id_number,
        'Ngày cấp CCCD' => $detail?->citizen_id_issue_date?->format('d/m/Y'),
        'Nơi cấp CCCD' => $detail?->citizen_id_issue_place,
        'Email' => $detail?->email,
        'Quê quán' => $detail?->hometown,
        'Địa chỉ thường trú' => $detail?->permanent_address,
        'Địa chỉ hiện tại' => $detail?->current_address,
        'Quốc tịch' => $detail?->nationality,
        'Dân tộc' => $detail?->ethnicity,
    ];

    $professional = [
        'Trình độ học vấn' => $detail?->education_level,
        'Trình độ chuyên môn' => $detail?->professional_qualification,
        'Học hàm / Học vị' => $detail?->academic_title_degree,
        'Chức danh chuyên môn' => $detail?->professional_title,
        'Chức vụ hiện tại' => $detail?->current_position,
        'Nơi làm việc' => $detail?->current_workplace,
        'Ngày bắt đầu làm việc' => $detail?->work_start_date?->format('d/m/Y'),
        'Số năm kinh nghiệm' => $detail?->years_of_experience,
        'Lĩnh vực chuyên môn' => $detail?->specialized_fields,
        'Kỹ năng chuyên môn' => $detail?->professional_skills,
        'Ngoại ngữ' => $detail?->languages,
    ];

    $license = [
        'Số giấy phép' => $detail?->practice_license_number,
        'Ngày cấp' => $detail?->practice_license_issue_date?->format('d/m/Y'),
        'Nơi cấp' => $detail?->practice_license_issue_place,
        'Ngày hết hạn' => $detail?->practice_license_expiry_date?->format('d/m/Y'),
        'Phạm vi hành nghề' => $detail?->practice_scope,
    ];

    $documents = [
        ['label' => 'Ảnh đại diện', 'column' => 'avatar_path', 'type' => 'avatar', 'image' => true],
        ['label' => 'CCCD mặt trước', 'column' => 'citizen_id_front_path', 'type' => 'citizen-id-front', 'image' => true],
        ['label' => 'CCCD mặt sau', 'column' => 'citizen_id_back_path', 'type' => 'citizen-id-back', 'image' => true],
        ['label' => 'Giấy phép hành nghề', 'column' => 'practice_license_file_path', 'type' => 'practice-license', 'image' => false],
        ['label' => 'Hồ sơ PDF', 'column' => 'profile_pdf_path', 'type' => 'profile-pdf', 'image' => false],
    ];
@endphp

<div class="doctor-detail-page">
    <div class="ds-heading">
        <div>
            <h1 class="page-title">{{ $doctor->full_name }}</h1>
            <p class="ds-description">
                {{ $doctor->doctor_code }}
                <span class="ds-status {{ $doctor->status === 'ACTIVE' ? 'ds-active' : 'ds-inactive' }}">
                    {{ $doctor->status === 'ACTIVE' ? 'Hoạt động' : 'Không hoạt động' }}
                </span>
            </p>
        </div>

        <div class="ds-actions">
            <a href="{{ route('admin.doctors.index') }}" class="ds-btn ds-light">← Danh sách</a>
            <a href="{{ route('admin.doctors.edit', $doctor) }}" class="ds-btn ds-primary">
                Chỉnh sửa hồ sơ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="ds-success">{{ session('success') }}</div>
    @endif

    <div class="ds-tabs" role="tablist" aria-label="Hồ sơ bác sĩ">
        <button type="button" class="ds-tab active" data-tab="overview" role="tab">Tổng quan</button>
        <button type="button" class="ds-tab" data-tab="personal" role="tab">Cá nhân</button>
        <button type="button" class="ds-tab" data-tab="professional" role="tab">Chuyên môn</button>
        <button type="button" class="ds-tab" data-tab="license" role="tab">Giấy phép</button>
        <button type="button" class="ds-tab" data-tab="documents" role="tab">Tài liệu</button>
    </div>

    <div class="ds-panel" data-panel="overview">
        <div class="ds-two">
            <section class="ds-card">
                <h2>Thông tin cơ bản</h2>
                <dl class="ds-list">
                    <div><dt>Mã bác sĩ</dt><dd>{{ $doctor->doctor_code }}</dd></div>
                    <div><dt>Họ tên</dt><dd>{{ $doctor->full_name }}</dd></div>
                    <div><dt>Ngày sinh</dt><dd>{{ $doctor->date_of_birth?->format('d/m/Y') ?? '-' }}</dd></div>
                    <div><dt>Giới tính</dt><dd>{{ $doctor->gender ?? '-' }}</dd></div>
                    <div><dt>Trạng thái hồ sơ</dt><dd>{{ \App\Models\AdminDisplay::label($doctor->status) }}</dd></div>
                    <div><dt>Ngày tạo</dt><dd>{{ $doctor->created_at?->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </section>

            <section class="ds-card">
                <h2>Tài khoản liên kết</h2>
                <dl class="ds-list">
                    <div><dt>Mã tài khoản</dt><dd>{{ $user?->business_code ?? '-' }}</dd></div>
                    <div><dt>Số điện thoại</dt><dd>{{ $user?->phone ?? '-' }}</dd></div>
                    <div><dt>Vai trò</dt><dd>{{ \App\Models\AdminDisplay::label($user?->role?->name ?? '-') }}</dd></div>
                    <div><dt>Trạng thái tài khoản</dt><dd>{{ \App\Models\AdminDisplay::label($user?->status ?? '-') }}</dd></div>
                </dl>

                @if($user)
                    <a href="{{ route('admin.users.show', $user) }}" class="ds-btn ds-light ds-account-link">
                        Xem tài khoản
                    </a>
                @endif
            </section>
        </div>

        <section class="ds-card ds-section">
            <h2>Giới thiệu chuyên môn</h2>
            <p class="ds-long">{{ $detail?->professional_bio ?: 'Chưa cập nhật.' }}</p>
        </section>

        <section class="ds-card ds-section">
            <h2>Liên kết lịch làm việc và lượt khám</h2>
            <p class="ds-help">
                Phần liên kết lịch làm việc, lịch hẹn và lượt khám sẽ được
                tích hợp sau khi các module của nhóm hoàn thành và có quan hệ
                dữ liệu chính thức. Không chỉnh sửa dữ liệu lịch sử tại đây.
            </p>
        </section>
    </div>

    @foreach([
        'personal' => ['title' => 'Thông tin cá nhân', 'values' => $personal],
        'professional' => ['title' => 'Hồ sơ chuyên môn', 'values' => $professional],
        'license' => ['title' => 'Giấy phép hành nghề', 'values' => $license],
    ] as $tabName => $section)
        <div class="ds-panel" data-panel="{{ $tabName }}" hidden>
            <section class="ds-card ds-section">
                <h2>{{ $section['title'] }}</h2>
                <dl class="ds-information">
                    @foreach($section['values'] as $label => $value)
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value === null || $value === '' ? 'Chưa cập nhật' : $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if($tabName === 'professional')
                <section class="ds-card ds-section">
                    <h2>Giới thiệu chuyên môn</h2>
                    <p class="ds-long">{{ $detail?->professional_bio ?: 'Chưa cập nhật.' }}</p>
                </section>
            @endif

            @if($tabName === 'personal')
                <section class="ds-card ds-section">
                    <h2>Ghi chú</h2>
                    <p class="ds-long">{{ $detail?->notes ?: 'Chưa có ghi chú.' }}</p>
                </section>
            @endif
        </div>
    @endforeach

    <div class="ds-panel" data-panel="documents" hidden>
        <section class="ds-card ds-section">
            <div class="ds-section-heading">
                <h2>Tài liệu đính kèm</h2>
                <a href="{{ route('admin.doctors.edit', $doctor) }}" class="ds-btn ds-light">
                    Cập nhật tài liệu
                </a>
            </div>

            <div class="ds-documents">
                @foreach($documents as $document)
                    @php
                        $hasFile = $detail && filled($detail->{$document['column']});
                        $url = $hasFile
                            ? route('admin.doctors.document', [$doctor, $document['type']])
                            : null;
                    @endphp

                    <div class="ds-document">
                        <strong>{{ $document['label'] }}</strong>

                        @if($hasFile)
                            @if($document['image'])
                                <img src="{{ $url }}" alt="{{ $document['label'] }}" loading="lazy">
                            @else
                                <div class="ds-file-placeholder">TÀI LIỆU</div>
                            @endif

                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                                Xem tài liệu
                            </a>
                        @else
                            <div class="ds-no-file">Chưa có tài liệu</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.ds-tab[data-tab]');
    const panels = document.querySelectorAll('.ds-panel[data-panel]');
    const valid = ['overview', 'personal', 'professional', 'license', 'documents'];

    function activate(name) {
        if (!valid.includes(name)) {
            name = 'overview';
        }

        tabs.forEach(function (tab) {
            const active = tab.dataset.tab === name;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            panel.hidden = panel.dataset.panel !== name;
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const name = tab.dataset.tab;
            activate(name);
            history.replaceState(null, '', '#' + name);
        });
    });

    activate(window.location.hash.replace('#', ''));

    window.addEventListener('hashchange', function () {
        activate(window.location.hash.replace('#', ''));
    });
});
</script>
@endpush
