@extends('admin.layouts.app')
@section('admin-page', 'payment-transactions-show')

@section('title', 'Chi tiết giao dịch thanh toán')

@section('content')

    @php
        $resultClass = match (
        strtoupper($transaction->transaction_result ?? '')
        ) {
            'SUCCESS' => 'success',
            'FAILED' => 'failed',
            default => 'default',
        };
    @endphp


    <div class="transaction-detail-page">

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Thanh toán</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.payment-transactions.index') }}">
                Giao dịch thanh toán
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                {{ $transaction->transaction_code }}
            </strong>

        </nav>


        <div class="transaction-detail-header">

            <div>

                <div class="transaction-module-label">
                    THANH TOÁN
                </div>

                <h1>
                    Chi tiết giao dịch
                </h1>

                <p>
                    Dữ liệu giao dịch chỉ đọc và được giữ nguyên lịch sử.
                </p>

            </div>


            <span class="
                    transaction-result
                    {{ $resultClass }}
                ">
                {{ \App\Models\AdminDisplay::label($transaction->transaction_result) }}
            </span>

        </div>


        <div class="transaction-actions">

            <a href="{{ route('admin.payment-transactions.index') }}" class="transaction-btn transaction-btn-secondary">
                ← Quay lại danh sách
            </a>


            @if ($payable)

                    <a href="{{ route(
                    'admin.payables.show',
                    $payable->id
                ) }}" class="transaction-btn transaction-btn-primary">
                        Xem khoản phải thanh toán
                    </a>

            @endif

        </div>


        <div class="transaction-card">

            <div class="transaction-card-header">
                <h2>
                    Thông tin giao dịch
                </h2>
            </div>


            <div class="transaction-info-grid">

                <div>
                    <span>Mã giao dịch</span>

                    <strong class="transaction-code">
                        {{ $transaction->transaction_code }}
                    </strong>
                </div>


                <div>
                    <span>Khoản phải thanh toán</span>

                    <strong>
                        {{ $payable?->payable_code ?? '—' }}
                    </strong>
                </div>


                <div>
                    <span>Lượt khám</span>

                    <strong>
                        {{ $visit?->visit_code ?? '—' }}
                    </strong>
                </div>


                <div>
                    <span>Phương thức</span>

                    <strong>
                        {{ \App\Models\AdminDisplay::label($transaction->payment_method) }}
                    </strong>
                </div>


                <div>
                    <span>Kết quả</span>

                    <strong>
                        {{ \App\Models\AdminDisplay::label($transaction->transaction_result) }}
                    </strong>
                </div>


                <div>
                    <span>Số tiền</span>

                    <strong class="transaction-amount">
                        {{ number_format(
        (float) $transaction->amount,
        0,
        ',',
        '.'
    ) }}
                        ₫
                    </strong>
                </div>

            </div>

        </div>


        <div class="transaction-card">

            <div class="transaction-card-header">
                <h2>
                    Thông tin nhà cung cấp / thu tiền
                </h2>
            </div>


            <div class="transaction-info-grid">

                <div>
                    <span>Mã giao dịch nhà cung cấp</span>

                    <strong>
                        {{
        $transaction->provider_transaction_code
        ?: '—'
                        }}
                    </strong>
                </div>


                <div>
                    <span>Nhân viên thu tiền</span>

                    <strong>
                        {{ $collector?->full_name ?? '—' }}
                    </strong>

                    @if ($collector?->employee_code)
                        <small>
                            {{ $collector->employee_code }}
                        </small>
                    @endif
                </div>


                <div>
                    <span>Nội dung thanh toán</span>

                    <strong>
                        {{ $transaction->payment_content ?: '—' }}
                    </strong>
                </div>

            </div>

        </div>


        <div class="transaction-card">

            <div class="transaction-card-header">
                <h2>
                    Mốc thời gian
                </h2>
            </div>


            <div class="transaction-time-grid">

                <div>
                    <span>Bắt đầu</span>

                    <strong>
                        {{
        $transaction->started_at
        ? $transaction->started_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>


                <div>
                    <span>Thanh toán</span>

                    <strong>
                        {{
        $transaction->paid_at
        ? $transaction->paid_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>


                <div>
                    <span>Xác nhận</span>

                    <strong>
                        {{
        $transaction->confirmed_at
        ? $transaction->confirmed_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>

            </div>

        </div>


        @if ($transaction->transaction_result === 'FAILED')

            <div class="transaction-card transaction-failure-card">

                <div class="transaction-card-header">
                    <h2>
                        Thông tin thất bại
                    </h2>
                </div>


                <div class="transaction-text-block">

                    <span>
                        Lý do thất bại
                    </span>

                    <p>
                        {{
                $transaction->failure_reason
                ?: 'Không có thông tin.'
                            }}
                    </p>

                </div>

            </div>

        @endif


        <div class="transaction-card">

            <div class="transaction-card-header">
                <h2>
                    Ghi chú
                </h2>
            </div>


            <div class="transaction-text-block">

                <p>
                    {{ $transaction->notes ?: 'Không có ghi chú.' }}
                </p>

            </div>

        </div>


        @if ($payable)

            <div class="transaction-card">

                <div class="transaction-card-header">
                    <h2>
                        Khoản phải thanh toán liên quan
                    </h2>
                </div>


                <div class="transaction-info-grid">

                    <div>
                        <span>Mã khoản thu</span>

                        <strong>
                            {{ $payable->payable_code }}
                        </strong>
                    </div>


                    <div>
                        <span>Loại khoản thu</span>

                        <strong>
                            @if ($payable->payment_type === 'EXAMINATION_FEE')
                                Phí khám
                            @elseif ($payable->payment_type === 'SERVICE_FEE')
                                Phí dịch vụ
                            @else
                                {{ \App\Models\AdminDisplay::label($payable->payment_type) }}
                            @endif
                        </strong>
                    </div>


                    <div>
                        <span>Trạng thái khoản thu</span>

                        <strong>
                            {{ \App\Models\AdminDisplay::label($payable->paymentStatus?->name ?? '—') }}
                        </strong>
                    </div>


                    <div>
                        <span>Tổng tiền phải thu</span>

                        <strong>
                            {{ number_format(
                (float) $payable->amount,
                0,
                ',',
                '.'
            ) }}
                            ₫
                        </strong>
                    </div>

                </div>

            </div>

        @endif


        <div class="transaction-card">

            <div class="transaction-card-header">
                <h2>
                    Thông tin hệ thống
                </h2>
            </div>


            <div class="transaction-time-grid">

                <div>
                    <span>Tạo lúc</span>

                    <strong>
                        {{
        $transaction->created_at
        ? $transaction->created_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>


                <div>
                    <span>Cập nhật lúc</span>

                    <strong>
                        {{
        $transaction->updated_at
        ? $transaction->updated_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>

            </div>

        </div>


        <div class="transaction-policy">

            <strong>
                Dữ liệu lịch sử tài chính
            </strong>

            <p>
                Giao dịch thanh toán chỉ được xem và tra cứu.
                Không có chức năng sửa hoặc xóa.
            </p>

        </div>

    </div>




@endsection
