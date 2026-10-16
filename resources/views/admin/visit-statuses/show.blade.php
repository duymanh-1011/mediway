@extends('admin.layouts.app')

@section('title', 'Chi tiết trạng thái lượt khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Trạng thái lượt khám /
        {{ $visitStatus->name }}
    </div>
@endsection

@section('content')

    <div style="
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px;
    ">

        <div>

            <h1 class="page-title">
                {{ $visitStatus->name }}
            </h1>

            <p class="page-description">
                Chi tiết cấu hình trạng thái lượt khám.
            </p>

        </div>


        <div style="
            display: flex;
            gap: 8px;
        ">

            <a
                href="{{ route('admin.visit-statuses.index') }}"
                class="btn btn-secondary"
            >
                Quay lại
            </a>

            <a
                href="{{ route(
                    'admin.visit-statuses.edit',
                    $visitStatus
                ) }}"
                class="btn btn-primary"
            >
                Sửa
            </a>

        </div>

    </div>


    <div class="card">

        <div style="
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        ">

            <div>
                <strong>
                    Tên trạng thái
                </strong>

                <div style="margin-top: 6px;">
                    {{ $visitStatus->name }}
                </div>
            </div>


            <div>
                <strong>
                    Thứ tự xử lý
                </strong>

                <div style="margin-top: 6px;">
                    {{ $visitStatus->process_order }}
                </div>
            </div>


            <div>
                <strong>
                    Cho phép chuyển trạng thái
                </strong>

                <div style="margin-top: 6px;">

                    @if ($visitStatus->allows_transition)

                        <span class="badge badge-success">
                            Có
                        </span>

                    @else

                        <span class="badge badge-warning">
                            Không
                        </span>

                    @endif

                </div>
            </div>


            <div>
                <strong>
                    Hoạt động
                </strong>

                <div style="margin-top: 6px;">

                    @if ($visitStatus->is_active)

                        <span class="badge badge-success">
                            Đang hoạt động
                        </span>

                    @else

                        <span class="badge badge-danger">
                            Ngừng hoạt động
                        </span>

                    @endif

                </div>
            </div>


            <div>
                <strong>
                    Số lượt khám đang sử dụng
                </strong>

                <div style="margin-top: 6px;">
                    {{ $visitStatus->medical_visits_count }}
                </div>
            </div>


            <div>
                <strong>
                    Ngày tạo
                </strong>

                <div style="margin-top: 6px;">
                    {{ $visitStatus->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>

        </div>


        <hr style="
            margin: 24px 0;
            border: 0;
            border-top: 1px solid #e2e8f0;
        ">


        <div>

            <strong>
                Mô tả
            </strong>

            <div style="
                margin-top: 8px;
                line-height: 1.7;
                white-space: pre-line;
            ">
                {{ $visitStatus->description ?: 'Chưa có mô tả.' }}
            </div>

        </div>

    </div>

@endsection
