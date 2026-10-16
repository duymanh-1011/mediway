@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết dịch vụ y tế - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Dịch vụ y tế /
        {{ $medicalService->service_code }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $medicalService->name }}
            </h1>

            <p class="page-description">
                Mã dịch vụ:
                {{ $medicalService->service_code }}
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.medical-services.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.medical-services.edit',
                    $medicalService
                ) }}"
                class="btn btn-primary"
            >
                Sửa dịch vụ
            </a>

        </div>

    </div>

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

            <div class="stat-value">
                {{
                    number_format(
                        $medicalService->price,
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
                Tổng giá trị chỉ định
            </div>

            <div class="stat-value">
                {{
                    number_format(
                        $orderedValue,
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

        <div class="card">

            <h3 class="section-title">
                Thông tin dịch vụ
            </h3>

            <div class="detail-row">

                <div class="detail-label">
                    Mã dịch vụ
                </div>

                <div class="detail-value">
                    {{ $medicalService->service_code }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên dịch vụ
                </div>

                <div class="detail-value">
                    {{ $medicalService->name }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Nhóm dịch vụ
                </div>

                <div class="detail-value">
                    {{ $medicalService
                        ->serviceGroup
                        ?->name
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Giá
                </div>

                <div class="detail-value">
                    {{
                        number_format(
                            $medicalService->price,
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
                    {{ $medicalService->unit }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Thời lượng
                </div>

                <div class="detail-value">

                    @if (
                        $medicalService
                            ->estimated_duration_minutes
                    )

                        {{ $medicalService
                            ->estimated_duration_minutes }}
                        phút

                    @else
                        —
                    @endif

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Thứ tự
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
                        === 'ACTIVE'
                    )

                        <span
                            class="badge badge-success"
                        >
                            ACTIVE
                        </span>

                    @else

                        <span
                            class="badge badge-danger"
                        >
                            INACTIVE
                        </span>

                    @endif

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Mô tả
                </div>

                <div class="detail-value">
                    {{ $medicalService
                        ->description
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Chuẩn bị
                </div>

                <div class="detail-value">
                    {{ $medicalService
                        ->preparation_instructions
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Lưu ý
                </div>

                <div class="detail-value">
                    {{ $medicalService
                        ->precautions
                        ?? '—' }}
                </div>

            </div>

        </div>

        <div class="card">

            <h3 class="section-title">
                Chỉ định gần đây
            </h3>

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Phiếu</th>
                            <th>Lượt khám</th>
                            <th>Bệnh nhân</th>
                            <th>Giá tại thời điểm chỉ định</th>
                            <th>Trạng thái</th>
                            <th>Ngày chỉ định</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse (
                        $recentOrderItems
                        as $item
                    )

                        <tr>

                            <td>
                                {{ $item
                                    ->service_order_code }}
                            </td>

                            <td>
                                {{ $item->visit_code }}
                            </td>

                            <td>

                                {{ $item->patient_code }}

                                <br>

                                <small>
                                    {{ $item->patient_name }}
                                </small>

                            </td>

                            <td>
                                {{
                                    number_format(
                                        $item->price_at_order,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                đ
                            </td>

                            <td>
                                {{ $item->service_status }}
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
                                colspan="6"
                                class="empty-state"
                            >
                                Dịch vụ này chưa được chỉ định.
                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection

@push('styles')

<style>

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 20px;
    }

    .header-actions {
        display: flex;
        gap: 8px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .stat-card {
        padding: 16px 18px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .stat-label {
        color: #64748b;
        font-size: 12px;
        margin-bottom: 6px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
    }

    .detail-grid {
        display: grid;
        grid-template-columns:
            minmax(320px, 0.8fr)
            minmax(0, 1.5fr);
        gap: 20px;
    }

    .section-title {
        margin: 0 0 16px;
        font-size: 17px;
    }

    .detail-row {
        display: grid;
        grid-template-columns:
            150px
            minmax(0, 1fr);
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .detail-value {
        color: #334155;
        font-size: 13px;
        line-height: 1.55;
    }

    .empty-state {
        padding: 30px !important;
        text-align: center !important;
        color: #94a3b8 !important;
    }

    @media (max-width: 1000px) {

        .detail-grid {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 700px) {

        .stats-grid {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush