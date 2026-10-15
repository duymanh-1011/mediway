
@extends('admin.layouts.app')

@section('title', 'Chi tiết bác sĩ - Mediway')

@section('breadcrumb')
    Admin / Nhân sự & bệnh nhân / Bác sĩ / Chi tiết
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
                    <div><dt>Trạng thái hồ sơ</dt><dd>{{ $doctor->status }}</dd></div>
                    <div><dt>Ngày tạo</dt><dd>{{ $doctor->created_at?->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </section>

            <section class="ds-card">
                <h2>Tài khoản liên kết</h2>
                <dl class="ds-list">
                    <div><dt>Mã tài khoản</dt><dd>{{ $user?->business_code ?? '-' }}</dd></div>
                    <div><dt>Số điện thoại</dt><dd>{{ $user?->phone ?? '-' }}</dd></div>
                    <div><dt>Vai trò</dt><dd>{{ $user?->role?->name ?? '-' }}</dd></div>
                    <div><dt>Trạng thái tài khoản</dt><dd>{{ $user?->status ?? '-' }}</dd></div>
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

@push('styles')
<style>
.doctor-detail-page{min-width:0}
.ds-heading{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:22px}
.ds-description{display:flex;align-items:center;gap:12px;color:#64748b;font-size:13px;margin:7px 0 0}
.ds-actions{display:flex;gap:10px;flex-wrap:wrap}
.ds-btn{display:inline-flex;align-items:center;justify-content:center;min-height:41px;padding:10px 15px;border:1px solid transparent;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none}
.ds-primary{background:#2563eb;color:#fff}
.ds-light{background:#fff;border-color:#cbd5e1;color:#334155}
.ds-status{padding:6px 10px;border-radius:20px;font-size:11px;font-weight:700}
.ds-active{background:#dcfce7;color:#166534}
.ds-inactive{background:#f1f5f9;color:#475569}
.ds-tabs{display:flex;gap:8px;border-bottom:1px solid #e2e8f0;overflow-x:auto;margin-bottom:22px}
.ds-tab{border:0;border-bottom:3px solid transparent;background:transparent;padding:13px 17px;font-size:13px;font-weight:700;color:#64748b;white-space:nowrap;cursor:pointer;font-family:inherit}
.ds-tab.active{color:#1d4ed8;border-bottom-color:#2563eb}
.ds-panel[hidden]{display:none!important}
.ds-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:18px}
.ds-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:23px;min-width:0;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.ds-card h2{font-size:17px;color:#0f172a;margin:0 0 18px}
.ds-section{margin-bottom:18px}
.ds-list{margin:0}
.ds-list>div{display:flex;justify-content:space-between;gap:14px;padding:13px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.ds-list dt,.ds-information dt{color:#64748b}
.ds-list dd{margin:0;text-align:right;color:#0f172a;overflow-wrap:anywhere}
.ds-account-link{margin-top:18px}
.ds-information{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 22px;margin:0}
.ds-information>div{padding:14px 0;border-bottom:1px solid #f1f5f9;min-width:0}
.ds-information dt{font-size:12px;font-weight:700;margin-bottom:8px}
.ds-information dd{font-size:13px;line-height:1.6;color:#0f172a;margin:0;white-space:pre-wrap;overflow-wrap:anywhere}
.ds-long{white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;color:#475569;line-height:1.8;margin:0}
.ds-help{font-size:13px;color:#64748b;line-height:1.8;margin:0}
.ds-section-heading{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.ds-section-heading h2{margin:0}
.ds-documents{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
.ds-document{min-width:0;padding:14px;background:#fafcff;border:1px solid #e2e8f0;border-radius:11px}
.ds-document strong{display:block;font-size:13px;margin-bottom:12px;color:#334155}
.ds-document img{display:block;width:100%;height:170px;object-fit:contain;background:#f1f5f9;border-radius:9px}
.ds-document a{display:inline-block;margin-top:12px;color:#1d4ed8;font-size:12px;font-weight:700;text-decoration:none}
.ds-no-file,.ds-file-placeholder{height:170px;display:flex;justify-content:center;align-items:center;background:#f1f5f9;border-radius:9px;color:#94a3b8;font-size:12px;text-align:center}
.ds-file-placeholder{background:#dbeafe;color:#1d4ed8;font-weight:800;font-size:17px}
.ds-success{padding:13px 15px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:9px;font-size:13px;margin-bottom:18px}
@media(max-width:850px){.ds-two{grid-template-columns:1fr}.ds-documents{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.ds-card{padding:16px}.ds-information{grid-template-columns:1fr}.ds-documents{grid-template-columns:1fr}}
</style>
@endpush

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
