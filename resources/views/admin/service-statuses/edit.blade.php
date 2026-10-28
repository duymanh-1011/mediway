@extends('admin.layouts.app')
@section('admin-page', 'service-statuses-edit')

@section('title', 'Sửa trạng thái dịch vụ')

@section('content')

    <div class="status-edit-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Cấu hình trạng thái</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.service-statuses.index') }}">
                Trạng thái dịch vụ
            </a>

            <span aria-hidden="true">/</span>

            <a href="{{ route(
        'admin.service-statuses.show',
        $status->id
    ) }}">
                {{ \App\Models\AdminDisplay::label($status->name, 'service') }}
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Sửa
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header status-edit-header">

            <div>


                <h1>
                    Sửa trạng thái dịch vụ
                </h1>

                <p>
                    Cập nhật mô tả, quyền chuyển tiếp
                    và trạng thái sử dụng.
                </p>

            </div>


        </div>


        {{-- Back --}}
        <div class="status-back-wrapper">

            <a href="{{ route(
        'admin.service-statuses.show',
        $status->id
    ) }}" class="admin-btn admin-btn-secondary">← Quay lại</a>

        </div>


        <div class="status-edit-layout">

            {{-- Form --}}
            <div class="status-form-card">

                <div class="status-card-header">

                    <div>

                        <h2>
                            Thông tin trạng thái
                        </h2>

                        <p>
                            Chỉ các trường được phép mới có thể chỉnh sửa.
                        </p>

                    </div>

                </div>


                <form novalidate method="POST" action="{{ route(
        'admin.service-statuses.update',
        $status->id
    ) }}">

                    @csrf
                    @method('PATCH')


                    <div class="status-form-body">

                        {{-- Name --}}
                        <div class="status-form-group">

                            <label>
                                Tên trạng thái
                            </label>

                            <input type="text" class="status-control status-control-readonly" value="{{ \App\Models\AdminDisplay::label($status->name, 'service') }}"
                                readonly>

                            <div class="status-help">
                                Tên trạng thái là khóa nghiệp vụ
                                và không được thay đổi tùy tiện.
                            </div>

                        </div>


                        {{-- Description --}}
                        <div class="status-form-group">

                            <label for="description">
                                Mô tả
                            </label>

                            <textarea id="description" name="description" rows="4" maxlength="255" class="status-textarea
                                            @error('description')
                                                status-control-error
                                            @enderror" placeholder="Nhập mô tả trạng thái...">{{ old(
        'description',
        $status->description
    ) }}</textarea>

                            <div class="status-help">
                                Tối đa 255 ký tự.
                            </div>

                            @error('description')

                                <div class="status-error">
                                    {{ \App\Models\AdminDisplay::text($message) }}
                                </div>

                            @enderror

                        </div>


                        {{-- Allows Transition --}}
                        <div class="status-form-group">

                            <label for="allows_transition">
                                Cho phép chuyển trạng thái
                                <span class="required">*</span>
                            </label>

                            <select id="allows_transition" name="allows_transition" class="status-control
                                            @error('allows_transition')
                                                status-control-error
                                            @enderror">

                                <option value="">
                                    -- Chọn quyền chuyển trạng thái --
                                </option>

                                <option value="1" @selected(
                                    old(
                                        'allows_transition',
                                        $status->allows_transition
                                        ? '1'
                                        : '0'
                                    ) === '1'
                                )>
                                    Cho phép
                                </option>

                                <option value="0" @selected(
                                    old(
                                        'allows_transition',
                                        $status->allows_transition
                                        ? '1'
                                        : '0'
                                    ) === '0'
                                )>
                                    Không cho phép
                                </option>

                            </select>

                            <div class="status-help">
                                Xác định trạng thái này có cho phép
                                tiếp tục chuyển sang bước nghiệp vụ
                                tiếp theo hay không.
                            </div>

                            @error('allows_transition')

                                <div class="status-error">
                                    {{ \App\Models\AdminDisplay::text($message) }}
                                </div>

                            @enderror

                        </div>


                        {{-- Usage Status --}}
                        <div class="status-form-group">

                            <label>
                                Trạng thái
                            </label>

                            <select name="is_active" class="status-control">

                                <option value="">
                                    Tất cả
                                </option>

                                <option value="1" @selected(request('is_active') === '1')>Đang hoạt động</option>

                                <option value="0" @selected(request('is_active') === '0')>Ngừng hoạt động</option>

                            </select>

                        </div>


                        {{-- Actions --}}
                        <div class="status-form-actions">

                            <a href="{{ route(
        'admin.service-statuses.show',
        $status->id
    ) }}" class="admin-btn admin-btn-secondary">Hủy</a>

                            <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>

                        </div>

                </form>

            </div>


            {{-- Side --}}
            <div class="status-edit-side">

                {{-- Policy --}}
                <div class="status-policy-card">

                    <div class="status-policy-icon">
                        !
                    </div>

                    <div>

                        <strong>
                            Chính sách dữ liệu
                        </strong>

                        <p>
                            Không đổi tên trạng thái tùy tiện.
                            Không thay đổi thứ tự xử lý tại màn hình này.
                            Không xóa vĩnh viễn trạng thái đã được sử dụng.
                        </p>

                    </div>

                </div>


                {{-- Current --}}
                <div class="status-current-card">

                    <div class="status-current-header">
                        Trạng thái hiện tại
                    </div>


                    <div class="status-current-item">

                        <span>
                            Tên
                        </span>

                        <strong class="status-current-name">@include('admin.layouts.partials.status-badge', ['badgeCode' => $status->name, 'badgeContext' => 'service'])</strong>

                    </div>


                    <div class="status-current-item">

                        <span>
                            Chuyển tiếp
                        </span>

                        @if ($status->allows_transition)

                            <strong class="text-success">
                                Cho phép
                            </strong>

                        @else

                            <strong class="text-muted">
                                Không cho phép
                            </strong>

                        @endif

                    </div>



<div class="status-form-group">

    <label for="is_active">
        Trạng thái
        <span class="required">*</span>
    </label>

    <select
        id="is_active"
        name="is_active"
        class="status-control
            @error('is_active')
                status-control-error
            @enderror"
    >

        <option value="">
            -- Chọn trạng thái sử dụng --
        </option>

        <option
            value="1"
            @selected(
                old(
                    'is_active',
                    $status->is_active
                        ? '1'
                        : '0'
                ) === '1'
            )
        >
            Đang sử dụng
        </option>

        <option
            value="0"
            @selected(
                old(
                    'is_active',
                    $status->is_active
                        ? '1'
                        : '0'
                ) === '0'
            )
        >
            Ngừng sử dụng
        </option>

    </select>

    <div class="status-help">
        Chọn trạng thái này còn được sử dụng
        trong hệ thống hay tạm ngừng sử dụng.
    </div>

    @error('is_active')
        <div class="status-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>

</div>


                </div>

            </div>

        </div>

    </div>




@endsection
