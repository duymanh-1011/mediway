@extends('admin.layouts.app')
@section('admin-page', 'administrators-show')

@section('title', 'Chi tiết quản trị viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <span>Quản trị viên</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chi tiết</strong>
@endsection

@section('content')
@php
    $user = $administrator->user;
    $isActive = $user?->status === 'ACTIVE' && $administrator->status === 'ACTIVE';
    $isSelf = $user?->id === auth()->id();
    $genderLabel = match($administrator->gender) {
        'MALE' => 'Nam',
        'FEMALE' => 'Nữ',
        'OTHER' => 'Khác',
        default => 'Chưa cập nhật',
    };
@endphp

<div class="ad-detail">
    <div class="ad-heading">
        <div>
            <h1 class="page-title">{{ $administrator->full_name }}</h1>
            <p class="page-description">
                Hồ sơ quản trị viên @include('admin.layouts.partials.record-code', ['code' => $user?->business_code ?? ''])
            </p>
        </div>

        <div class="ad-heading-actions">
            <a href="{{ route('admin.administrators.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
            <a href="{{ route('admin.administrators.edit', $administrator) }}"
               class="admin-btn admin-btn-secondary">Sửa</a>
        </div>
    </div>

    @if(session('success'))
        <div class="ad-alert ad-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="ad-alert ad-error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="ad-alert ad-error">
            @foreach($errors->all() as $error)
                <div>{{ \App\Models\AdminDisplay::text($error) }}</div>
            @endforeach
        </div>
    @endif

    <div class="ad-grid">
        <section class="ad-card">
            <h2>Thông tin quản trị viên</h2>

            <dl class="ad-details">
                <div><dt>Họ và tên</dt><dd><strong>{{ $administrator->full_name }}</strong></dd></div>
                <div><dt>Email</dt><dd>{{ $administrator->email ?: 'Chưa cập nhật' }}</dd></div>
                <div>
                    <dt>Ngày sinh</dt>
                    <dd>
                        {{ $administrator->date_of_birth
                            ? \Illuminate\Support\Carbon::parse($administrator->date_of_birth)->format('d/m/Y')
                            : 'Chưa cập nhật' }}
                    </dd>
                </div>
                <div><dt>Giới tính</dt><dd>{{ $genderLabel }}</dd></div>
                <div><dt>Địa chỉ</dt><dd>{{ $administrator->address ?: 'Chưa cập nhật' }}</dd></div>
                <div>
                    <dt>Trạng thái hồ sơ</dt>
                    <dd>
                        @include('admin.layouts.partials.status-badge', ['badgeCode' => $administrator->status])
                    </dd>
                </div>
                <div>
<dt>Ngày tạo</dt>
                    <dd>{{ $administrator->created_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </section>

        <section class="ad-card">
            <h2>Tài khoản đăng nhập</h2>

            <dl class="ad-details">
                <div><dt>Mã tài khoản</dt><dd><strong>@include('admin.layouts.partials.record-code', ['code' => $user?->business_code ?? '-'])</strong></dd></div>
                <div><dt>Số điện thoại</dt><dd>{{ $user?->phone ?? '-' }}</dd></div>
                <div><dt>Vai trò</dt><dd>{{ \App\Models\AdminDisplay::label($user?->role?->name ?? '-') }}</dd></div>
                <div>
                    <dt>Trạng thái</dt>
                    <dd>
                        @include('admin.layouts.partials.status-badge', ['badgeCode' => $user?->status])
                    </dd>
                </div>
            </dl>

            @if($user)
                <a href="{{ route('admin.users.show', $user) }}"
                   class="admin-btn admin-btn-detail">
                    Xem tài khoản
                </a>
            @endif
        </section>
    </div>

    <section class="ad-card ad-management">
        <h2>Quản lý trạng thái</h2>
        <p>
            Khi khóa quản trị viên, hệ thống sẽ khóa tài khoản đăng nhập và
            chuyển hồ sơ sang trạng thái ngừng hoạt động. Không xóa lịch sử dữ liệu.
        </p>

        @if($isSelf)
            <div class="ad-notice">
                Đây là tài khoản bạn đang sử dụng. Bạn không thể tự khóa chính mình.
            </div>
        @elseif($user && $isActive)
            <form novalidate method="POST"
                  action="{{ route('admin.administrators.update-status', $administrator) }}"
                  onsubmit="return confirm('Bạn có chắc muốn khóa quản trị viên này?');">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="LOCKED">

                <button type="submit" class="admin-btn admin-btn-danger">
                    Khóa quản trị viên
                </button>
            </form>
        @elseif($user && $user->status === 'LOCKED' && $administrator->status === 'INACTIVE')
            <form novalidate method="POST"
                  action="{{ route('admin.administrators.update-status', $administrator) }}"
                  onsubmit="return confirm('Xác nhận mở khóa quản trị viên này?');">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="ACTIVE">

                <button type="submit" class="admin-btn admin-btn-success">
                    Mở khóa quản trị viên
                </button>
            </form>
        @else
            <div class="ad-notice">
                Tài khoản và hồ sơ chưa đồng nhất trạng thái.
Cần kiểm tra dữ liệu trước khi thực hiện chuyển trạng thái.
            </div>
        @endif
    </section>
</div>
@endsection
