@extends('admin.layouts.app')
@section('admin-page', 'service-orders-show')

@section('title', 'Chi tiết phiếu chỉ định')

@section('content')

    <div class="service-order-show-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Dịch vụ</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.service-orders.index') }}">
                Chỉ định dịch vụ
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                {{ $order->service_order_code }}
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header order-header">

            <div>


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
                {{ \App\Models\AdminDisplay::label($order->status) }}
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
                        {{ \App\Models\AdminDisplay::label($order->status) }}
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
                        && (bool) $item->serviceStatus?->allows_transition
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
                        && (bool) $item->serviceStatus?->allows_transition
                        && (bool) $item->result?->hasContent();
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
                            {{ \App\Models\AdminDisplay::label($serviceStatusName) }}
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

                                <form novalidate
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

                                <form novalidate
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
                                            {{ \App\Models\AdminDisplay::label($payable->payment_type) }}
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
                                            {{ \App\Models\AdminDisplay::label($paymentStatusName ?? '—') }}
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




@endsection
