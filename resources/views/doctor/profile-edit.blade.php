@extends('doctor.layout')

@section('title', 'Chỉnh sửa thông tin')

@section('content')
    @php
        $detail = $doctor->detail;
    @endphp

    <div class="employee-profile employee-profile--edit">
        <header class="employee-profile__heading">
            <div>
                <a class="employee-profile__back" href="{{ route('doctor.profile') }}">← Quay lại hồ sơ cá nhân</a>
                <p class="portal-eyebrow">TÀI KHOẢN BÁC SĨ</p>
                <h1>Chỉnh sửa thông tin</h1>
                <p>Cập nhật thông tin liên hệ của bạn tại Mediway.</p>
            </div>
        </header>

        <div class="employee-profile__notice">
            <x-employee-icon name="shield" />
            <div>
                <strong>Thông tin tài khoản</strong>
                <p>
                    Bạn có thể cập nhật các thông tin liên hệ được hệ thống cho phép.
                    Để thay đổi họ tên, mã bác sĩ, chuyên khoa hoặc giấy phép hành nghề,
                    vui lòng liên hệ quản trị viên.
                </p>
            </div>
        </div>

        <form class="employee-profile__edit-form" method="post" action="{{ route('doctor.profile.update') }}">
            @csrf
            @method('PUT')

            <section class="employee-card employee-profile__card">
                <div class="employee-card__heading">
                    <span class="employee-card__icon"><x-employee-icon name="contact" /></span>
                    <div>
                        <h2>Thông tin liên hệ</h2>
                        <p>Thông tin dùng để liên hệ với bạn</p>
                    </div>
                </div>

                <div class="employee-profile__edit-fields">
                    <div class="employee-profile__field">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $detail?->email) }}"
                            maxlength="150"
                            @if($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                        >
                        @error('email')
                            <p class="portal-field-error" id="email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="employee-profile__field">
                        <label for="current_address">Địa chỉ hiện tại</label>
                        <input
                            id="current_address"
                            name="current_address"
                            type="text"
                            value="{{ old('current_address', $detail?->current_address) }}"
                            maxlength="255"
                            @if($errors->has('current_address')) aria-invalid="true" aria-describedby="current_address-error" @endif
                        >
                        @error('current_address')
                            <p class="portal-field-error" id="current_address-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="employee-profile__field">
                        <label for="permanent_address">Địa chỉ thường trú</label>
                        <input
                            id="permanent_address"
                            name="permanent_address"
                            type="text"
                            value="{{ old('permanent_address', $detail?->permanent_address) }}"
                            maxlength="255"
                            @if($errors->has('permanent_address')) aria-invalid="true" aria-describedby="permanent_address-error" @endif
                        >
                        @error('permanent_address')
                            <p class="portal-field-error" id="permanent_address-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <div class="employee-profile__actions">
                <a class="portal-btn portal-btn--secondary" href="{{ route('doctor.profile') }}">
                    Hủy
                </a>

                <button class="portal-btn portal-btn--primary" type="submit">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
@endsection