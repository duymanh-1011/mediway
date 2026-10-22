@extends('admin.layouts.app')
@section('admin-page', 'medicines-show')

@section(
    'title',
    'Chi tiết thuốc - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Thuốc</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ $medicine->medicine_code }}</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $medicine->name }}
            </h1>

            <p class="page-description">
                Mã thuốc:
                {{ $medicine->medicine_code }}
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route('admin.medicines.index') }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.medicines.edit',
                    $medicine
                ) }}"
                class="btn btn-primary"
            >
                Sửa thuốc
            </a>

        </div>

    </div>

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Số lần được kê
            </div>

            <div class="stat-value">
                {{ $prescriptionItemCount }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Tổng số lượng đã kê
            </div>

            <div class="stat-value">
                {{ $totalQuantityPrescribed }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Trạng thái
            </div>

            <div class="stat-text">

                @if ($medicine->status === 'ACTIVE')

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

    </div>

    <div class="card">

        <h3 class="section-title">
            Thông tin thuốc
        </h3>

        <div class="info-grid">

            <div class="detail-row">

                <div class="detail-label">
                    Mã thuốc
                </div>

                <div class="detail-value">
                    {{ $medicine->medicine_code }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên thuốc
                </div>

                <div class="detail-value">
                    {{ $medicine->name }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Hoạt chất
                </div>

                <div class="detail-value">
                    {{ $medicine
                        ->active_ingredient
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Hàm lượng
                </div>

                <div class="detail-value">
                    {{ $medicine->strength ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Dạng bào chế
                </div>

                <div class="detail-value">
                    {{ $medicine->dosage_form ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Đơn vị
                </div>

                <div class="detail-value">
                    {{ $medicine->unit ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Đường dùng
                </div>

                <div class="detail-value">
                    {{ $medicine
                        ->route_of_administration
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Nhà sản xuất
                </div>

                <div class="detail-value">
                    {{ $medicine->manufacturer ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Nước sản xuất
                </div>

                <div class="detail-value">
                    {{ $medicine
                        ->country_of_origin
                        ?? '—' }}
                </div>

            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Mô tả
            </div>

            <div class="detail-value long-text">
                {{ $medicine->description ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Chỉ định
            </div>

            <div class="detail-value long-text">
                {{ $medicine->indications ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Chống chỉ định
            </div>

            <div class="detail-value long-text">
                {{ $medicine
                    ->contraindications
                    ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Cảnh báo
            </div>

            <div class="detail-value long-text">
                {{ $medicine->warnings ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Tác dụng phụ thường gặp
            </div>

            <div class="detail-value long-text">
                {{ $medicine
                    ->common_side_effects
                    ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Hướng dẫn bảo quản
            </div>

            <div class="detail-value long-text">
                {{ $medicine
                    ->storage_instructions
                    ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Ghi chú
            </div>

            <div class="detail-value long-text">
                {{ $medicine->notes ?? '—' }}
            </div>

        </div>

    </div>

    <div
        class="admin-u-a26bda7d card"

    >

        <h3 class="section-title">
            Lịch sử kê thuốc gần đây
        </h3>

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Đơn thuốc</th>
                        <th>Số lượng</th>
                        <th>Đơn vị dùng</th>
                        <th>Liều/lần</th>
                        <th>Số lần/ngày</th>
                        <th>Số ngày</th>
                        <th>Cách dùng</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $recentPrescriptionItems
                    as $item
                )

                    <tr>

                        @include('admin.layouts.partials.row-number', ['rows' => $recentPrescriptionItems, 'index' => $loop->index])
                        <td>
                            #{{ $item->prescription_id }}
                        </td>

                        <td>
                            {{ $item->quantity }}
                        </td>

                        <td>
                            {{ $item->usage_unit ?? '—' }}
                        </td>

                        <td>
                            {{ $item->dose_per_use ?? '—' }}
                        </td>

                        <td>
                            {{ $item->times_per_day ?? '—' }}
                        </td>

                        <td>

                            @if ($item->duration_days)

                                {{ $item->duration_days }}
                                ngày

                            @else
                                —
                            @endif

                        </td>

                        <td>
                            {{ $item->usage_method ?? '—' }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            class="empty-state"
                        >
                            Thuốc này chưa từng được kê.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection
