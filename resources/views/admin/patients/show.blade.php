
@extends('admin.layouts.app')
@section('admin-page', 'patients-show')

@section('title', 'Chi tiết bệnh nhân - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chi tiết</strong>
@endsection

@section('content')
@php
    $detail = $patient->detail;
    $user = $patient->user;

    // Chỉ lấy lịch sử của bệnh nhân đang xem.
    $healthRecords = $patient->healthRecords()
        ->orderByDesc('recorded_at')
        ->orderByDesc('id')
        ->limit(8)
        ->get();

    $healthRecordCount = $patient->healthRecords()->count();

    $information = [
        'Số CCCD' => $detail?->citizen_id_number,
        'Ngày cấp CCCD' => $detail?->citizen_id_issue_date?->format('d/m/Y'),
        'Nơi cấp CCCD' => $detail?->citizen_id_issue_place,
        'Email' => $detail?->email,
        'Quê quán' => $detail?->hometown,
        'Địa chỉ thường trú' => $detail?->permanent_address,
        'Địa chỉ hiện tại' => $detail?->current_address,
        'Quốc tịch' => $detail?->nationality,
        'Dân tộc' => $detail?->ethnicity,
        'Nghề nghiệp' => $detail?->occupation,
        'Nhóm máu' => $detail?->blood_type,
    ];

    $documents = [
        [
            'label' => 'Ảnh đại diện',
            'column' => 'avatar_path',
            'type' => 'avatar',
            'is_image' => true,
        ],
        [
            'label' => 'CCCD mặt trước',
            'column' => 'citizen_id_front_path',
            'type' => 'citizen-id-front',
            'is_image' => true,
        ],
        [
            'label' => 'CCCD mặt sau',
            'column' => 'citizen_id_back_path',
            'type' => 'citizen-id-back',
            'is_image' => true,
        ],
        [
            'label' => 'Hồ sơ PDF',
            'column' => 'profile_pdf_path',
            'type' => 'profile-pdf',
            'is_image' => false,
        ],
    ];
@endphp

<div class="patient-detail-page">

    {{-- TIÊU ĐỀ --}}
    <div class="pd-heading">
        <div>
            <h1 class="page-title">{{ $patient->full_name }}</h1>
            <p class="page-description">
                Hồ sơ bệnh nhân
                <strong>{{ $patient->patient_code }}</strong>

                <span class="pd-badge {{ $patient->status === 'ACTIVE' ? 'pd-green' : 'pd-neutral' }}">
                    {{ $patient->status === 'ACTIVE' ? 'Hoạt động' : $patient->status }}
                </span>
            </p>
        </div>

        <div class="pd-actions">
            <a href="{{ route('admin.patients.index') }}"
               class="admin-btn admin-btn-detail pd-btn pd-light">
                ← Danh sách
            </a>

            <a href="{{ route('admin.patients.edit', $patient) }}"
               class="pd-btn pd-primary">
                Chỉnh sửa hồ sơ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="pd-alert pd-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="pd-alert pd-error">
            {{ session('error') }}
        </div>
    @endif

    {{-- THANH TAB --}}
    <div class="pd-tabs" role="tablist" aria-label="Các mục hồ sơ bệnh nhân">
        <button type="button"
                id="pd-tab-overview"
                class="pd-tab active"
                data-tab="overview"
                role="tab"
                aria-controls="pd-panel-overview"
                aria-selected="true">
            Tổng quan
        </button>

        <button type="button"
                id="pd-tab-personal"
                class="pd-tab"
                data-tab="personal"
                role="tab"
                aria-controls="pd-panel-personal"
                aria-selected="false">
            Thông tin cá nhân
        </button>

        <button type="button"
                id="pd-tab-health"
                class="pd-tab"
                data-tab="health"
                role="tab"
                aria-controls="pd-panel-health"
                aria-selected="false">
            Lịch sử sức khỏe
            <span class="pd-tab-count">{{ $healthRecordCount }}</span>
        </button>

        <button type="button"
                id="pd-tab-documents"
                class="pd-tab"
                data-tab="documents"
                role="tab"
                aria-controls="pd-panel-documents"
                aria-selected="false">
            Tài liệu
        </button>
    </div>

    {{-- TAB 1: TỔNG QUAN --}}
    <div id="pd-panel-overview"
         class="pd-tab-panel active"
         data-panel="overview"
         role="tabpanel"
         aria-labelledby="pd-tab-overview">

        <div class="pd-grid">
            <section class="pd-card">
                <h2>Thông tin bệnh nhân</h2>

                <dl class="pd-details">
                    <div>
                        <dt>Mã bệnh nhân</dt>
                        <dd><strong>{{ $patient->patient_code }}</strong></dd>
                    </div>

                    <div>
                        <dt>Họ và tên</dt>
                        <dd>{{ $patient->full_name }}</dd>
                    </div>

                    <div>
                        <dt>Ngày sinh</dt>
                        <dd>
                            {{ $patient->date_of_birth?->format('d/m/Y') ?? '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt>Giới tính</dt>
                        <dd>{{ $patient->gender ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt>Trạng thái hồ sơ</dt>
                        <dd>
                            <span class="pd-badge {{ $patient->status === 'ACTIVE' ? 'pd-green' : 'pd-neutral' }}">
                                {{ $patient->status === 'ACTIVE' ? 'Hoạt động' : $patient->status }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt>Ngày tạo</dt>
                        <dd>
                            {{ $patient->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="pd-card">
                <h2>Tài khoản đăng nhập</h2>

                @if($user)
                    <dl class="pd-details">
                        <div>
                            <dt>Mã tài khoản</dt>
                            <dd>
                                <strong>{{ $user->business_code }}</strong>
                            </dd>
                        </div>

                        <div>
                            <dt>Số điện thoại</dt>
                            <dd>{{ $user->phone }}</dd>
                        </div>

                        <div>
                            <dt>Vai trò</dt>
                            <dd>{{ \App\Models\AdminDisplay::label($user->role?->name ?? 'Patient') }}</dd>
                        </div>

                        <div>
                            <dt>Trạng thái</dt>
                            <dd>
                                <span class="pd-badge {{ $user->status === 'ACTIVE' ? 'pd-green' : 'pd-neutral' }}">
                                    {{ $user->status === 'ACTIVE' ? 'Hoạt động' : $user->status }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <a href="{{ route('admin.users.show', $user) }}"
                       class="pd-btn pd-light pd-account-link">
                        Xem tài khoản
                    </a>
                @else
                    <div class="pd-empty-account">
                        <div class="pd-empty-icon">!</div>
                        <strong>Chưa liên kết tài khoản</strong>
                        <p>
                            Bệnh nhân đã có hồ sơ nhưng chưa có tài khoản
                            đăng nhập Mediway.
                        </p>
                    </div>
                @endif
            </section>
        </div>

        <section class="pd-card pd-section">
            <div class="pd-section-heading">
                <div>
                    <h2>Tóm tắt sức khỏe</h2>
                    <p class="pd-help">
                        Tổng số lần ghi nhận:
                        <strong>{{ $healthRecordCount }}</strong>
                    </p>
                </div>

                <button type="button"
                        class="pd-btn pd-light"
                        data-open-tab="health">
                    Xem lịch sử sức khỏe →
                </button>
            </div>

            @if($healthRecords->isNotEmpty())
                @php
                    $latestHealth = $healthRecords->first();
                @endphp

                <div class="pd-summary-metrics">
                    <div class="pd-summary-metric">
                        <span>Lần đo gần nhất</span>
                        <strong>
                            {{ $latestHealth->recorded_at?->format('d/m/Y H:i') }}
                        </strong>
                    </div>

                    <div class="pd-summary-metric">
                        <span>Chiều cao</span>
                        <strong>
                            {{ $latestHealth->height_cm !== null ? $latestHealth->height_cm . ' cm' : '-' }}
                        </strong>
                    </div>

                    <div class="pd-summary-metric">
                        <span>Cân nặng</span>
                        <strong>
                            {{ $latestHealth->weight_kg !== null ? $latestHealth->weight_kg . ' kg' : '-' }}
                        </strong>
                    </div>

                    <div class="pd-summary-metric">
                        <span>BMI</span>
                        <strong>{{ $latestHealth->bmi ?? '-' }}</strong>
                    </div>
                </div>
            @else
                <p class="pd-help">
                    Chưa có chỉ số sức khỏe được ghi nhận cho bệnh nhân này.
                </p>
            @endif
        </section>
    </div>

    {{-- TAB 2: THÔNG TIN CÁ NHÂN --}}
    <div id="pd-panel-personal"
         class="pd-tab-panel"
         data-panel="personal"
         role="tabpanel"
         aria-labelledby="pd-tab-personal"
         hidden>

        <section class="pd-card pd-section">
            <h2>Thông tin cá nhân chi tiết</h2>

            @if($detail)
                <dl class="pd-information">
                    @foreach($information as $label => $value)
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value ?: 'Chưa cập nhật' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="pd-help">Bệnh nhân chưa có thông tin chi tiết.</p>
            @endif
        </section>

        <section class="pd-card pd-section">
            <h2>Liên hệ khẩn cấp</h2>

            <dl class="pd-information">
                <div>
                    <dt>Người liên hệ</dt>
                    <dd>
                        {{ $detail?->emergency_contact_name ?: 'Chưa cập nhật' }}
                    </dd>
                </div>

                <div>
                    <dt>Số điện thoại</dt>
                    <dd>
                        {{ $detail?->emergency_contact_phone ?: 'Chưa cập nhật' }}
                    </dd>
                </div>

                <div>
                    <dt>Mối quan hệ</dt>
                    <dd>
                        {{ $detail?->emergency_contact_relationship ?: 'Chưa cập nhật' }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="pd-card pd-section">
            <h2>Ghi chú</h2>
            <p class="pd-notes">
                {{ $detail?->notes ?: 'Chưa có ghi chú.' }}
            </p>
        </section>
    </div>

    {{-- TAB 3: LỊCH SỬ SỨC KHỎE --}}
    <div id="pd-panel-health"
         class="pd-tab-panel"
         data-panel="health"
         role="tabpanel"
         aria-labelledby="pd-tab-health"
         hidden>

        <section class="pd-card pd-section">
            <div class="pd-section-heading">
                <div>
                    <h2>Lịch sử chỉ số sức khỏe</h2>
                    <p class="pd-help">
                        {{ $patient->patient_code }} —
                        {{ $healthRecordCount }} lần ghi nhận
                    </p>
                </div>

                <div class="pd-actions">
                    <a href="{{ route('admin.personal-health-records.index', ['patient_id' => $patient->id]) }}"
                       class="pd-btn pd-light">
                        Xem toàn bộ
                    </a>

                    @if($patient->status === 'ACTIVE')
                        <a href="{{ route('admin.personal-health-records.create', ['patient_id' => $patient->id]) }}"
                           class="pd-btn pd-primary">
                            + Thêm lần đo
                        </a>
                    @endif
                </div>
            </div>

            @if($healthRecords->isNotEmpty())
                <div class="pd-table-wrap">
                    <table class="pd-health-table">
                        <thead>
                            <tr>
                                <th scope="col" class="admin-row-number">STT</th>
                                <th>Thời điểm ghi nhận</th>
                                <th>Chiều cao</th>
                                <th>Cân nặng</th>
                                <th>BMI</th>
                                <th>Huyết áp</th>
                                <th>Nhịp tim</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($healthRecords as $record)
                                <tr>
                                    @include('admin.layouts.partials.row-number', ['rows' => $healthRecords, 'index' => $loop->index])
                                    <td>
                                        <strong>
                                            {{ $record->recorded_at?->format('d/m/Y H:i') }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $record->height_cm !== null ? $record->height_cm . ' cm' : '-' }}
                                    </td>

                                    <td>
                                        {{ $record->weight_kg !== null ? $record->weight_kg . ' kg' : '-' }}
                                    </td>

                                    <td>
                                        {{ $record->bmi ?? '-' }}
                                    </td>

                                    <td>
                                        @if($record->systolic_blood_pressure !== null && $record->diastolic_blood_pressure !== null)
                                            {{ $record->systolic_blood_pressure }}/{{ $record->diastolic_blood_pressure }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        {{ $record->heart_rate ?? '-' }}
                                    </td>

                                    <td>
                                        <div class="pd-row-actions">
                                            <a href="{{ route('admin.personal-health-records.show', $record) }}"
                                               class="pd-action-link pd-view">Chi tiết</a>

                                            <a href="{{ route('admin.personal-health-records.edit', $record) }}"
                                               class="admin-btn admin-btn-secondary pd-action-link pd-edit">Sửa</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($healthRecordCount > 8)
                    <div class="pd-table-footer">
                        Đang hiển thị 8 lần đo gần nhất.

                        <a href="{{ route('admin.personal-health-records.index', ['patient_id' => $patient->id]) }}">
                            Xem toàn bộ {{ $healthRecordCount }} lần đo →
                        </a>
                    </div>
                @endif
            @else
                <div class="pd-empty-health">
                    <div class="pd-empty-icon">+</div>

                    <strong>Chưa có lịch sử sức khỏe</strong>

                    <p>
                        Bệnh nhân chưa có lần đo sức khỏe nào trong hệ thống.
                    </p>

                    @if($patient->status === 'ACTIVE')
                        <a href="{{ route('admin.personal-health-records.create', ['patient_id' => $patient->id]) }}"
                           class="pd-btn pd-primary">
                            Ghi nhận chỉ số đầu tiên
                        </a>
                    @endif
                </div>
            @endif
        </section>
    </div>

    {{-- TAB 4: TÀI LIỆU --}}
    <div id="pd-panel-documents"
         class="pd-tab-panel"
         data-panel="documents"
         role="tabpanel"
         aria-labelledby="pd-tab-documents"
         hidden>

        <section class="pd-card pd-section">
            <div class="pd-section-heading">
                <div>
                    <h2>Tài liệu đính kèm</h2>
                    <p class="pd-help">
                        Ảnh và PDF được lưu riêng tư, truy cập qua route Admin.
                    </p>
                </div>

                <a href="{{ route('admin.patients.edit', $patient) }}"
                   class="pd-btn pd-light">
                    Cập nhật tài liệu
                </a>
            </div>

            <div class="pd-documents">
                @foreach($documents as $document)
                    <div class="pd-document">
                        <div class="pd-document-title">
                            {{ $document['label'] }}
                        </div>

                        @if($detail && $detail->{$document['column']})
                            @if($document['is_image'])
                                <a href="{{ route('admin.patients.document', [$patient, $document['type']]) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="pd-document-image-link">
                                    <img
                                        src="{{ route('admin.patients.document', [$patient, $document['type']]) }}"
                                        alt="{{ $document['label'] }}"
                                        class="pd-document-image"
                                        loading="lazy"
                                        referrerpolicy="no-referrer"
                                    >
                                </a>
                            @else
                                <div class="pd-pdf-placeholder">PDF</div>
                            @endif

                            <a href="{{ route('admin.patients.document', [$patient, $document['type']]) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="pd-document-link">
                                Xem {{ $document['label'] }}
                            </a>
                        @else
                            <div class="pd-document-missing">
                                Chưa có tài liệu
                            </div>
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
    const tabs = document.querySelectorAll('.pd-tab[data-tab]');
    const panels = document.querySelectorAll('.pd-tab-panel[data-panel]');
    const openButtons = document.querySelectorAll('[data-open-tab]');
    const validTabs = ['overview', 'personal', 'health', 'documents'];

    function activateTab(name, updateHash) {
        if (!validTabs.includes(name)) {
            name = 'overview';
        }

        tabs.forEach(function (tab) {
            const active = tab.dataset.tab === name;

            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.tabIndex = active ? 0 : -1;
        });

        panels.forEach(function (panel) {
            const active = panel.dataset.panel === name;

            panel.classList.toggle('active', active);
            panel.hidden = !active;
        });

        if (updateHash) {
            history.replaceState(null, '', '#' + name);
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateTab(tab.dataset.tab, true);
        });

        tab.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();

            const currentIndex = validTabs.indexOf(tab.dataset.tab);
            const movement = event.key === 'ArrowRight' ? 1 : -1;
            const nextIndex = (
                currentIndex + movement + validTabs.length
            ) % validTabs.length;

            activateTab(validTabs[nextIndex], true);

            const nextTab = document.querySelector(
                '.pd-tab[data-tab="' + validTabs[nextIndex] + '"]'
            );

            if (nextTab) {
                nextTab.focus();
            }
        });
    });

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            activateTab(button.dataset.openTab, true);
        });
    });

    const initialTab = window.location.hash.replace('#', '');

    activateTab(
        validTabs.includes(initialTab) ? initialTab : 'overview',
        false
    );

    window.addEventListener('hashchange', function () {
        activateTab(window.location.hash.replace('#', ''), false);
    });
});
</script>
@endpush
