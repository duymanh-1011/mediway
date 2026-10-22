@extends('admin.layouts.app')
@section('admin-page', 'payment-statuses-edit')

@section('title', 'Sửa trạng thái thanh toán')

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

            <a href="{{ route('admin.payment-statuses.index') }}">
                Trạng thái thanh toán
            </a>

            <span aria-hidden="true">/</span>

            <a href="{{ route(
        'admin.payment-statuses.show',
        $status->id
    ) }}">
                {{ \App\Models\AdminDisplay::label($status->name) }}
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
                    Sửa trạng thái thanh toán
                </h1>

                <p>
                    Cập nhật mô tả, quyền thực hiện dịch vụ
                    và trạng thái sử dụng.
                </p>

            </div>


        </div>


        <div class="status-back-wrapper">

            <a href="{{ route(
        'admin.payment-statuses.show',
        $status->id
    ) }}" class="status-back-btn">
                ← Quay lại chi tiết
            </a>

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
        'admin.payment-statuses.update',
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

                            <input type="text" class="status-control status-control-readonly" value="{{ \App\Models\AdminDisplay::label($status->name, 'payment') }}"
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


                        {{-- Allows service execution --}}
                        <div class="status-form-group">

                            <label for="allows_service_execution">
                                Cho phép thực hiện dịch vụ
                                <span class="required">*</span>
                            </label>

                            <select id="allows_service_execution" name="allows_service_execution" class="status-control
                                    @error('allows_service_execution')
                                        status-control-error
                                    @enderror">

                                <option value="">
                                    -- Chọn quyền thực hiện dịch vụ --
                                </option>

                                <option value="1" @selected(
                                    old(
                                        'allows_service_execution',
                                        $status->allows_service_execution
                                        ? '1'
                                        : '0'
                                    ) === '1'
                                )>
                                    Cho phép
                                </option>

                                <option value="0" @selected(
                                    old(
                                        'allows_service_execution',
                                        $status->allows_service_execution
                                        ? '1'
                                        : '0'
                                    ) === '0'
                                )>
                                    Không cho phép
                                </option>

                            </select>

                            <div class="status-help">
                                Xác định trạng thái thanh toán này có cho phép
                                tiếp tục thực hiện dịch vụ hay không.
                            </div>

                            @error('allows_service_execution')

                                <div class="status-error">
                                    {{ \App\Models\AdminDisplay::text($message) }}
                                </div>

                            @enderror

                        </div>


                        {{-- Usage status --}}
                        <div class="status-form-group">

                            <label for="is_active">
                                Trạng thái sử dụng
                                <span class="required">*</span>
                            </label>

                            <select id="is_active" name="is_active" class="status-control
                                    @error('is_active')
                                        status-control-error
                                    @enderror">

                                <option value="">
                                    -- Chọn trạng thái sử dụng --
                                </option>

                                <option value="1" @selected(
                                    old(
                                        'is_active',
                                        $status->is_active
                                        ? '1'
                                        : '0'
                                    ) === '1'
                                )>
                                    Đang sử dụng
                                </option>

                                <option value="0" @selected(
                                    old(
                                        'is_active',
                                        $status->is_active
                                        ? '1'
                                        : '0'
                                    ) === '0'
                                )>
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


                    <div class="status-form-actions">

                        <a href="{{ route(
        'admin.payment-statuses.show',
        $status->id
    ) }}" class="status-btn status-btn-secondary">Hủy</a>

                        <button type="submit" class="status-btn status-btn-primary">
                            Lưu thay đổi
                        </button>

                    </div>

                </form>

            </div>


            {{-- Side --}}
            <div class="status-edit-side">

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
                            Không hard-delete trạng thái đã được sử dụng.
                        </p>

                    </div>

                </div>


                <div class="status-current-card">

                    <div class="status-current-header">
                        Trạng thái hiện tại
                    </div>


                    <div class="status-current-item">

                        <span>
                            Tên
                        </span>

                        <strong class="status-current-name">
                            {{ \App\Models\AdminDisplay::label($status->name) }}
                        </strong>

                    </div>


                    <div class="status-current-item">

                        <span>
                            Thực hiện dịch vụ
                        </span>

                        @if ($status->allows_service_execution)

                            <strong class="text-success">
                                Cho phép
                            </strong>

                        @else

                            <strong class="text-muted">
                                Không cho phép
                            </strong>

                        @endif

                    </div>


                    <div class="status-current-item">

                        <span>
                            Trạng thái sử dụng
                        </span>

                        @if ($status->is_active)

                            <strong class="text-success">
                                Đang sử dụng
                            </strong>

                        @else

                            <strong class="text-danger">
                                Ngừng sử dụng
                            </strong>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>




@endsection
