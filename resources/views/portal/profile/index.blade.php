@extends('portal.layouts.app')
@section('title', 'Hồ sơ cá nhân - Mediway')
@section('content')
<link rel="stylesheet" href="{{ asset('css/patient-profile.css') }}">
<section class="profile-page"><div class="portal-shell">
    <a class="profile-back" href="{{ route('patient.home') }}">← Quay lại tài khoản</a>
    <header class="profile-heading"><p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p><h1>Hồ sơ cá nhân</h1><p>Thông tin cá nhân và liên hệ của bạn tại Mediway.</p></header>
    <div class="profile-summary">
        <span class="profile-summary__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($patient->full_name), 0, 1)) }}</span>
        <div class="profile-summary__identity"><span class="profile-summary__label">HỒ SƠ BỆNH NHÂN</span><h2>{{ $patient->full_name }}</h2><p>Mã bệnh nhân: {{ $patient->patient_code }}</p><x-portal.badge type="info">Đang hoạt động</x-portal.badge></div>
        <x-portal.button variant="primary" :href="route('patient.profile.edit')">Chỉnh sửa hồ sơ</x-portal.button>
    </div>
    <div class="profile-cards">
        <section class="profile-card" aria-labelledby="profile-patient"><div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">▤</span><div><h2 id="profile-patient">Thông tin bệnh nhân</h2><p>Thông tin cơ bản trong hồ sơ</p></div></div><dl class="profile-details">
            @foreach ([['Họ và tên', $patient->full_name], ['Ngày sinh', $patient->date_of_birth?->format('d/m/Y')], ['Giới tính', $patient->gender], ['Mã bệnh nhân', $patient->patient_code], ['Số CCCD', $patient->detail?->citizen_id_number ? '••••••••'.substr($patient->detail->citizen_id_number, -4) : null], ['Ngày cấp CCCD', $patient->detail?->citizen_id_issue_date?->format('d/m/Y')], ['Nơi cấp CCCD', $patient->detail?->citizen_id_issue_place], ['Nhóm máu (theo hồ sơ)', $patient->detail?->blood_type], ['Trạng thái hồ sơ', 'Đang hoạt động']] as [$label, $value])
                <div><dt>{{ $label }}</dt><dd @class(['profile-value-empty' => ! $value])>{{ $value ?: 'Chưa cập nhật' }}</dd></div>
            @endforeach
        </dl></section>
        <section class="profile-card" aria-labelledby="profile-contact"><div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">⌂</span><div><h2 id="profile-contact">Liên hệ và địa chỉ</h2><p>Thông tin liên lạc của bạn</p></div></div><dl class="profile-details">
            @foreach ([['Email', $patient->detail?->email], ['Địa chỉ hiện tại', $patient->detail?->current_address], ['Địa chỉ thường trú', $patient->detail?->permanent_address], ['Quê quán', $patient->detail?->hometown], ['Quốc tịch', $patient->detail?->nationality], ['Dân tộc', $patient->detail?->ethnicity], ['Nghề nghiệp', $patient->detail?->occupation]] as [$label, $value])
                <div><dt>{{ $label }}</dt><dd @class(['profile-value-empty' => ! $value])>{{ $value ?: 'Chưa cập nhật' }}</dd></div>
            @endforeach
        </dl></section>
        <section class="profile-card" aria-labelledby="profile-emergency"><div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">♡</span><div><h2 id="profile-emergency">Liên hệ khẩn cấp</h2><p>Người có thể liên hệ khi cần</p></div></div><dl class="profile-details">
            @foreach ([['Họ và tên', $patient->detail?->emergency_contact_name], ['Số điện thoại', $patient->detail?->emergency_contact_phone], ['Quan hệ', $patient->detail?->emergency_contact_relationship]] as [$label, $value])
                <div><dt>{{ $label }}</dt><dd @class(['profile-value-empty' => ! $value])>{{ $value ?: 'Chưa cập nhật' }}</dd></div>
            @endforeach
        </dl></section>
        <section class="profile-card" aria-labelledby="profile-account"><div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">✦</span><div><h2 id="profile-account">Thông tin tài khoản</h2><p>Thông tin đăng nhập của bạn</p></div></div><dl class="profile-details">
            <div><dt>Số điện thoại đăng nhập</dt><dd>{{ $user->phone }}</dd></div><div><dt>Trạng thái tài khoản</dt><dd>Đang hoạt động</dd></div>
        </dl><p class="profile-card__note">Số điện thoại đăng nhập được quản lý riêng. <a href="{{ route('patient.profile.phone') }}">Đổi số điện thoại</a></p></section>
    </div>
</div></section>
@endsection
