@extends('admin.layouts.app')

@section('title', 'Chi tiết bệnh nhân - Mediway')

@section('breadcrumb')
    Admin / Bệnh nhân / Chi tiết
@endsection

@section('content')
@php
    $detail = $patient->detail;
    $user = $patient->user;

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
    <div class="pd-heading">
        <div>
            <h1 class="page-title">{{ $patient->full_name }}</h1>
            <p class="page-description">
                Hồ sơ bệnh nhân {{ $patient->patient_code }}
            </p>
        </div>

        <div class="pd-actions">
            <a href="{{ route('admin.patients.index') }}"
               class="pd-btn pd-light">
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
                        <dd><strong>{{ $user->business_code }}</strong></dd>
                    </div>

                    <div>
                        <dt>Số điện thoại</dt>
                        <dd>{{ $user->phone }}</dd>
                    </div>

                    <div>
                        <dt>Vai trò</dt>
                        <dd>{{ $user->role?->name ?? 'Patient' }}</dd>
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
                        Bệnh nhân đã có hồ sơ nhưng chưa có tài khoản đăng nhập.
                    </p>
                </div>
            @endif
        </section>
    </div>

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
        <div class="pd-document-heading">
            <div>
                <h2>Tài liệu đính kèm</h2>
                <p class="pd-help">
                    Tài liệu được lưu riêng tư. Chỉ quản trị viên đã đăng nhập
                    và được middleware cho phép mới có thể mở.
                </p>
            </div>
        </div>

        <div class="pd-documents">
            @foreach($documents as $document)
                <div class="pd-document">
                    <div class="pd-document-title">
                        {{ $document['label'] }}
                    </div>

                    @if($detail && $detail->{$document['column']})
                        @if($document['is_image'])
                            <a
                                href="{{ route('admin.patients.document', [$patient, $document['type']]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="pd-document-image-link"
                            >
                                <img
                                    src="{{ route('admin.patients.document', [$patient, $document['type']]) }}"
                                    alt="{{ $document['label'] }} của bệnh nhân"
                                    class="pd-document-image"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                >
                            </a>
                        @else
                            <div class="pd-pdf-placeholder">
                                PDF
                            </div>
                        @endif

                        <a
                            href="{{ route('admin.patients.document', [$patient, $document['type']]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="pd-document-link"
                        >
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

    <section class="pd-card pd-section">
        <h2>Ghi chú</h2>
        <p class="pd-notes">
            {{ $detail?->notes ?: 'Chưa có ghi chú.' }}
        </p>
    </section>
</div>
@endsection

@push('styles')
<style>
.patient-detail-page{min-width:0}
.pd-heading{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:22px}
.pd-heading .page-description{margin:7px 0 0;color:#64748b}
.pd-actions{display:flex;gap:10px;flex-wrap:wrap}
.pd-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:19px}
.pd-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:24px;box-shadow:0 4px 20px rgba(15,23,42,.035);min-width:0}
.pd-card h2{font-size:17px;color:#0f172a;margin:0 0 20px}
.pd-section{margin-bottom:19px}
.pd-details{margin:0}
.pd-details>div{display:grid;grid-template-columns:135px minmax(0,1fr);gap:12px;padding:13px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.pd-details>div:last-child{border-bottom:0}
.pd-details dt,.pd-information dt{color:#64748b}
.pd-details dd,.pd-information dd{margin:0;color:#0f172a;overflow-wrap:anywhere}
.pd-information{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 22px;margin:0}
.pd-information>div{padding:14px 0;border-bottom:1px solid #f1f5f9;min-width:0}
.pd-information dt{font-size:12px;font-weight:700;margin-bottom:8px}
.pd-information dd{font-size:13px;line-height:1.6}
.pd-badge{display:inline-block;border-radius:30px;padding:6px 10px;font-size:11px;font-weight:700}
.pd-green{background:#dcfce7;color:#166534}
.pd-neutral{background:#f1f5f9;color:#475569}
.pd-btn{display:inline-flex;justify-content:center;align-items:center;min-height:41px;padding:10px 16px;border:1px solid transparent;border-radius:9px;text-decoration:none;font-family:inherit;font-size:13px;font-weight:700}
.pd-primary{background:#2563eb;color:#fff}
.pd-light{background:#fff;color:#334155;border-color:#cbd5e1}
.pd-account-link{margin-top:20px}
.pd-empty-account{font-size:13px;color:#334155;line-height:1.7}
.pd-empty-account p{color:#64748b}
.pd-empty-icon{width:45px;height:45px;border-radius:13px;background:#fef3c7;color:#92400e;display:flex;align-items:center;justify-content:center;font-size:23px;font-weight:800;margin-bottom:13px}
.pd-help{font-size:13px;color:#64748b;line-height:1.6;margin:0}
.pd-notes{margin:0;white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;line-height:1.7;color:#475569}
.pd-alert{padding:13px 15px;border-radius:9px;margin-bottom:17px;font-size:13px}
.pd-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.pd-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.pd-document-heading{margin-bottom:20px}
.pd-document-heading h2{margin-bottom:8px}
.pd-documents{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}
.pd-document{border:1px solid #e2e8f0;border-radius:11px;padding:14px;background:#fafcff;min-width:0}
.pd-document-title{font-size:13px;font-weight:700;color:#334155;margin-bottom:13px}
.pd-document-image-link{display:block;overflow:hidden;border-radius:9px;background:#edf2f7}
.pd-document-image{display:block;width:100%;height:150px;object-fit:contain}
.pd-pdf-placeholder{height:150px;background:#fef2f2;color:#b91c1c;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:29px;font-weight:800;letter-spacing:1px}
.pd-document-link{display:inline-block;color:#1d4ed8;font-size:12px;font-weight:700;text-decoration:none;margin-top:13px}
.pd-document-link:hover{text-decoration:underline}
.pd-document-missing{height:150px;display:flex;justify-content:center;align-items:center;text-align:center;background:#f1f5f9;border-radius:9px;color:#94a3b8;font-size:12px;padding:10px}
@media(max-width:950px){.pd-documents{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:800px){.pd-grid{grid-template-columns:1fr}}
@media(max-width:600px){.pd-card{padding:17px}.pd-information{grid-template-columns:1fr}.pd-details>div{grid-template-columns:1fr;gap:5px}}
@media(max-width:430px){.pd-documents{grid-template-columns:1fr}}
</style>
@endpush