@extends('employee.layout')
@section('title', 'Chỉnh sửa hồ sơ')
@section('content')
<section class="employee-intro"><a class="portal-text-link" href="{{ route('employee.profile') }}">← Quay lại hồ sơ cá nhân</a><p class="portal-eyebrow">TÀI KHOẢN NHÂN VIÊN</p><h1>Chỉnh sửa thông tin liên hệ</h1><p>Cập nhật thông tin dùng để liên hệ với bạn.</p></section>
<section class="employee-card"><h2>Thông tin liên hệ</h2><form class="portal-form" method="post" action="{{ route('employee.profile.update') }}">@csrf @method('PUT')
@foreach (['email' => 'Email', 'current_address' => 'Địa chỉ hiện tại', 'permanent_address' => 'Địa chỉ thường trú'] as $field => $label)
<label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $employee->detail->{$field}) }}" maxlength="{{ $field === 'email' ? 150 : 255 }}" @if($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif>
@error($field)<p class="portal-field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror
@endforeach<button class="portal-btn portal-btn--primary" type="submit">Lưu thông tin</button></form></section>
@endsection
