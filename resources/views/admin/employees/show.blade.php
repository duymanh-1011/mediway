
@extends('admin.layouts.app')

@section('title', 'Chi tiết nhân viên - Mediway')

@section('breadcrumb')
    Admin / Nhân sự & bệnh nhân / Nhân viên / Chi tiết
@endsection

@section('content')
@php
    $detail = $employee->detail;
    $user = $employee->user;

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
        'Trình độ học vấn' => $detail?->education_level,
        'Trình độ chuyên môn' => $detail?->professional_qualification,
    ];

    $work = [
        'Phòng ban' => $detail?->department,
        'Chức vụ' => $detail?->position,
        'Loại nhân viên' => $detail?->employee_type,
        'Ngày tuyển dụng' => $detail?->hire_date?->format('d/m/Y'),
        'Ngày nghỉ việc' => $detail?->termination_date?->format('d/m/Y'),
        'Số năm công tác' => $detail?->years_of_service,
        'Ca làm việc chính' => $detail?->primary_shift,
        'Nơi làm việc' => $detail?->work_location,
        'Mô tả công việc' => $detail?->job_description,
        'Kỹ năng chuyên môn' => $detail?->professional_skills,
    ];

    $documents = [
        [
            'label' => 'Ảnh đại diện',
            'column' => 'avatar_path',
            'type' => 'avatar',
            'image' => true,
        ],
        [
            'label' => 'CCCD mặt trước',
            'column' => 'citizen_id_front_path',
            'type' => 'citizen-id-front',
            'image' => true,
        ],
        [
            'label' => 'CCCD mặt sau',
            'column' => 'citizen_id_back_path',
            'type' => 'citizen-id-back',
            'image' => true,
        ],
        [
            'label' => 'Hồ sơ PDF',
            'column' => 'profile_pdf_path',
            'type' => 'profile-pdf',
            'image' => false,
        ],
    ];
@endphp

