@extends('admin.layouts.app')
@section('admin-page', 'visit-statuses-edit')

@section('title', 'Sửa trạng thái lượt khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Cấu hình trạng thái</span>
    <span aria-hidden="true">/</span>
    <span>Trạng thái lượt khám</span>
    <span aria-hidden="true">/</span>
    @include('admin.layouts.partials.status-badge', ['badgeCode' => $visitStatus->name])
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="admin-page-header">
        <div>

        <h1 class="page-title">
            Sửa trạng thái lượt khám
        </h1>

        <p class="page-description">
            Cập nhật cấu hình cho trạng thái
            {{ \App\Models\AdminDisplay::label($visitStatus->name) }}.
        </p>

        </div>
        <a href="{{ route('admin.visit-statuses.show', $visitStatus) }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>


    <div class="card">

        <form novalidate
            method="POST"
            action="{{ route(
                'admin.visit-statuses.update',
                $visitStatus
            ) }}"
        >

            @csrf
            @method('PUT')


            <div class="admin-u-6691f14f">


                <div>

                    <label class="admin-u-63101fce">
                        Tên trạng thái
                    </label>

                    <input class="admin-u-f3c8faba"
                        type="text"
                        value="{{ \App\Models\AdminDisplay::label($visitStatus->name) }}"
                        disabled

                    >

                    <small class="admin-u-a6f48144">
                        Tên trạng thái là khóa nghiệp vụ và không được thay đổi tại đây.
                    </small>

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="description"

                    >
                        Mô tả
                    </label>

                    <textarea class="admin-u-419e8d7e"
                        id="description"
                        name="description"
                        rows="4"

                    >{{ old(
                        'description',
                        $visitStatus->description
                    ) }}</textarea>

                    @error('description')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="process_order"

                    >
                        Thứ tự xử lý
                    </label>

                    <input class="admin-u-419e8d7e"
                        type="number"
                        id="process_order"
                        name="process_order"
                        min="0"
                        value="{{ old(
                            'process_order',
                            $visitStatus->process_order
                        ) }}"
                        required

                    >

                    @error('process_order')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce">
                        Cho phép chuyển trạng thái
                    </label>

                    <select class="admin-u-184ecf5a"
                        name="allows_transition"
                        required

                    >

                        <option
                            value="1"
                            @selected(
                                (string) old(
                                    'allows_transition',
                                    (int) $visitStatus->allows_transition
                                ) === '1'
                            )
                        >
                            Có
                        </option>

                        <option
                            value="0"
                            @selected(
                                (string) old(
                                    'allows_transition',
                                    (int) $visitStatus->allows_transition
                                ) === '0'
                            )
                        >
                            Không
                        </option>

                    </select>

                    @error('allows_transition')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce">
                        Trạng thái hoạt động
                    </label>

                    <select class="admin-u-184ecf5a"
                        name="is_active"
                        required

                    >

                        <option
                            value="1"
                            @selected(
                                (string) old(
                                    'is_active',
                                    (int) $visitStatus->is_active
                                ) === '1'
                            )
                        >
                            Đang hoạt động
                        </option>

                        <option
                            value="0"
                            @selected(
                                (string) old(
                                    'is_active',
                                    (int) $visitStatus->is_active
                                ) === '0'
                            )
                        >
                            Ngừng hoạt động
                        </option>

                    </select>

                    @error('is_active')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div class="admin-u-5786f359">

                    <a
                        href="{{ route(
                            'admin.visit-statuses.show',
                            $visitStatus
                        ) }}"
                        class="admin-btn admin-btn-secondary"
                    >Hủy</a>

                    <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>

                </div>

            </div>

        </form>

    </div>

@endsection
