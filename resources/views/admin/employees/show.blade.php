
@extends('admin.layouts.app')
@section('admin-page', 'employees-show')

@section('title', 'Chi tiết nhân viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Nhân viên</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chi tiết</strong>
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
        'Loại nhân viên' => \App\Models\AdminDisplay::label($detail?->employee_type),
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
                @include('admin.layouts.partials.status-badge', ['badgeCode' => $employee->status])
            </p>
        </div>

        <div class="es-actions">
            <a href="{{ route('admin.employees.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
            <a href="{{ route('admin.employees.edit', $employee) }}" class="admin-btn admin-btn-secondary">Sửa</a>
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
                    <div><dt>Trạng thái</dt><dd>@include('admin.layouts.partials.status-badge', ['badgeCode' => $employee->status])</dd></div>
                    <div><dt>Ngày tạo</dt><dd>{{ $employee->created_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                </dl>
            </section>

            <section class="es-card">
                <h2>Tài khoản liên kết</h2>
                <dl class="es-list">
                    <div><dt>Mã tài khoản</dt><dd>{{ $user?->business_code ?? '-' }}</dd></div>
                    <div><dt>Số điện thoại</dt><dd>{{ $user?->phone ?? '-' }}</dd></div>
                    <div><dt>Vai trò</dt><dd>{{ \App\Models\AdminDisplay::label($user?->role?->name ?? '-') }}</dd></div>
                    <div><dt>Trạng thái tài khoản</dt><dd>@include('admin.layouts.partials.status-badge', ['badgeCode' => $user?->status ?? '-'])</dd></div>
                </dl>

                @if($user)
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="admin-btn admin-btn-detail">
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
                <div><dt>Loại nhân viên</dt><dd>{{ \App\Models\AdminDisplay::label($detail?->employee_type) }}</dd></div>
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
                   class="admin-btn admin-btn-secondary">Sửa</a>
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
