@extends('portal.layouts.app')
@section('title', 'Chỉnh sửa hồ sơ - Mediway')
@section('content')
<link rel="stylesheet" href="{{ asset('css/patient-profile.css') }}">
<script src="{{ asset('js/patient-profile.js') }}" defer></script>
<section class="profile-page"><div class="portal-shell profile-edit-shell">
    <a class="profile-back" href="{{ route('patient.profile.show') }}">← Quay lại hồ sơ cá nhân</a>
    <header class="profile-heading"><p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p><h1>Chỉnh sửa hồ sơ</h1><p>Cập nhật thông tin cá nhân và liên hệ của bạn.</p></header>
    <div class="profile-notice"><span class="profile-notice__icon" aria-hidden="true">i</span><div><strong>Thông tin được quản lý riêng</strong><p>Ngày sinh, giới tính, số điện thoại đăng nhập và thông tin định danh không thể thay đổi tại đây. <a href="{{ route('public.support') }}">Liên hệ hỗ trợ</a> khi cần.</p></div></div>
    @if ($errors->any())<x-portal.alert type="error" class="profile-alert">Vui lòng kiểm tra các thông tin chưa hợp lệ bên dưới.</x-portal.alert>@endif
    @if (session('error'))<x-portal.alert type="error" class="profile-alert">{{ session('error') }}</x-portal.alert>@endif
    <form method="post" action="{{ route('patient.profile.update') }}" class="profile-edit" data-profile-form>@csrf @method('PUT')
        @foreach ([
            ['Thông tin cá nhân', 'Thông tin cơ bản của bạn', '▤', [
                ['full_name', 'Họ và tên', $patient->full_name, 'text'],
                ['email', 'Email', $patient->detail?->email, 'email'],
            ]],
            ['Địa chỉ và liên hệ', 'Thông tin liên hệ và nơi sinh sống', '⌂', [
                ['hometown', 'Quê quán', $patient->detail?->hometown, 'text'],
                ['permanent_address', 'Địa chỉ thường trú', $patient->detail?->permanent_address, 'text'],
                ['current_address', 'Địa chỉ hiện tại', $patient->detail?->current_address, 'text'],
                ['nationality', 'Quốc tịch', $patient->detail?->nationality, 'text'],
                ['ethnicity', 'Dân tộc', $patient->detail?->ethnicity, 'text'],
                ['occupation', 'Nghề nghiệp', $patient->detail?->occupation, 'text'],
            ]],
            ['Liên hệ khẩn cấp', 'Người có thể liên hệ khi cần', '♡', [
                ['emergency_contact_name', 'Họ tên liên hệ khẩn cấp', $patient->detail?->emergency_contact_name, 'text'],
                ['emergency_contact_phone', 'Số điện thoại liên hệ khẩn cấp', $patient->detail?->emergency_contact_phone, 'tel'],
                ['emergency_contact_relationship', 'Quan hệ với người liên hệ', $patient->detail?->emergency_contact_relationship, 'text'],
            ]],
        ] as [$heading, $description, $icon, $fields])
            <section class="profile-card"><div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">{{ $icon }}</span><div><h2>{{ $heading }}</h2><p>{{ $description }}</p></div></div>
                <div class="profile-form-fields">@foreach ($fields as [$name, $label, $value, $type])
                    <div class="profile-form-field"><label for="{{ $name }}">{{ $label }}@if ($name === 'full_name') <span aria-hidden="true">*</span>@endif</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @if ($name === 'full_name') required @endif aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @if ($errors->has($name)) aria-describedby="{{ $name }}-error" @endif>
                        @error($name)<p class="portal-field-error" id="{{ $name }}-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach</div>
            </section>
        @endforeach
        <div class="profile-form-actions"><p>Các trường có dấu <span aria-hidden="true">*</span> là bắt buộc.</p><div><x-portal.button variant="secondary" :href="route('patient.profile.show')">Hủy</x-portal.button><x-portal.button type="submit" variant="primary" data-profile-submit>Lưu thay đổi</x-portal.button></div></div>
        <p class="profile-submit-status" role="status" aria-live="polite" data-profile-status></p>
    </form>
</div></section>
@endsection
