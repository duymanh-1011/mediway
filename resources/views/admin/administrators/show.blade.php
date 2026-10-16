@extends('admin.layouts.app')

@section('title', 'Chi tiết quản trị viên - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Quản trị viên / Chi tiết
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
                Hồ sơ quản trị viên {{ $user?->business_code ?? '' }}
            </p>
        </div>

        <div class="ad-heading-actions">
            <a href="{{ route('admin.administrators.index') }}" class="ad-btn ad-light">
                ← Danh sách
            </a>
            <a href="{{ route('admin.administrators.edit', $administrator) }}"
               class="ad-btn ad-primary">
                Chỉnh sửa
            </a>
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
                <div>{{ $error }}</div>
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
                        <span class="ad-badge {{ $administrator->status === 'ACTIVE' ? 'ad-green' : 'ad-red' }}">
                            {{ $administrator->status === 'ACTIVE' ? 'Hoạt động' : 'Ngừng hoạt động' }}
                        </span>
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
                <div><dt>Mã tài khoản</dt><dd><strong>{{ $user?->business_code ?? '-' }}</strong></dd></div>
                <div><dt>Số điện thoại</dt><dd>{{ $user?->phone ?? '-' }}</dd></div>
                <div><dt>Vai trò</dt><dd>{{ $user?->role?->name ?? '-' }}</dd></div>
                <div>
                    <dt>Trạng thái</dt>
                    <dd>
                        <span class="ad-badge {{ $isActive ? 'ad-green' : 'ad-red' }}">
                            {{ $isActive ? 'Hoạt động' : 'Đã khóa / Ngừng hoạt động' }}
                        </span>
                    </dd>
                </div>
            </dl>

            @if($user)
                <a href="{{ route('admin.users.show', $user) }}"
                   class="ad-btn ad-light ad-account-link">
                    Xem chi tiết tài khoản
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
            <form method="POST"
                  action="{{ route('admin.administrators.update-status', $administrator) }}"
                  onsubmit="return confirm('Bạn có chắc muốn khóa quản trị viên này?');">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="LOCKED">

                <button type="submit" class="ad-btn ad-danger">
                    Khóa quản trị viên
                </button>
            </form>
        @elseif($user && $user->status === 'LOCKED' && $administrator->status === 'INACTIVE')
            <form method="POST"
                  action="{{ route('admin.administrators.update-status', $administrator) }}"
                  onsubmit="return confirm('Xác nhận mở khóa quản trị viên này?');">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="ACTIVE">

                <button type="submit" class="ad-btn ad-primary">
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

@push('styles')
<style>
.ad-detail{min-width:0}
.ad-heading{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;margin-bottom:22px}
.ad-heading .page-description{margin:7px 0 0;color:#64748b}
.ad-heading-actions{display:flex;gap:10px;flex-wrap:wrap}
.ad-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:20px}
.ad-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:24px;box-shadow:0 4px 20px rgba(15,23,42,.035);min-width:0}
.ad-card h2{font-size:17px;color:#0f172a;margin:0 0 20px}
.ad-details{margin:0}
.ad-details>div{display:grid;grid-template-columns:145px minmax(0,1fr);gap:12px;padding:13px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.ad-details>div:last-child{border-bottom:0}
.ad-details dt{color:#64748b}
.ad-details dd{margin:0;color:#0f172a;overflow-wrap:anywhere}
.ad-badge{display:inline-block;padding:6px 10px;border-radius:30px;font-size:11px;font-weight:700}
.ad-green{background:#dcfce7;color:#166534}
.ad-red{background:#fee2e2;color:#991b1b}
.ad-management p{font-size:13px;color:#64748b;line-height:1.7;margin:0 0 18px}
.ad-notice{background:#fffbeb;border:1px solid #fde68a;border-radius:9px;color:#92400e;padding:13px 15px;font-size:13px}
.ad-btn{display:inline-flex;justify-content:center;align-items:center;text-decoration:none;border:1px solid transparent;border-radius:9px;padding:10px 16px;min-height:41px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer}
.ad-primary{background:#2563eb;color:#fff}
.ad-light{background:#fff;color:#334155;border-color:#cbd5e1}
.ad-danger{background:#dc2626;color:#fff}
.ad-account-link{margin-top:23px}
.ad-alert{padding:13px 16px;border-radius:9px;margin-bottom:17px;font-size:13px}
.ad-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.ad-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
@media(max-width:800px){.ad-grid{grid-template-columns:1fr}}
@media(max-width:560px){.ad-card{padding:17px}.ad-details>div{grid-template-columns:1fr;gap:5px}}
</style>
@endpush