@extends('admin.layouts.app')

@section('title', 'Chi tiết phiếu chỉ định')

@section('content')

    <div class="service-order-show-page">

        {{-- Breadcrumb --}}
        <div class="order-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.service-orders.index') }}">
                Chỉ định dịch vụ
            </a>

            <span>/</span>

            <strong>
                {{ $order->service_order_code }}
            </strong>

        </div>


        {{-- Header --}}
        <div class="order-header">

            <div>

                <div class="order-module-label">
                    PHIẾU CHỈ ĐỊNH DỊCH VỤ
                </div>

                <h1>
                    {{ $order->service_order_code }}
                </h1>

                <p>
                    Chi tiết phiếu chỉ định và các dịch vụ thuộc phiếu.
                </p>

            </div>


            @php
                $orderStatusClass = match ($order->status) {
                    'COMPLETED' => 'order-status-completed',
                    'ACTIVE' => 'order-status-active',
                    'CANCELLED' => 'order-status-cancelled',
                    default => 'order-status-default',
                };
            @endphp

            <span class="order-header-status {{ $orderStatusClass }}">
                {{ $order->status }}
            </span>

        </div>


        {{-- Thông báo --}}
        @if (session('success'))

            <div class="order-alert order-alert-success">
                {{ session('success') }}
            </div>

        @endif

        @if (session('error'))

            <div class="order-alert order-alert-error">
                {{ session('error') }}
            </div>

        @endif


        {{-- Back --}}
        <div class="order-back-wrapper">

            <a
                href="{{ route('admin.service-orders.index') }}"
                class="order-back-btn"
            >
                ← Quay lại danh sách
            </a>

        </div>


        {{-- Main information --}}
        <div class="order-card">

            <div class="order-card-header">

                <div>

                    <h2>
                        Thông tin phiếu chỉ định
                    </h2>

                    <p>
                        Thông tin chính của phiếu dịch vụ.
                    </p>

                </div>

            </div>


            <div class="order-info-grid">

                <div class="order-info-item">

                    <div class="order-info-label">
                        Mã phiếu chỉ định
                    </div>

                    <div class="order-code">
                        {{ $order->service_order_code }}
                    </div>

                </div>


                <div class="order-info-item">

                    <div class="order-info-label">
                        Lượt khám
                    </div>

                    @if ($order->medicalVisit)

                        <div class="order-value-code">
                            {{ $order->medicalVisit->visit_code }}
                        </div>

                    @else

                        <div class="order-empty-value">
                            —
                        </div>

                    @endif

                </div>


                <div class="order-info-item">

                    <div class="order-info-label">
                        Bác sĩ chỉ định
                    </div>

                    @if ($order->orderedByDoctor)

                        <div class="order-doctor">

                            <strong>
                                {{ $order->orderedByDoctor->full_name }}
                            </strong>

                            <span>
                                {{ $order->orderedByDoctor->doctor_code }}
                            </span>

                        </div>

                    @else

                        <div class="order-empty-value">
                            —
                        </div>

                    @endif

                </div>


                <div class="order-info-item">

                    <div class="order-info-label">
                        Thời gian chỉ định
                    </div>

                    <div class="order-value">

                        @if ($order->ordered_at)

                            {{ $order->ordered_at->format('d/m/Y H:i:s') }}

                        @else

                            —

                        @endif

                    </div>

                </div>


                <div class="order-info-item">

                    <div class="order-info-label">
                        Trạng thái phiếu
                    </div>

                    <span class="order-status-badge {{ $orderStatusClass }}">
                        {{ $order->status }}
                    </span>

                </div>


                <div class="order-info-item">

                    <div class="order-info-label">
                        Số dịch vụ
                    </div>

                    <div class="order-count-badge">
                        {{ $order->items->count() }}
                    </div>

                </div>

            </div>


            <div class="order-text-section">

                <div class="order-info-label">
                    Lý do chỉ định
                </div>

                <div class="order-text-value">
                    {{ $order->reason ?? 'Chưa có thông tin.' }}
                </div>

            </div>


            <div class="order-text-section">

                <div class="order-info-label">
                    Hướng dẫn / lưu ý
                </div>

                <div class="order-text-value">
                    {{ $order->instructions ?? 'Chưa có hướng dẫn.' }}
                </div>

            </div>

        </div>


        {{-- Service items --}}
        <div class="order-card">

            <div class="order-card-header order-list-header">

                <div>

                    <h2>
                        Chi tiết dịch vụ
                    </h2>

                    <p>
                        Dịch vụ, thanh toán, trạng thái thực hiện
                        và kết quả liên quan.
                    </p>

                </div>


                <div class="order-total-items">

                    Tổng cộng

                    <strong>
                        {{ $order->items->count() }}
                    </strong>

                    dịch vụ

                </div>

            </div>


            @forelse ($order->items as $item)

                @php
                    $serviceStatusName =
                        $item->serviceStatus?->name ?? 'UNKNOWN';

                    $serviceStatusClass = match ($serviceStatusName) {
                        'PENDING' => 'service-status-pending',
                        'PROCESSING' => 'service-status-processing',
                        'COMPLETED' => 'service-status-completed',
                        'CANCELLED' => 'service-status-cancelled',
                        default => 'service-status-default',
                    };

                    $payable =
                        $payablesByItem->get($item->id);

                    $paymentStatusName =
                        $payable?->payment_status_name ?? null;

                    $allowsServiceExecution =
                        $payable
                            ? (bool) $payable->allows_service_execution
                            : false;

                    $canPay =
                        $payable
                        &&
                        in_array(
                            $paymentStatusName,
                            [
                                'UNPAID',
                                'PENDING',
                            ],
                            true
                        );

                    $canStart =
                        $serviceStatusName === 'PENDING'
                        &&
                        $payable
                        &&
                        $allowsServiceExecution;

                    $canCreateResult =
                        $serviceStatusName === 'PROCESSING'
                        &&
                        !$item->result;

                    $canComplete =
                        $serviceStatusName === 'PROCESSING'
                        &&
                        (bool) $item->result;
                @endphp


                <div class="service-item">

                    {{-- Item Header --}}
                    <div class="service-item-header">

                        <div class="service-item-title">

                            <div class="service-number">
                                {{ $loop->iteration }}
                            </div>

                            <div>

                                @if ($item->medicalService)

                                    <h3>
                                        {{ $item->medicalService->name }}
                                    </h3>

                                    <div class="service-code">
                                        {{ $item->medicalService->service_code }}
                                    </div>

                                @else

                                    <h3>
                                        Dịch vụ không xác định
                                    </h3>

                                @endif

                            </div>

                        </div>


                        <span class="service-status-badge {{ $serviceStatusClass }}">
                            {{ $serviceStatusName }}
                        </span>

                    </div>


                    {{-- Action bar --}}
                    <div class="service-action-bar">

                        <div class="service-action-status">

                            @if (!$payable)

                                <span class="action-message action-message-warning">
                                    Chưa có khoản phải thanh toán.
                                </span>

                            @elseif ($serviceStatusName === 'PENDING' && !$allowsServiceExecution)

                                <span class="action-message action-message-warning">
                                    Cần hoàn tất thanh toán trước khi thực hiện dịch vụ.
                                </span>

                            @elseif ($serviceStatusName === 'PENDING' && $allowsServiceExecution)

                                <span class="action-message action-message-success">
                                    Đã thanh toán. Dịch vụ có thể bắt đầu thực hiện.
                                </span>

                            @elseif ($serviceStatusName === 'PROCESSING' && !$item->result)

                                <span class="action-message action-message-info">
                                    Dịch vụ đang thực hiện. Có thể nhập kết quả.
                                </span>

                            @elseif ($serviceStatusName === 'PROCESSING' && $item->result)

                                <span class="action-message action-message-success">
                                    Đã có kết quả. Có thể hoàn tất dịch vụ.
                                </span>

                            @elseif ($serviceStatusName === 'COMPLETED')

                                <span class="action-message action-message-success">
                                    Dịch vụ đã hoàn thành.
                                </span>

                            @elseif ($serviceStatusName === 'CANCELLED')

                                <span class="action-message action-message-danger">
                                    Dịch vụ đã bị hủy.
                                </span>

                            @endif

                        </div>


                        <div class="service-actions">

                            {{-- Thanh toán --}}
                            @if (
                                $canPay
                                &&
                                Route::has(
                                    'admin.payables.payment-transactions.create'
                                )
                            )

                                <a
                                    href="{{ route(
                                        'admin.payables.payment-transactions.create',
                                        $payable->id
                                    ) }}"
                                    class="service-action-btn service-action-pay"
                                >
                                    Thanh toán
                                </a>

                            @endif


                            {{-- Bắt đầu thực hiện --}}
                            @if (
                                $canStart
                                &&
                                Route::has(
                                    'admin.service-order-items.start'
                                )
                            )

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.service-order-items.start',
                                        $item
                                    ) }}"
                                    class="inline-action-form"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="service-action-btn service-action-start"
                                        onclick="return confirm(
                                            'Xác nhận bắt đầu thực hiện dịch vụ này?'
                                        )"
                                    >
                                        Bắt đầu thực hiện
                                    </button>

                                </form>

                            @endif


                            {{-- Nhập kết quả --}}
                            @if (
                                $canCreateResult
                                &&
                                Route::has(
                                    'admin.service-order-items.service-results.create'
                                )
                            )

                                <a
                                    href="{{ route(
                                        'admin.service-order-items.service-results.create',
                                        $item
                                    ) }}"
                                    class="service-action-btn service-action-result"
                                >
                                    Nhập kết quả
                                </a>

                            @endif


                            {{-- Xem kết quả --}}
                            @if (
                                $item->result
                                &&
                                Route::has(
                                    'admin.service-results.show'
                                )
                            )

                                <a
                                    href="{{ route(
                                        'admin.service-results.show',
                                        $item->result->id
                                    ) }}"
                                    class="service-action-btn service-action-view"
                                >
                                    Xem kết quả
                                </a>

                            @endif


                            {{-- Hoàn tất dịch vụ --}}
                            @if (
                                $canComplete
                                &&
                                Route::has(
                                    'admin.service-order-items.complete'
                                )
                            )

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.service-order-items.complete',
                                        $item
                                    ) }}"
                                    class="inline-action-form"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="service-action-btn service-action-complete"
                                        onclick="return confirm(
                                            'Xác nhận hoàn tất dịch vụ này? Sau khi hoàn tất, trạng thái dịch vụ sẽ chuyển sang COMPLETED.'
                                        )"
                                    >
                                        Hoàn tất dịch vụ
                                    </button>

                                </form>

                            @endif

                        </div>

                    </div>


                    {{-- Item basic information --}}
                    <div class="service-info-grid">

                        <div class="service-info-item">

                            <div class="service-info-label">
                                Giá tại thời điểm chỉ định
                            </div>

                            <div class="service-price">
                                {{ number_format(
                                    (float) $item->price_at_order,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                                đ
                            </div>

                        </div>


                        <div class="service-info-item">

                            <div class="service-info-label">
                                Người thực hiện
                            </div>

                            @if ($item->performer)

                                <div class="service-performer">

                                    <strong>
                                        {{ $item->performer->full_name }}
                                    </strong>

                                    <span>
                                        {{ $item->performer->employee_code }}
                                    </span>

                                </div>

                            @else

                                <div class="order-empty-value">
                                    Chưa phân công
                                </div>

                            @endif

                        </div>


                        <div class="service-info-item">

                            <div class="service-info-label">
                                Bắt đầu thực hiện
                            </div>

                            <div class="service-time">

                                @if ($item->started_at)

                                    {{ $item->started_at->format(
                                        'd/m/Y H:i:s'
                                    ) }}

                                @else

                                    —

                                @endif

                            </div>

                        </div>


                        <div class="service-info-item">

                            <div class="service-info-label">
                                Hoàn thành
                            </div>

                            <div class="service-time">

                                @if ($item->completed_at)

                                    {{ $item->completed_at->format(
                                        'd/m/Y H:i:s'
                                    ) }}

                                @else

                                    —

                                @endif

                            </div>

                        </div>

                    </div>


                    {{-- Execution notes --}}
                    @if ($item->execution_notes)

                        <div class="service-note">

                            <div class="service-info-label">
                                Ghi chú thực hiện
                            </div>

                            <p>
                                {{ $item->execution_notes }}
                            </p>

                        </div>

                    @endif


                    {{-- Related data --}}
                    <div class="service-related-grid">

                        {{-- Result --}}
                        <div class="related-card">

                            <div class="related-card-header">

                                <div>

                                    <div class="related-label">
                                        KẾT QUẢ DỊCH VỤ
                                    </div>

                                    <h4>
                                        Kết quả
                                    </h4>

                                </div>


                                @if ($item->result)

                                    <span class="related-exists">
                                        Có kết quả
                                    </span>

                                @else

                                    <span class="related-empty">
                                        Chưa có
                                    </span>

                                @endif

                            </div>


                            @if ($item->result)

                                <div class="related-content">

                                    <div class="related-row">

                                        <span>
                                            Tóm tắt
                                        </span>

                                        <strong>
                                            {{ $item->result->result_summary ?? '—' }}
                                        </strong>

                                    </div>


                                    <div class="related-row">

                                        <span>
                                            Kết luận
                                        </span>

                                        <strong>
                                            {{ $item->result->result_conclusion ?? '—' }}
                                        </strong>

                                    </div>


                                    <div class="related-row">

                                        <span>
                                            Thời gian trả kết quả
                                        </span>

                                        <strong>

                                            @if ($item->result->resulted_at)

                                                {{ $item->result
                                                    ->resulted_at
                                                    ->format(
                                                        'd/m/Y H:i:s'
                                                    ) }}

                                            @else

                                                —

                                            @endif

                                        </strong>

                                    </div>


                                    @if (
                                        Route::has(
                                            'admin.service-results.show'
                                        )
                                    )

                                        <div class="related-action">

                                            <a
                                                href="{{ route(
                                                    'admin.service-results.show',
                                                    $item->result->id
                                                ) }}"
                                                class="related-link"
                                            >
                                                Xem chi tiết kết quả
                                            </a>

                                        </div>

                                    @endif

                                </div>

                            @else

                                <div class="related-no-data">

                                    @if ($serviceStatusName === 'PROCESSING')

                                        Dịch vụ đang thực hiện và chưa nhập kết quả.

                                    @elseif ($serviceStatusName === 'PENDING')

                                        Cần bắt đầu thực hiện dịch vụ trước khi nhập kết quả.

                                    @else

                                        Dịch vụ này chưa có kết quả.

                                    @endif

                                </div>

                            @endif

                        </div>


                        {{-- Payable --}}
                        <div class="related-card">

                            <div class="related-card-header">

                                <div>

                                    <div class="related-label">
                                        THANH TOÁN
                                    </div>

                                    <h4>
                                        Khoản phải thanh toán
                                    </h4>

                                </div>


                                @if ($payable)

                                    <span class="related-exists">
                                        Có khoản thu
                                    </span>

                                @else

                                    <span class="related-empty">
                                        Chưa có
                                    </span>

                                @endif

                            </div>


                            @if ($payable)

                                <div class="related-content">

                                    <div class="related-row">

                                        <span>
                                            Mã khoản
                                        </span>

                                        <strong class="related-code">
                                            {{ $payable->payable_code }}
                                        </strong>

                                    </div>


                                    <div class="related-row">

                                        <span>
                                            Loại
                                        </span>

                                        <strong>
                                            {{ $payable->payment_type }}
                                        </strong>

                                    </div>


                                    <div class="related-row">

                                        <span>
                                            Số tiền
                                        </span>

                                        <strong class="related-amount">

                                            {{ number_format(
                                                (float) $payable->amount,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                            đ

                                        </strong>

                                    </div>


                                    <div class="related-row">

                                        <span>
                                            Trạng thái thanh toán
                                        </span>

                                        <strong>
                                            {{ $paymentStatusName ?? '—' }}
                                        </strong>

                                    </div>


                                    <div class="related-action related-action-group">

                                        @if (
                                            Route::has(
                                                'admin.payables.show'
                                            )
                                        )

                                            <a
                                                href="{{ route(
                                                    'admin.payables.show',
                                                    $payable->id
                                                ) }}"
                                                class="related-link"
                                            >
                                                Xem khoản thanh toán
                                            </a>

                                        @endif


                                        @if (
                                            $canPay
                                            &&
                                            Route::has(
                                                'admin.payables.payment-transactions.create'
                                            )
                                        )

                                            <a
                                                href="{{ route(
                                                    'admin.payables.payment-transactions.create',
                                                    $payable->id
                                                ) }}"
                                                class="related-link related-link-pay"
                                            >
                                                Thanh toán
                                            </a>

                                        @endif

                                    </div>

                                </div>

                            @else

                                <div class="related-no-data">
                                    Chưa phát sinh khoản phải thanh toán
                                    cho dịch vụ này.
                                </div>

                            @endif

                        </div>

                    </div>

                </div>

            @empty

                <div class="order-empty">

                    <div class="order-empty-icon">
                        !
                    </div>

                    <strong>
                        Phiếu chưa có dịch vụ
                    </strong>

                    <p>
                        Không tìm thấy chi tiết dịch vụ
                        thuộc phiếu chỉ định này.
                    </p>

                </div>

            @endforelse

        </div>


        {{-- System timestamps --}}
        <div class="order-card">

            <div class="order-card-header">

                <div>

                    <h2>
                        Thông tin hệ thống
                    </h2>

                    <p>
                        Thời gian tạo và cập nhật bản ghi.
                    </p>

                </div>

            </div>


            <div class="system-grid">

                <div class="system-item">

                    <div class="order-info-label">
                        Ngày tạo
                    </div>

                    <div class="order-value">

                        @if ($order->created_at)

                            {{ $order->created_at->format(
                                'd/m/Y H:i:s'
                            ) }}

                        @else

                            —

                        @endif

                    </div>

                </div>


                <div class="system-item">

                    <div class="order-info-label">
                        Cập nhật gần nhất
                    </div>

                    <div class="order-value">

                        @if ($order->updated_at)

                            {{ $order->updated_at->format(
                                'd/m/Y H:i:s'
                            ) }}

                        @else

                            —

                        @endif

                    </div>

                </div>

            </div>

        </div>


        {{-- Policy --}}
        <div class="order-policy-box">

            <div class="order-policy-icon">
                !
            </div>

            <div>

                <strong>
                    Chính sách dữ liệu
                </strong>

                <p>
                    Không xóa cứng phiếu chỉ định hoặc
                    chi tiết dịch vụ đã được thực hiện hoặc
                    đã phát sinh thanh toán.
                </p>

            </div>

        </div>

    </div>


    <style>
        .service-order-show-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .service-order-show-page * {
            box-sizing: border-box;
        }


        /* =========================
           ALERT
        ========================= */

        .order-alert {
            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 10px;

            font-size: 13px;

            line-height: 1.6;
        }

        .order-alert-success {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #166534;
        }

        .order-alert-error {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #991b1b;
        }


        /* =========================
           BREADCRUMB
        ========================= */

        .order-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .order-breadcrumb a {
            color: #64748b;

            text-decoration: none;
        }

        .order-breadcrumb a:hover {
            color: var(--primary);
        }

        .order-breadcrumb strong {
            color: #334155;
        }


        /* =========================
           HEADER
        ========================= */

        .order-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;
        }

        .order-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .order-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;
        }

        .order-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .order-header-status,
        .order-status-badge,
        .service-status-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }

        .order-header-status {
            padding: 8px 13px;

            font-size: 11px;
        }

        .order-status-active {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .order-status-completed {
            background: #dcfce7;

            color: #15803d;
        }

        .order-status-cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }

        .order-status-default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =========================
           BACK
        ========================= */

        .order-back-wrapper {
            margin-bottom: 20px;
        }

        .order-back-btn {
            color: #475569;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;
        }

        .order-back-btn:hover {
            color: var(--primary);
        }


        /* =========================
           CARD
        ========================= */

        .order-card {
            overflow: hidden;

            margin-bottom: 22px;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .order-card-header {
            padding: 18px 20px;

            border-bottom: 1px solid var(--border);
        }

        .order-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .order-card-header p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;

            line-height: 1.5;
        }

        .order-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .order-total-items {
            color: #64748b;

            font-size: 12px;

            white-space: nowrap;
        }

        .order-total-items strong {
            color: #0f172a;

            font-size: 14px;
        }


        /* =========================
           ORDER INFO
        ========================= */

        .order-info-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }

        .order-info-item {
            padding: 18px 20px;

            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .order-info-item:nth-child(3n) {
            border-right: none;
        }

        .order-info-label,
        .service-info-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .order-code,
        .order-value-code,
        .related-code {
            color: #1d4ed8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-weight: 750;
        }

        .order-code {
            display: inline-flex;

            padding: 6px 9px;

            border: 1px solid #dbeafe;
            border-radius: 8px;

            background: #eff6ff;

            font-size: 12px;
        }

        .order-value-code {
            font-size: 12px;
        }

        .order-value {
            color: #334155;

            font-size: 12px;

            line-height: 1.5;
        }

        .order-empty-value {
            color: #94a3b8;

            font-size: 12px;
        }

        .order-doctor {
            display: grid;

            gap: 3px;
        }

        .order-doctor strong {
            color: #0f172a;

            font-size: 12px;
        }

        .order-doctor span {
            color: #94a3b8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
        }

        .order-count-badge {
            display: inline-flex;

            min-width: 32px;
            height: 30px;

            align-items: center;
            justify-content: center;

            padding: 0 8px;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 12px;
            font-weight: 800;
        }

        .order-text-section {
            padding: 17px 20px;

            border-bottom: 1px solid #edf2f7;
        }

        .order-text-section:last-child {
            border-bottom: none;
        }

        .order-text-value {
            color: #334155;

            font-size: 13px;

            line-height: 1.7;
        }


        /* =========================
           SERVICE ITEM
        ========================= */

        .service-item {
            border-bottom: 1px solid var(--border);
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-item-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 18px;

            padding: 18px 20px;

            background: #fbfdff;
        }

        .service-item-title {
            display: flex;
            align-items: center;

            gap: 12px;
        }

        .service-number {
            display: flex;

            width: 34px;
            height: 34px;

            flex: 0 0 34px;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 12px;
            font-weight: 800;
        }

        .service-item-title h3 {
            margin: 0;

            color: #0f172a;

            font-size: 14px;
            font-weight: 750;
        }

        .service-code {
            margin-top: 4px;

            color: #64748b;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
        }


        /* =========================
           ACTION BAR
        ========================= */

        .service-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 12px 20px;

            border-top: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;

            background: #ffffff;
        }

        .service-action-status {
            min-width: 0;
        }

        .action-message {
            display: inline-block;

            font-size: 11px;

            line-height: 1.5;
        }

        .action-message-warning {
            color: #92400e;
        }

        .action-message-success {
            color: #15803d;
        }

        .action-message-info {
            color: #1d4ed8;
        }

        .action-message-danger {
            color: #b91c1c;
        }

        .service-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;

            gap: 8px;
        }

        .inline-action-form {
            margin: 0;
        }

        .service-action-btn {
            display: inline-flex;

            min-height: 34px;

            align-items: center;
            justify-content: center;

            padding: 7px 12px;

            border-radius: 8px;

            font-family:
                "Times New Roman",
                Times,
                serif;

            font-size: 11px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;
        }

        .service-action-pay {
            border: 1px solid #16a34a;

            background: #16a34a;

            color: #ffffff;
        }

        .service-action-pay:hover {
            background: #15803d;
        }

        .service-action-start {
            border: 1px solid #2563eb;

            background: #2563eb;

            color: #ffffff;
        }

        .service-action-start:hover {
            background: #1d4ed8;
        }

        .service-action-result {
            border: 1px solid #7c3aed;

            background: #7c3aed;

            color: #ffffff;
        }

        .service-action-result:hover {
            background: #6d28d9;
        }

        .service-action-view {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #334155;
        }

        .service-action-view:hover {
            background: #f8fafc;
        }

        .service-action-complete {
            border: 1px solid #16a34a;

            background: #16a34a;

            color: #ffffff;
        }

        .service-action-complete:hover {
            background: #15803d;
        }


        /* =========================
           SERVICE STATUS
        ========================= */

        .service-status-pending {
            background: #fef3c7;

            color: #b45309;
        }

        .service-status-processing {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .service-status-completed {
            background: #dcfce7;

            color: #15803d;
        }

        .service-status-cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }

        .service-status-default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =========================
           SERVICE INFO
        ========================= */

        .service-info-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .service-info-item {
            padding: 17px 20px;

            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .service-info-item:nth-child(4n) {
            border-right: none;
        }

        .service-price {
            color: #15803d;

            font-size: 13px;
            font-weight: 800;
        }

        .service-performer {
            display: grid;

            gap: 3px;
        }

        .service-performer strong {
            color: #0f172a;

            font-size: 12px;
        }

        .service-performer span {
            color: #94a3b8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
        }

        .service-time {
            color: #475569;

            font-size: 12px;

            line-height: 1.5;
        }

        .service-note {
            padding: 16px 20px;

            border-bottom: 1px solid #edf2f7;

            background: #fffdf7;
        }

        .service-note p {
            margin: 0;

            color: #475569;

            font-size: 12px;

            line-height: 1.65;
        }


        /* =========================
           RELATED DATA
        ========================= */

        .service-related-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 14px;

            padding: 18px 20px;

            background: #f8fafc;
        }

        .related-card {
            overflow: hidden;

            border: 1px solid #e2e8f0;
            border-radius: 12px;

            background: #ffffff;
        }

        .related-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 12px;

            padding: 14px 15px;

            border-bottom: 1px solid #edf2f7;
        }

        .related-label {
            margin-bottom: 4px;

            color: #2563eb;

            font-size: 9px;
            font-weight: 800;

            letter-spacing: .06em;
        }

        .related-card-header h4 {
            margin: 0;

            color: #0f172a;

            font-size: 12px;
            font-weight: 750;
        }

        .related-exists,
        .related-empty {
            display: inline-flex;

            padding: 5px 8px;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 800;

            white-space: nowrap;
        }

        .related-exists {
            background: #dcfce7;

            color: #15803d;
        }

        .related-empty {
            background: #f1f5f9;

            color: #64748b;
        }

        .related-content {
            padding: 14px 15px;
        }

        .related-row {
            display: grid;

            grid-template-columns:
                minmax(110px, .7fr)
                minmax(0, 1.3fr);

            gap: 12px;

            padding: 9px 0;

            border-bottom: 1px dashed #e2e8f0;
        }

        .related-row:last-of-type {
            border-bottom: none;
        }

        .related-row span {
            color: #64748b;

            font-size: 10px;
        }

        .related-row strong {
            color: #334155;

            font-size: 11px;
            font-weight: 650;

            text-align: right;

            line-height: 1.5;
        }

        .related-amount {
            color: #15803d !important;

            font-weight: 800 !important;
        }

        .related-no-data {
            padding: 20px 15px;

            color: #94a3b8;

            font-size: 11px;

            line-height: 1.6;
        }

        .related-action {
            margin-top: 12px;
        }

        .related-action-group {
            display: flex;
            flex-wrap: wrap;

            gap: 8px;
        }

        .related-link {
            display: inline-flex;

            min-height: 34px;

            align-items: center;
            justify-content: center;

            padding: 7px 12px;

            border: 1px solid #bfdbfe;
            border-radius: 8px;

            background: #ffffff;

            color: #2563eb;

            font-size: 11px;
            font-weight: 700;

            text-decoration: none;
        }

        .related-link:hover {
            background: #eff6ff;
        }

        .related-link-pay {
            border-color: #bbf7d0;

            color: #15803d;
        }

        .related-link-pay:hover {
            background: #f0fdf4;
        }


        /* =========================
           SYSTEM
        ========================= */

        .system-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .system-item {
            padding: 18px 20px;

            border-right: 1px solid #edf2f7;
        }

        .system-item:last-child {
            border-right: none;
        }


        /* =========================
           EMPTY
        ========================= */

        .order-empty {
            padding: 45px 20px;

            text-align: center;
        }

        .order-empty-icon {
            display: flex;

            width: 44px;
            height: 44px;

            align-items: center;
            justify-content: center;

            margin: 0 auto 12px;

            border-radius: 50%;

            background: #f1f5f9;

            color: #64748b;

            font-size: 18px;
            font-weight: 800;
        }

        .order-empty strong {
            display: block;

            color: #0f172a;

            font-size: 14px;
        }

        .order-empty p {
            margin: 6px 0 0;

            color: #64748b;

            font-size: 12px;
        }


        /* =========================
           POLICY
        ========================= */

        .order-policy-box {
            display: flex;
            align-items: flex-start;

            gap: 12px;

            padding: 16px;

            border: 1px solid #fde68a;
            border-radius: 12px;

            background: #fffbeb;
        }

        .order-policy-icon {
            display: flex;

            width: 30px;
            height: 30px;

            flex: 0 0 30px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #f59e0b;

            color: #ffffff;

            font-size: 12px;
            font-weight: 800;
        }

        .order-policy-box strong {
            display: block;

            margin-bottom: 5px;

            color: #92400e;

            font-size: 12px;
        }

        .order-policy-box p {
            margin: 0;

            color: #78350f;

            font-size: 11px;

            line-height: 1.65;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .order-info-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .order-info-item:nth-child(3n) {
                border-right: 1px solid #edf2f7;
            }

            .order-info-item:nth-child(2n) {
                border-right: none;
            }

            .service-info-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .service-info-item:nth-child(4n) {
                border-right: 1px solid #edf2f7;
            }

            .service-info-item:nth-child(2n) {
                border-right: none;
            }

        }


        @media (max-width: 768px) {

            .order-header {
                flex-direction: column;
            }

            .order-header h1 {
                font-size: 24px;
            }

            .order-info-grid,
            .service-info-grid,
            .service-related-grid,
            .system-grid {
                grid-template-columns: 1fr;
            }

            .order-info-item,
            .service-info-item,
            .system-item {
                border-right: none !important;
            }

            .order-list-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .service-item-header {
                flex-direction: column;
            }

            .service-action-bar {
                align-items: flex-start;

                flex-direction: column;
            }

            .service-actions {
                width: 100%;

                justify-content: flex-start;
            }

            .related-row {
                grid-template-columns: 1fr;
            }

            .related-row strong {
                text-align: left;
            }

        }

    </style>

@endsection