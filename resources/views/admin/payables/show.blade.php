@extends('admin.layouts.app')

@section('title', 'Chi tiết khoản thanh toán')

@section('content')

    @php
        $statusName = strtoupper(
            $payable->paymentStatus?->name ?? ''
        );

        $statusClass = match ($statusName) {
            'PAID' => 'paid',
            'UNPAID' => 'unpaid',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            default => 'default',
        };

        $typeLabel = match ($payable->payment_type) {
            'EXAMINATION_FEE' => 'Phí khám',
            'SERVICE_FEE' => 'Phí dịch vụ',
            default => $payable->payment_type,
        };
    @endphp


    <div class="payable-detail-page">

        <div class="payable-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.payables.index') }}">
                Khoản phải thanh toán
            </a>

            <span>/</span>

            <strong>
                {{ $payable->payable_code }}
            </strong>

        </div>


        <div class="payable-detail-header">

            <div>
                <div class="payable-module-label">
                    THANH TOÁN
                </div>

                <h1>
                    Chi tiết khoản phải thanh toán
                </h1>

                <p>
                    Thông tin tài chính và lịch sử giao dịch liên quan.
                </p>
            </div>

            <span class="
                    payable-status
                    {{ $statusClass }}
                ">
                {{ $payable->paymentStatus?->name ?? '—' }}
            </span>

        </div>


        @if (session('success'))
            <div class="payable-alert payable-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="payable-alert payable-alert-error">
                {{ session('error') }}
            </div>
        @endif


        <div class="payable-actions">

            <a href="{{ route('admin.payables.index') }}" class="payable-btn payable-btn-secondary">
                ← Quay lại danh sách
            </a>


            @if (
                    !in_array(
                        $payable->paymentStatus?->name,
                        ['PAID', 'CANCELLED']
                    )
                )

                <label for="status-modal" class="payable-btn payable-btn-primary">
                    Đổi trạng thái
                </label>

                <label for="cancel-modal" class="payable-btn payable-btn-danger-outline">
                    Hủy nghiệp vụ
                </label>

            @endif

        </div>


        <div class="payable-card">

            <div class="payable-card-header">
                <div>
                    <h2>
                        Thông tin khoản phải thanh toán
                    </h2>

                    <p>
                        Thông tin nghiệp vụ chính.
                    </p>
                </div>
            </div>


            <div class="payable-info-grid">

                <div>
                    <span>Mã khoản thu</span>

                    <strong class="payable-code">
                        {{ $payable->payable_code }}
                    </strong>
                </div>


                <div>
                    <span>Lượt khám</span>

                    <strong>
                        {{ $visit?->visit_code ?? '—' }}
                    </strong>
                </div>


                <div>
                    <span>Loại khoản thu</span>

                    <strong>
                        {{ $typeLabel }}
                    </strong>
                </div>


                <div>
                    <span>Số tiền</span>

                    <strong class="payable-amount">
                        {{ number_format(
        (float) $payable->amount,
        0,
        ',',
        '.'
    ) }}
                        ₫
                    </strong>
                </div>


                <div>
                    <span>Trạng thái</span>

                    <strong>
                        {{ $payable->paymentStatus?->name ?? '—' }}
                    </strong>
                </div>


                <div>
                    <span>Số giao dịch</span>

                    <strong>
                        {{ $payable->transactions->count() }}
                    </strong>
                </div>

            </div>

        </div>


        <div class="payable-card">

            <div class="payable-card-header">
                <div>
                    <h2>
                        Nội dung thanh toán
                    </h2>
                </div>
            </div>


            <div class="payable-text-grid">

                <div>
                    <span>Mô tả</span>

                    <p>
                        {{ $payable->description ?: '—' }}
                    </p>
                </div>


                <div>
                    <span>Ghi chú</span>

                    <p>
                        {{ $payable->notes ?: 'Không có.' }}
                    </p>
                </div>

            </div>

        </div>


        <div class="payable-card">

            <div class="payable-card-header">
                <div>
                    <h2>
                        Thời gian thanh toán
                    </h2>
                </div>
            </div>


            <div class="payable-time-grid">

                <div>
                    <span>Khởi tạo lúc</span>

                    <strong>
                        {{
        $payable->generated_at
        ? $payable->generated_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>


                <div>
                    <span>Hạn thanh toán</span>

                    <strong>
                        {{
        $payable->due_at
        ? $payable->due_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>


                <div>
                    <span>Thanh toán lúc</span>

                    <strong>
                        {{
        $payable->paid_at
        ? $payable->paid_at->format(
            'd/m/Y H:i:s'
        )
        : 'Chưa thanh toán'
                        }}
                    </strong>
                </div>

            </div>

        </div>


        <div class="payable-card">

            <div class="payable-card-header">
                <div>
                    <h2>
                        Thông tin QR
                    </h2>

                    <p>
                        Dữ liệu QR được lưu cùng khoản thanh toán.
                    </p>
                </div>
            </div>


            <div class="payable-qr">

                @if ($payable->payment_qr_data)

                    <pre>{{ $payable->payment_qr_data }}</pre>

                @else

                    <span class="payable-empty">
                        Không có dữ liệu QR.
                    </span>

                @endif

            </div>

        </div>


        @if ($payable->payment_type === 'SERVICE_FEE')

            <div class="payable-card">

                <div class="payable-card-header">
                    <div>
                        <h2>
                            Dịch vụ liên quan
                        </h2>
                    </div>
                </div>


                <div class="payable-info-grid">

                    <div>
                        <span>Phiếu chỉ định</span>

                        <strong>
                            {{ $serviceContext?->service_order_code ?? '—' }}
                        </strong>
                    </div>


                    <div>
                        <span>Mã dịch vụ</span>

                        <strong>
                            {{ $serviceContext?->service_code ?? '—' }}
                        </strong>
                    </div>


                    <div>
                        <span>Tên dịch vụ</span>

                        <strong>
                            {{ $serviceContext?->service_name ?? '—' }}
                        </strong>
                    </div>


                    <div>
                        <span>Giá tại thời điểm chỉ định</span>

                        <strong>
                            @if ($serviceContext)

                                            {{ number_format(
                                    (float) $serviceContext->price_at_order,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                                            ₫

                            @else
                                —
                            @endif
                        </strong>
                    </div>

                </div>

            </div>

        @endif


        <div class="payable-card">

            <div class="payable-card-header">

                <div>
                    <h2>
                        Lịch sử giao dịch
                    </h2>

                    <p>
                        Giao dịch được giữ nguyên để bảo toàn lịch sử tài chính.
                    </p>
                </div>

                <span class="payable-count">
                    {{ $payable->transactions->count() }}
                </span>

            </div>


            <div class="payable-table-wrapper">

                <table class="payable-table">

                    <thead>
                        <tr>
                            <th>Mã giao dịch</th>
                            <th>Phương thức</th>
                            <th>Kết quả</th>
                            <th class="text-right">Số tiền</th>
                            <th>Bắt đầu</th>
                            <th>Xác nhận</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>


                    <tbody>

                        @forelse ($payable->transactions as $transaction)

                                            @php
                                                $resultClass = match (
                                                strtoupper(
                                                    $transaction->transaction_result
                                                )
                                                ) {
                                                    'SUCCESS' => 'success',
                                                    'FAILED' => 'failed',
                                                    default => 'default',
                                                };
                                            @endphp

                                            <tr>

                                                <td>
                                                    <strong class="payable-code">
                                                        {{ $transaction->transaction_code }}
                                                    </strong>
                                                </td>


                                                <td>
                                                    {{ $transaction->payment_method }}
                                                </td>


                                                <td>
                                                    <span class="
                                                                transaction-result
                                                                {{ $resultClass }}
                                                            ">
                                                        {{ $transaction->transaction_result }}
                                                    </span>
                                                </td>


                                                <td class="text-right">
                                                    <strong>
                                                        {{ number_format(
                                (float) $transaction->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                                                        ₫
                                                    </strong>
                                                </td>


                                                <td>
                                                    {{
                                $transaction->started_at
                                ? $transaction->started_at->format(
                                    'd/m/Y H:i'
                                )
                                : '—'
                                                        }}
                                                </td>


                                                <td>
                                                    {{
                                $transaction->confirmed_at
                                ? $transaction->confirmed_at->format(
                                    'd/m/Y H:i'
                                )
                                : '—'
                                                        }}
                                                </td>


                                                <td class="text-right">

                                                    <a href="{{ route(
                                'admin.payment-transactions.show',
                                $transaction->id
                            ) }}" class="payable-detail-link">
                                                        Chi tiết
                                                    </a>

                                                </td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="payable-empty-state">
                                    Chưa có giao dịch thanh toán.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        <div class="payable-card">

            <div class="payable-card-header">
                <h2>
                    Thông tin hệ thống
                </h2>
            </div>


            <div class="payable-time-grid">

                <div>
                    <span>Tạo lúc</span>

                    <strong>
                        {{
        $payable->created_at
        ? $payable->created_at->format(
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
        $payable->updated_at
        ? $payable->updated_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>
                </div>

            </div>

        </div>


        {{-- MODAL ĐỔI TRẠNG THÁI --}}
        @if (
                !in_array(
                    $payable->paymentStatus?->name,
                    ['PAID', 'CANCELLED']
                )
            )

            <form method="POST" action="{{ route(
                'admin.payables.update-status',
                $payable->id
            ) }}">

                @csrf
                @method('PATCH')


                <input
    id="status-modal"
    type="checkbox"
    class="payable-modal-toggle"
    @checked(
        $errors->has('payment_status_id')
        || $errors->has('confirm_update')
    )
>


                <div class="payable-modal">

                    <label for="status-modal" class="payable-modal-backdrop"></label>


                    <div class="payable-modal-dialog">

                        <h3>
                            Đổi trạng thái thanh toán
                        </h3>

                        <p>
                            Khoản phải thanh toán
                            <strong>
                                {{ $payable->payable_code }}
                            </strong>
                        </p>


                        <div class="payable-form-group">

                            <label for="payment_status_id">
                                Trạng thái mới
                            </label>

                            <select
    id="payment_status_id"
    name="payment_status_id"
    class="payable-control
        @error('payment_status_id')
            payable-control-error
        @enderror"
>

    @foreach ($paymentStatuses as $status)

        @if ($status->name !== 'CANCELLED')

            <option
                value="{{ $status->id }}"
                @selected(
                    (string) old(
                        'payment_status_id',
                        $payable->payment_status_id
                    ) === (string) $status->id
                )
            >
                {{ $status->name }}
            </option>

        @endif

    @endforeach

</select>

@error('payment_status_id')
    <div class="payable-error">
        {{ $message }}
    </div>
@enderror

                        </div>


                        <div class="payable-modal-actions">

                            <label for="status-modal" class="payable-btn payable-btn-secondary">
                                Quay lại
                            </label>

                            <button type="submit" name="confirm_update" value="1" class="payable-btn payable-btn-primary">
                                Xác nhận
                            </button>

                        </div>

                    </div>

                </div>

            </form>


            {{-- MODAL HỦY NGHIỆP VỤ --}}

            <form method="POST" action="{{ route(
                'admin.payables.cancel',
                $payable->id
            ) }}">

                @csrf
                @method('PATCH')


                <input
    id="cancel-modal"
    type="checkbox"
    class="payable-modal-toggle"
    @checked(
        $errors->has('cancel_reason')
        || $errors->has('confirm_cancel')
    )
>


                <div class="payable-modal">

                    <label for="cancel-modal" class="payable-modal-backdrop"></label>


                    <div class="payable-modal-dialog">

                        <h3>
                            Hủy khoản phải thanh toán
                        </h3>

                        <p>
                            Bản ghi không bị xóa.
                            Trạng thái sẽ chuyển sang
                            <strong>CANCELLED</strong>.
                        </p>


                        <div class="payable-form-group">

                            <label for="cancel_reason">
                                Lý do hủy
                            </label>

                            <textarea
    id="cancel_reason"
    name="cancel_reason"
    rows="4"
    class="
        payable-control
        payable-textarea
        @error('cancel_reason')
            payable-control-error
        @enderror
    "
    placeholder="Nhập lý do hủy..."
>{{ old('cancel_reason') }}</textarea>

@error('cancel_reason')
    <div class="payable-error">
        {{ $message }}
    </div>
@enderror
                        </div>


                        <div class="payable-modal-actions">

                            <label for="cancel-modal" class="payable-btn payable-btn-secondary">
                                Quay lại
                            </label>

                            <button type="submit" name="confirm_cancel" value="1" class="payable-btn payable-btn-danger">
                                Xác nhận hủy
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        @endif

    </div>


    <style>
        .payable-detail-page {
            --primary: #2563eb;
            --border: #e2e8f0;
            --muted: #64748b;

            width: 100%;
            color: #0f172a;
        }

        .payable-detail-page * {
            box-sizing: border-box;
        }

        .payable-breadcrumb {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            color: #94a3b8;
            font-size: 13px;
        }

        .payable-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .payable-detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .payable-module-label {
            margin-bottom: 7px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .payable-detail-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .payable-detail-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .payable-alert {
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 10px;
            font-size: 11px;
        }

        .payable-alert-success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
        }

        .payable-alert-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .payable-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 20px;
        }

        .payable-btn {
            display: inline-flex;
            min-width: 110px;
            height: 39px;
            align-items: center;
            justify-content: center;
            padding: 0 13px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .payable-btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .payable-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .payable-btn-danger,
        .payable-btn-danger-outline {
            border: 1px solid #dc2626;
        }

        .payable-btn-danger {
            background: #dc2626;
            color: #ffffff;
        }

        .payable-btn-danger-outline {
            background: #ffffff;
            color: #dc2626;
        }

        .payable-card {
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
        }

        .payable-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .payable-card-header h2 {
            margin: 0;
            font-size: 15px;
        }

        .payable-card-header p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 10px;
        }

        .payable-info-grid,
        .payable-time-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .payable-info-grid>div,
        .payable-time-grid>div {
            padding: 18px 20px;
            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .payable-info-grid>div:nth-child(3n),
        .payable-time-grid>div:nth-child(3n) {
            border-right: none;
        }

        .payable-info-grid span,
        .payable-time-grid span,
        .payable-text-grid span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .payable-info-grid strong,
        .payable-time-grid strong {
            font-size: 11px;
        }

        .payable-code {
            color: #1d4ed8 !important;
            font-family: Consolas, Monaco, monospace;
        }

        .payable-amount {
            font-size: 14px !important;
        }

        .payable-text-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .payable-text-grid>div {
            padding: 20px;
            border-right: 1px solid #edf2f7;
        }

        .payable-text-grid>div:last-child {
            border-right: none;
        }

        .payable-text-grid p {
            margin: 0;
            color: #475569;
            font-size: 11px;
            line-height: 1.65;
            white-space: pre-line;
        }

        .payable-status,
        .transaction-result {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
        }

        .payable-status.paid,
        .transaction-result.success {
            background: #dcfce7;
            color: #15803d;
        }

        .payable-status.unpaid,
        .transaction-result.failed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .payable-status.pending {
            background: #fef3c7;
            color: #b45309;
        }

        .payable-status.cancelled,
        .payable-status.default,
        .transaction-result.default {
            background: #f1f5f9;
            color: #475569;
        }

        .payable-qr {
            padding: 20px;
        }

        .payable-qr pre {
            overflow-x: auto;
            margin: 0;
            padding: 14px;
            border-radius: 9px;
            background: #f8fafc;
            color: #334155;
            font-size: 10px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .payable-table-wrapper {
            overflow-x: auto;
        }

        .payable-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .payable-table th {
            padding: 11px 14px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
        }

        .payable-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #edf2f7;
            color: #334155;
            font-size: 10px;
        }

        .text-right {
            text-align: right !important;
        }

        .payable-detail-link {
            display: inline-flex;
            height: 29px;
            align-items: center;
            padding: 0 10px;
            border: 1px solid #bfdbfe;
            border-radius: 7px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 9px;
            text-decoration: none;
        }

        .payable-count {
            display: inline-flex;
            min-width: 28px;
            height: 28px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #f1f5f9;
            font-size: 10px;
            font-weight: 800;
        }

        .payable-empty,
        .payable-empty-state {
            color: #94a3b8;
        }

        .payable-empty-state {
            padding: 35px !important;
            text-align: center;
        }

        .payable-form-group {
            text-align: left;
        }

        .payable-form-group label {
            display: block;
            margin-bottom: 7px;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
        }

        .payable-control {
            width: 100%;
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: inherit;
            font-size: 11px;
        }
        .payable-control-error {
    border-color: #dc2626;
}

.payable-error {
    margin-top: 6px;
    color: #b91c1c;
    font-size: 10px;
    line-height: 1.5;
}

        .payable-textarea {
            height: auto;
            resize: vertical;
        }

        .payable-modal-toggle {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .payable-modal {
            position: fixed;
            z-index: 9999;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .payable-modal-toggle:checked+.payable-modal {
            display: flex;
        }

        .payable-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .55);
        }

        .payable-modal-dialog {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 450px;
            padding: 24px;
            border-radius: 14px;
            background: #ffffff;
        }

        .payable-modal-dialog h3 {
            margin: 0 0 8px;
            font-size: 17px;
        }

        .payable-modal-dialog>p {
            margin: 0 0 18px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
        }

        .payable-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
        }

        @media (max-width: 800px) {

            .payable-detail-header,
            .payable-actions {
                flex-direction: column;
            }

            .payable-actions .payable-btn {
                width: 100%;
            }

            .payable-info-grid,
            .payable-time-grid,
            .payable-text-grid {
                grid-template-columns: 1fr;
            }

            .payable-info-grid>div,
            .payable-time-grid>div,
            .payable-text-grid>div {
                border-right: none;
            }

            .payable-modal-actions {
                flex-direction: column-reverse;
            }

            .payable-modal-actions .payable-btn {
                width: 100%;
            }
        }
    </style>

@endsection