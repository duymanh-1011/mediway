@extends('admin.layouts.app')
@section('admin-page', 'medical-services-show')

@section(
    'title',
    'Chi tiết dịch vụ y tế - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Dịch vụ y tế</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ $medicalService->service_code }}</strong>
@endsection


@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $medicalService->name }}
            </h1>

            <p class="page-description">
                Mã dịch vụ:
                <strong>
                    {{ $medicalService->service_code }}
                </strong>
            </p>

        </div>


        <div class="header-actions">
            @include('admin.layouts.partials.catalog-status', ['record' => $medicalService, 'catalog' => 'medical-services'])

            <a
                href="{{ route(
                    'admin.medical-services.index'
                ) }}"
                class="admin-btn admin-btn-secondary"
            >
                ← Quay lại
            </a>

            <a
                href="{{ route(
                    'admin.medical-services.edit',
                    $medicalService
                ) }}"
                class="admin-btn admin-btn-secondary"
            >Sửa</a>

        </div>

    </div>


    {{-- =====================================================
         THỐNG KÊ
    ====================================================== --}}

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Số lần được chỉ định
            </div>

            <div class="stat-value">
                {{ $serviceOrderItemCount }}
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Giá hiện tại
            </div>

            <div class="stat-value stat-value-price">

                {{
                    number_format(
                        (float) $medicalService->price,
                        0,
                        ',',
                        '.'
                    )
                }}
                đ

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Tổng giá trị đã chỉ định
            </div>

            <div class="stat-value stat-value-price">

                {{
                    number_format(
                        (float) $orderedValue,
                        0,
                        ',',
                        '.'
                    )
                }}
                đ

            </div>

        </div>

    </div>


    <div class="detail-grid">

        {{-- =====================================================
             THÔNG TIN DỊCH VỤ
        ====================================================== --}}

        <div class="card">

            <h2 class="section-title">
                Thông tin dịch vụ
            </h2>


            <div class="detail-row">

                <div class="detail-label">
                    Mã dịch vụ
                </div>

                <div class="detail-value detail-code">
                    {{ $medicalService->service_code }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Tên dịch vụ
                </div>

                <div class="detail-value">
                    <strong>
                        {{ $medicalService->name }}
                    </strong>
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Nhóm dịch vụ
                </div>

                <div class="detail-value">

                    <div class="group-badges">

                        @forelse (
                            $medicalService->serviceGroups
                            as $group
                        )

                            <a class="group-badge" href="{{ route('admin.service-groups.show', $group) }}">

                                {{ $group->name }}

                                @if (
                                    $group->status
                                    ===
                                    'INACTIVE'
                                )

                                    <small>
                                        · Ngừng hoạt động
                                    </small>

                                @endif

                            </a>

                        @empty

                            <span class="text-muted">
                                Chưa phân nhóm
                            </span>

                        @endforelse

                    </div>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Giá dịch vụ
                </div>

                <div class="detail-value detail-price">

                    {{
                        number_format(
                            (float) $medicalService->price,
                            0,
                            ',',
                            '.'
                        )
                    }}
                    đ

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Đơn vị
                </div>

                <div class="detail-value">
                    {{ $medicalService->unit ?: '—' }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Thời lượng dự kiến
                </div>

                <div class="detail-value">

                    @if (
                        $medicalService
                            ->estimated_duration_minutes
                    )

                        {{
                            $medicalService
                                ->estimated_duration_minutes
                        }}
                        phút

                    @else

                        —

                    @endif

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Thứ tự hiển thị
                </div>

                <div class="detail-value">
                    {{ $medicalService->display_order }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Trạng thái
                </div>

                <div class="detail-value">

                    @if (
                        $medicalService->status
                        ===
                        'ACTIVE'
                    )

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                    @else

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

                    @endif

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Mô tả
                </div>

                <div class="detail-value detail-long-text">

                    {{
                        $medicalService
                            ->description
                        ?: 'Chưa có thông tin.'
                    }}

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Hướng dẫn chuẩn bị
                </div>

                <div class="detail-value detail-long-text">

                    {{
                        $medicalService
                            ->preparation_instructions
                        ?: 'Không có yêu cầu chuẩn bị đặc biệt.'
                    }}

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Lưu ý / Cảnh báo
                </div>

                <div class="detail-value detail-long-text">

                    {{
                        $medicalService
                            ->precautions
                        ?: 'Không có lưu ý.'
                    }}

                </div>

            </div>

        </div>


        {{-- =====================================================
             CHỈ ĐỊNH GẦN ĐÂY
        ====================================================== --}}

        <div class="card">

            <div class="section-heading">

                <div>

                    <h2 class="section-title">
                        Lịch sử chỉ định gần đây
                    </h2>

                    <p class="section-description">
                        Các lượt khám gần nhất đã sử dụng
                        dịch vụ này.
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Phiếu chỉ định</th>
                            <th>Lượt khám</th>
                            <th>Bệnh nhân</th>
                            <th>Giá chỉ định</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                        </tr>

                    </thead>


                    <tbody>

                    @forelse (
                        $recentOrderItems
                        as $item
                    )

                        @php
                            $itemStatus =
                                $item->service_status
                                ?? null;



                        @endphp


                        <tr>

                            @include('admin.layouts.partials.row-number', ['rows' => $recentOrderItems, 'index' => $loop->index])
                            <td>
                                <strong>
                                    {{
                                        $item
                                            ->service_order_code
                                    }}
                                </strong>
                            </td>


                            <td>
                                {{ $item->visit_code }}
                            </td>


                            <td>

                                <strong>
                                    {{ $item->patient_name }}
                                </strong>

                                <div class="sub-text">
                                    {{ $item->patient_code }}
                                </div>

                            </td>


                            <td class="price-cell">

                                {{
                                    number_format(
                                        (float)
                                        $item->price_at_order,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                đ

                            </td>


                            <td>

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => $itemStatus, 'badgeContext' => 'service'])

                            </td>


                            <td>

                                @if ($item->ordered_at)

                                    {{
                                        \Carbon\Carbon::parse(
                                            $item->ordered_at
                                        )->format(
                                            'd/m/Y H:i'
                                        )
                                    }}

                                @else

                                    —

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="empty-state"
                            >
                                Dịch vụ này chưa từng
                                được chỉ định.
                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection
