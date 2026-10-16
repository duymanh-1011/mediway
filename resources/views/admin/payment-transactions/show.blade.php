@extends('admin.layouts.app')

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

        <div class="transaction-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.payment-transactions.index') }}">
                Giao dịch thanh toán
            </a>

            <span>/</span>

            <strong>
                {{ $transaction->transaction_code }}
            </strong>

        </div>


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
                {{ $transaction->transaction_result }}
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
                        {{ $transaction->payment_method }}
                    </strong>
                </div>


                <div>
                    <span>Kết quả</span>

                    <strong>
                        {{ $transaction->transaction_result }}
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
                                {{ $payable->payment_type }}
                            @endif
                        </strong>
                    </div>


                    <div>
                        <span>Trạng thái khoản thu</span>

                        <strong>
                            {{ $payable->paymentStatus?->name ?? '—' }}
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


    <style>
        .transaction-detail-page {
            --primary: #2563eb;
            --border: #e2e8f0;

            width: 100%;
            color: #0f172a;
        }

        .transaction-detail-page * {
            box-sizing: border-box;
        }

        .transaction-breadcrumb {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            color: #94a3b8;
            font-size: 13px;
        }

        .transaction-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .transaction-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }

        .transaction-module-label {
            margin-bottom: 7px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .transaction-detail-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .transaction-detail-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .transaction-result {
            display: inline-flex;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
        }

        .transaction-result.success {
            background: #dcfce7;
            color: #15803d;
        }

        .transaction-result.failed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .transaction-result.default {
            background: #f1f5f9;
            color: #475569;
        }

        .transaction-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 20px;
        }

        .transaction-btn {
            display: inline-flex;
            height: 39px;
            align-items: center;
            justify-content: center;
            padding: 0 13px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
        }

        .transaction-btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .transaction-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .transaction-card {
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
        }

        .transaction-card-header {
            padding: 17px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .transaction-card-header h2 {
            margin: 0;
            font-size: 15px;
        }

        .transaction-info-grid,
        .transaction-time-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .transaction-info-grid>div,
        .transaction-time-grid>div {
            padding: 18px 20px;
            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .transaction-info-grid>div:nth-child(3n),
        .transaction-time-grid>div:nth-child(3n) {
            border-right: none;
        }

        .transaction-info-grid span,
        .transaction-time-grid span,
        .transaction-text-block span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .transaction-info-grid strong,
        .transaction-time-grid strong {
            font-size: 11px;
        }

        .transaction-info-grid small {
            display: block;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 9px;
        }

        .transaction-code {
            color: #1d4ed8 !important;
            font-family: Consolas, Monaco, monospace;
        }

        .transaction-amount {
            font-size: 14px !important;
        }

        .transaction-text-block {
            padding: 20px;
        }

        .transaction-text-block p {
            margin: 0;
            color: #475569;
            font-size: 11px;
            line-height: 1.7;
            white-space: pre-line;
        }

        .transaction-failure-card {
            border-color: #fecaca;
        }

        .transaction-failure-card .transaction-card-header {
            background: #fff7f7;
            color: #b91c1c;
        }

        .transaction-policy {
            padding: 15px 17px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #f8fafc;
        }

        .transaction-policy strong {
            display: block;
            margin-bottom: 5px;
            color: #334155;
            font-size: 11px;
        }

        .transaction-policy p {
            margin: 0;
            color: #64748b;
            font-size: 10px;
            line-height: 1.6;
        }

        @media (max-width: 800px) {

            .transaction-detail-header,
            .transaction-actions {
                flex-direction: column;
            }

            .transaction-actions .transaction-btn {
                width: 100%;
            }

            .transaction-info-grid,
            .transaction-time-grid {
                grid-template-columns: 1fr;
            }

            .transaction-info-grid>div,
            .transaction-time-grid>div {
                border-right: none;
            }
        }
    </style>

@endsection