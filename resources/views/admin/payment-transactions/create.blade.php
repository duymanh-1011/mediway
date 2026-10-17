@extends('admin.layouts.app')

@section(
    'title',
    'Thanh toán khoản phải thu - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin /
        Thanh toán /
        {{ $payable->payable_code }}
    </div>

@endsection


@section('content')

    @php
        $serviceItem =
            $payable->serviceOrderItem;

        $service =
            $serviceItem?->medicalService;

        $order =
            $serviceItem?->serviceOrder;
    @endphp


    <div class="page-header">

        <div>

            <h1 class="page-title">
                Ghi nhận thanh toán
            </h1>

            <p class="page-description">
                Xác nhận thanh toán cho khoản
                {{ $payable->payable_code }}.
            </p>

        </div>


        <div>

            <a
                href="{{ route(
                    'admin.payables.show',
                    $payable->id
                ) }}"
                class="btn btn-secondary"
            >
                ← Quay lại khoản phải thu
            </a>

        </div>

    </div>


    @if ($errors->any())

        <div class="payment-alert payment-alert-error">

            <strong>
                Không thể ghi nhận thanh toán.
            </strong>

            <ul>

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="payment-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin khoản phải thanh toán
            </h3>


            <div class="detail-row">

                <span>
                    Mã khoản
                </span>

                <strong>
                    {{ $payable->payable_code }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Loại khoản thu
                </span>

                <strong>
                    {{ $payable->payment_type }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Mô tả
                </span>

                <strong>
                    {{ $payable->description }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Trạng thái
                </span>

                <strong>
                    {{ $payable
                        ->paymentStatus
                        ?->name
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Số tiền
                </span>

                <strong class="payment-amount">
                    {{ number_format(
                        (float) $payable->amount,
                        0,
                        ',',
                        '.'
                    ) }}
                    đ
                </strong>

            </div>

        </div>


        <div class="card">

            <h3 class="section-title">
                Nguồn phát sinh
            </h3>


            <div class="detail-row">

                <span>
                    Lượt khám
                </span>

                <strong>
                    {{ $payable
                        ->medicalVisit
                        ?->visit_code
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Phiếu chỉ định
                </span>

                <strong>
                    {{ $order
                        ?->service_order_code
                        ?? '—' }}
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Dịch vụ
                </span>

                <strong>
                    {{ $service
                        ?->service_code
                        ?? '—' }}

                    @if ($service)

                        -

                        {{ $service->name }}

                    @endif
                </strong>

            </div>

        </div>

    </div>


    <form
        method="POST"
        action="{{ route(
            'admin.payables.payment-transactions.store',
            $payable
        ) }}"
        class="card payment-form-card"
    >

        @csrf


        <h3 class="section-title">
            Thông tin thanh toán
        </h3>


        <div class="form-grid">

            <div class="form-group">

                <label for="payment_method">
                    Phương thức thanh toán
                    <span class="required">*</span>
                </label>

                <select
                    id="payment_method"
                    name="payment_method"
                    class="form-control"
                    required
                >

                    <option value="">
                        -- Chọn phương thức --
                    </option>

                    <option
                        value="CASH"
                        @selected(
                            old('payment_method')
                            ===
                            'CASH'
                        )
                    >
                        Tiền mặt
                    </option>

                    <option
                        value="QR"
                        @selected(
                            old('payment_method')
                            ===
                            'QR'
                        )
                    >
                        QR
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Số tiền thanh toán
                </label>

                <input
                    type="text"
                    class="form-control"
                    value="{{ number_format(
                        (float) $payable->amount,
                        0,
                        ',',
                        '.'
                    ) }} đ"
                    readonly
                >

                <div class="form-help">
                    Số tiền được lấy trực tiếp từ khoản phải thu,
                    không thể thay đổi tại màn hình này.
                </div>

            </div>

        </div>


        <div class="form-group section-field">

            <label for="provider_transaction_code">
                Mã giao dịch nhà cung cấp
            </label>

            <input
                id="provider_transaction_code"
                type="text"
                name="provider_transaction_code"
                class="form-control"
                maxlength="120"
                value="{{ old(
                    'provider_transaction_code'
                ) }}"
                placeholder="Có thể để trống với tiền mặt"
            >

        </div>


        <div class="form-group section-field">

            <label for="payment_content">
                Nội dung thanh toán
            </label>

            <input
                id="payment_content"
                type="text"
                name="payment_content"
                class="form-control"
                maxlength="255"
                value="{{ old(
                    'payment_content'
                ) }}"
                placeholder="Ví dụ: Thanh toán phí dịch vụ xét nghiệm"
            >

        </div>


        <div class="form-group section-field">

            <label for="notes">
                Ghi chú
            </label>

            <textarea
                id="notes"
                name="notes"
                class="form-control"
                rows="4"
                placeholder="Nhập ghi chú nếu có..."
            >{{ old('notes') }}</textarea>

        </div>


        <div class="payment-warning">

            <strong>
                Xác nhận giao dịch
            </strong>

            <p>
                Khi xác nhận, hệ thống sẽ tạo giao dịch
                SUCCESS và chuyển khoản phải thanh toán
                sang trạng thái PAID.
            </p>

            <label class="confirm-row">

                <input
                    type="checkbox"
                    name="confirm_payment"
                    value="1"
                    required
                >

                <span>
                    Tôi xác nhận khoản tiền này đã được thanh toán.
                </span>

            </label>

        </div>


        <div class="form-actions">

            <a
                href="{{ route(
                    'admin.payables.show',
                    $payable->id
                ) }}"
                class="btn btn-secondary"
            >
                Hủy
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Xác nhận thanh toán
            </button>

        </div>

    </form>

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

    .payment-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 20px;
    }

    .section-title {
        margin-top: 0;
        margin-bottom: 16px;
    }

    .detail-row {
        display: grid;

        grid-template-columns:
            145px minmax(0, 1fr);

        gap: 15px;

        padding: 11px 0;

        border-bottom:
            1px solid #f1f5f9;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-row span {
        color: #64748b;
    }

    .payment-amount {
        color: #15803d;
        font-size: 18px;
    }

    .payment-form-card {
        margin-top: 20px;
    }

    .form-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 20px;
    }

    .form-group label {
        display: block;

        margin-bottom: 7px;

        font-weight: 700;
    }

    .form-control {
        display: block;

        width: 100%;

        box-sizing: border-box;

        min-height: 42px;

        padding: 10px 12px;

        border:
            1px solid #cbd5e1;

        border-radius: 8px;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 14px;
    }

    textarea.form-control {
        resize: vertical;
    }

    .required {
        color: #dc2626;
    }

    .form-help {
        margin-top: 6px;

        color: #64748b;

        font-size: 12px;
    }

    .section-field {
        margin-top: 18px;
    }

    .payment-warning {
        margin-top: 22px;

        padding: 16px;

        border:
            1px solid #fde68a;

        border-radius: 10px;

        background: #fffbeb;
    }

    .payment-warning strong {
        display: block;

        margin-bottom: 7px;

        color: #92400e;
    }

    .payment-warning p {
        margin:
            0 0 12px;

        color: #78350f;

        line-height: 1.6;
    }

    .confirm-row {
        display: flex !important;

        align-items: flex-start;

        gap: 9px;

        margin: 0 !important;

        font-weight: 400 !important;

        cursor: pointer;
    }

    .confirm-row input {
        margin-top: 3px;
    }

    .form-actions {
        display: flex;

        justify-content: flex-end;

        gap: 10px;

        margin-top: 24px;
    }

    .payment-alert {
        margin-bottom: 20px;

        padding: 14px 16px;

        border-radius: 8px;
    }

    .payment-alert-error {
        border:
            1px solid #fecaca;

        background: #fef2f2;

        color: #991b1b;
    }

    .payment-alert ul {
        margin-bottom: 0;
    }

    @media (max-width: 800px) {
        .page-header {
            flex-direction: column;
        }

        .payment-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@endpush