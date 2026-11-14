@extends('employee.layout')
@section('title', 'Chỉnh sửa hồ sơ')
@section('content')
@php($detail = $employee->detail)
<div class="employee-profile employee-profile--edit">
    <header class="employee-profile__heading"><div><a class="employee-profile__back" href="{{ route('employee.profile') }}">← Quay lại hồ sơ cá nhân</a><p class="portal-eyebrow">TÀI KHOẢN NHÂN VIÊN</p><h1>Chỉnh sửa thông tin</h1><p>Cập nhật thông tin liên hệ của bạn tại Mediway.</p></div></header>
    <div class="employee-profile__notice"><x-employee-icon name="shield" /><div><strong>Thông tin tài khoản</strong><p>Bạn có thể cập nhật email và địa chỉ. Để thay đổi thông tin công việc hoặc số điện thoại đăng nhập, vui lòng liên hệ quản trị viên.</p></div></div>
    <form class="employee-profile__edit-form" method="post" action="{{ route('employee.profile.update') }}">@csrf @method('PUT')
        <section class="employee-card employee-profile__card">
            <div class="employee-card__heading"><span class="employee-card__icon"><x-employee-icon name="contact" /></span><div><h2>Thông tin liên hệ</h2><p>Thông tin dùng để liên hệ với bạn</p></div></div>
            <div class="employee-profile__edit-fields">
                @foreach (['email' => 'Email', 'current_address' => 'Địa chỉ hiện tại', 'permanent_address' => 'Địa chỉ thường trú'] as $field => $label)
                    <div class="employee-profile__field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $detail?->{$field}) }}" maxlength="{{ $field === 'email' ? 150 : 255 }}" @if($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif>@error($field)<p class="portal-field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror</div>
                @endforeach
            </div>
        </section>
        <div class="employee-profile__actions"><a class="portal-btn portal-btn--secondary" href="{{ route('employee.profile') }}">Hủy</a><button class="portal-btn portal-btn--primary" type="submit">Lưu thay đổi</button></div>
    </form>
</div>
@endsection
