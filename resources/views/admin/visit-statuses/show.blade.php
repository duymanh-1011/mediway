@extends('admin.layouts.app')
@section('admin-page', 'visit-statuses-show')

@section('title', 'Chi tiết trạng thái lượt khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Cấu hình trạng thái</span>
    <span aria-hidden="true">/</span>
    <span>Trạng thái lượt khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">@include('admin.layouts.partials.status-badge', ['badgeCode' => $visitStatus->name])</strong>
@endsection

@section('content')

    <div class="admin-u-e0f559a1">

        <div>

            <h1 class="page-title">
                {{ \App\Models\AdminDisplay::label($visitStatus->name) }}
            </h1>

            <p class="page-description">
                Chi tiết cấu hình trạng thái lượt khám.
            </p>

        </div>


        <div class="admin-u-5786f359">

            <a
                href="{{ route('admin.visit-statuses.index') }}"
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>

            <a
                href="{{ route(
                    'admin.visit-statuses.edit',
                    $visitStatus
                ) }}"
                class="admin-btn admin-btn-secondary"
            >Sửa</a>

        </div>

    </div>


    <div class="card">

        <div class="admin-u-6b3ec5d8">

            <div>
                <strong>
                    Tên trạng thái
                </strong>

                <div class="admin-u-a5595d11">@include('admin.layouts.partials.status-badge', ['badgeCode' => $visitStatus->name])</div>
            </div>


            <div>
                <strong>
                    Thứ tự xử lý
                </strong>

                <div class="admin-u-a5595d11">
                    {{ $visitStatus->process_order }}
                </div>
            </div>


            <div>
                <strong>
                    Cho phép chuyển trạng thái
                </strong>

                <div class="admin-u-a5595d11">

                    @if ($visitStatus->allows_transition)

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ALLOWED'])

                    @else

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'DENIED'])

                    @endif

                </div>
            </div>


            <div>
                <strong>Trạng thái</strong>

                <div class="admin-u-a5595d11">

                    @if ($visitStatus->is_active)

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                    @else

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

                    @endif

                </div>
            </div>


            <div>
                <strong>
                    Số lượt khám đang sử dụng
                </strong>

                <div class="admin-u-a5595d11">
                    {{ $visitStatus->medical_visits_count }}
                </div>
            </div>


            <div>
                <strong>
                    Ngày tạo
                </strong>

                <div class="admin-u-a5595d11">
                    {{ $visitStatus->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>

        </div>


        <hr class="admin-u-20f76a32">


        <div>

            <strong>
                Mô tả
            </strong>

            <div class="admin-u-c6ec35a1">
                {{ $visitStatus->description ?: 'Chưa có mô tả.' }}
            </div>

        </div>

    </div>

@endsection