<div class="es-page">
    <div class="es-heading">
        <div>
            <h1 class="page-title">{{ $employee->full_name }}</h1>
            <p class="es-description">
                {{ $employee->employee_code }}
                <span class="es-status {{ $employee->status === 'ACTIVE' ? 'es-active' : 'es-inactive' }}">
                    {{ $employee->status === 'ACTIVE' ? 'Hoạt động' : 'Không hoạt động' }}
                </span>
            </p>
        </div>

        <div class="es-actions">
            <a href="{{ route('admin.employees.index') }}" class="es-btn es-light">
                ← Danh sách
            </a>
            <a href="{{ route('admin.employees.edit', $employee) }}" class="es-btn es-primary">
                Chỉnh sửa hồ sơ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="es-alert">{{ session('success') }}</div>
    @endif

    <div class="es-tabs" role="tablist" aria-label="Hồ sơ nhân viên">
        <button type="button" class="es-tab active"
                id="es-tab-overview" data-tab="overview"
                role="tab" aria-controls="es-panel-overview"
                aria-selected="true">Tổng quan</button>

        <button type="button" class="es-tab"
                id="es-tab-personal" data-tab="personal"
                role="tab" aria-controls="es-panel-personal"
                aria-selected="false">Cá nhân</button>

        <button type="button" class="es-tab"
                id="es-tab-work" data-tab="work"
                role="tab" aria-controls="es-panel-work"
                aria-selected="false">Công việc</button>

        <button type="button" class="es-tab"
                id="es-tab-documents" data-tab="documents"
                role="tab" aria-controls="es-panel-documents"
                aria-selected="false">Tài liệu</button>
    </div>

    <div class="es-panel" id="es-panel-overview"
         data-panel="overview" role="tabpanel"
         aria-labelledby="es-tab-overview">
        <div class="es-two">
            <section class="es-card">
                <h2>Thông tin cơ bản</h2>
                <dl class="es-list">
                    <div><dt>Mã nhân viên</dt><dd>{{ $employee->employee_code }}</dd></div>
                    <div><dt>Họ và tên</dt><dd>{{ $employee->full_name }}</dd></div>
                    <div><dt>Ngày sinh</dt><dd>{{ $employee->date_of_birth?->format('d/m/Y') ?? '-' }}</dd></div>
                    <div><dt>Giới tính</dt><dd>{{ $employee->gender ?? '-' }}</dd></div>
                    <div><dt>Trạng thái</dt><dd>{{ $employee->status }}</dd></div>
                    <div><dt>Ngày tạo</dt><dd>{{ $employee->created_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                </dl>
            </section>

            <section class="es-card">
                <h2>Tài khoản liên kết</h2>
                <dl class="es-list">
                    <div><dt>Mã tài khoản</dt><dd>{{ $user?->business_code ?? '-' }}</dd></div>
                    <div><dt>Số điện thoại</dt><dd>{{ $user?->phone ?? '-' }}</dd></div>
                    <div><dt>Vai trò</dt><dd>{{ $user?->role?->name ?? '-' }}</dd></div>
                    <div><dt>Trạng thái tài khoản</dt><dd>{{ $user?->status ?? '-' }}</dd></div>
                </dl>

                @if($user)
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="es-btn es-light es-account-link">
                        Xem tài khoản
                    </a>
                @endif
            </section>
        </div>

        <section class="es-card es-section">
            <h2>Tóm tắt công việc</h2>
            <dl class="es-information">
                <div><dt>Phòng ban</dt><dd>{{ $detail?->department ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Chức vụ</dt><dd>{{ $detail?->position ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Loại nhân viên</dt><dd>{{ $detail?->employee_type ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Ca làm việc</dt><dd>{{ $detail?->primary_shift ?: 'Chưa cập nhật' }}</dd></div>
            </dl>
        </section>
    </div>

    <div class="es-panel" id="es-panel-personal"
         data-panel="personal" role="tabpanel"
         aria-labelledby="es-tab-personal" hidden>
        <section class="es-card es-section">
            <h2>Thông tin cá nhân</h2>
            <dl class="es-information">
                @foreach($personal as $label => $value)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value === null || $value === '' ? 'Chưa cập nhật' : $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="es-card es-section">
            <h2>Ghi chú</h2>
            <p class="es-long">{{ $detail?->notes ?: 'Chưa có ghi chú.' }}</p>
        </section>
    </div>

    <div class="es-panel" id="es-panel-work"
         data-panel="work" role="tabpanel"
         aria-labelledby="es-tab-work" hidden>
        <section class="es-card es-section">
            <h2>Hồ sơ công việc</h2>
            <dl class="es-information">
                @foreach($work as $label => $value)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value === null || $value === '' ? 'Chưa cập nhật' : $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    <div class="es-panel" id="es-panel-documents"
         data-panel="documents" role="tabpanel"
         aria-labelledby="es-tab-documents" hidden>
        <section class="es-card es-section">
            <div class="es-section-heading">
                <h2>Tài liệu đính kèm</h2>
                <a href="{{ route('admin.employees.edit', $employee) }}"
                   class="es-btn es-light">Cập nhật tài liệu</a>
            </div>

            <div class="es-documents">
                @foreach($documents as $document)
                    @php
                        $hasFile = $detail && filled($detail->{$document['column']});
                        $fileUrl = $hasFile
                            ? route('admin.employees.document', [$employee, $document['type']])
                            : null;
                    @endphp

                    <div class="es-document">
                        <strong>{{ $document['label'] }}</strong>

                        @if($hasFile)
                            @if($document['image'])
                                <a href="{{ $fileUrl }}" target="_blank"
                                   rel="noopener noreferrer">
                                    <img src="{{ $fileUrl }}"
                                         alt="{{ $document['label'] }}"
                                         loading="lazy">
                                </a>
                            @else
                                <div class="es-file-placeholder">PDF</div>
                            @endif

                            <a href="{{ $fileUrl }}" target="_blank"
                               rel="noopener noreferrer" class="es-file-link">
                                Xem tài liệu
                            </a>
                        @else
                            <div class="es-no-file">Chưa có tài liệu</div>
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
.es-page{min-width:0}
.es-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:22px}
.es-description{display:flex;align-items:center;gap:12px;color:#64748b;font-size:13px;margin:7px 0 0}
.es-actions{display:flex;gap:10px;flex-wrap:wrap}
.es-btn{display:inline-flex;align-items:center;justify-content:center;min-height:41px;padding:10px 15px;border:1px solid transparent;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none}
.es-primary{background:#2563eb;color:#fff}
.es-light{background:#fff;border-color:#cbd5e1;color:#334155}
.es-status{padding:6px 10px;border-radius:20px;font-size:11px;font-weight:700}
.es-active{background:#dcfce7;color:#166534}
.es-inactive{background:#f1f5f9;color:#475569}
.es-tabs{display:flex;gap:8px;border-bottom:1px solid #e2e8f0;overflow-x:auto;margin-bottom:22px}
.es-tab{border:0;border-bottom:3px solid transparent;background:transparent;padding:13px 17px;font-size:13px;font-weight:700;color:#64748b;white-space:nowrap;cursor:pointer;font-family:inherit}
.es-tab.active{color:#1d4ed8;border-bottom-color:#2563eb}
.es-panel[hidden]{display:none!important}
.es-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:18px}
.es-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:23px;min-width:0;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.es-card h2{font-size:17px;color:#0f172a;margin:0 0 18px}
.es-section{margin-bottom:18px}
.es-list{margin:0}
.es-list>div{display:flex;justify-content:space-between;gap:14px;padding:13px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.es-list dt,.es-information dt{color:#64748b}
.es-list dd{margin:0;text-align:right;color:#0f172a;overflow-wrap:anywhere}
.es-account-link{margin-top:18px}
.es-information{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 22px;margin:0}
.es-information>div{padding:14px 0;border-bottom:1px solid #f1f5f9;min-width:0}
.es-information dt{font-size:12px;font-weight:700;margin-bottom:8px}
.es-information dd{font-size:13px;line-height:1.6;color:#0f172a;margin:0;white-space:pre-wrap;overflow-wrap:anywhere}
.es-long{white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;color:#475569;line-height:1.8;margin:0}
.es-section-heading{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.es-section-heading h2{margin:0}
.es-documents{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
.es-document{min-width:0;padding:14px;background:#fafcff;border:1px solid #e2e8f0;border-radius:11px}
.es-document strong{display:block;font-size:13px;margin-bottom:12px;color:#334155}
.es-document img{display:block;width:100%;height:180px;object-fit:contain;background:#f1f5f9;border-radius:9px}
.es-file-link{display:inline-block;margin-top:12px;color:#1d4ed8;font-size:12px;font-weight:700;text-decoration:none}
.es-no-file,.es-file-placeholder{height:180px;display:flex;justify-content:center;align-items:center;background:#f1f5f9;border-radius:9px;color:#94a3b8;font-size:12px;text-align:center}
.es-file-placeholder{background:#fee2e2;color:#b91c1c;font-weight:800;font-size:22px}
.es-alert{padding:13px 15px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:9px;font-size:13px;margin-bottom:18px}
@media(max-width:850px){.es-two{grid-template-columns:1fr}}
@media(max-width:600px){.es-card{padding:16px}.es-information,.es-documents{grid-template-columns:1fr}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const names = ['overview', 'personal', 'work', 'documents'];
    const tabs = document.querySelectorAll('.es-tab[data-tab]');
    const panels = document.querySelectorAll('.es-panel[data-panel]');

    function activate(name, changeHash) {
        if (!names.includes(name)) {
            name = 'overview';
        }

        tabs.forEach(function (tab) {
            const active = tab.dataset.tab === name;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.tabIndex = active ? 0 : -1;
        });

        panels.forEach(function (panel) {
            panel.hidden = panel.dataset.panel !== name;
        });

        if (changeHash) {
            history.replaceState(null, '', '#' + name);
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activate(tab.dataset.tab, true);
        });

        tab.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();

            const current = names.indexOf(tab.dataset.tab);
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const next = (current + direction + names.length) % names.length;

            activate(names[next], true);
            document.getElementById('es-tab-' + names[next]).focus();
        });
    });

    activate(location.hash.substring(1), false);

    window.addEventListener('hashchange', function () {
        activate(location.hash.substring(1), false);
    });
});
</script>
@endpush
