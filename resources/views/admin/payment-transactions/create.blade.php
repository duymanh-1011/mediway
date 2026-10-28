@extends('admin.layouts.app')
@section('admin-page', 'payment-transactions-create')

@section(
    'title',
    'Thanh toán khoản phải thu - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Thanh toán</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">@include('admin.layouts.partials.record-code', ['code' => $payable->payable_code])</strong>
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
                @include('admin.layouts.partials.record-code', ['code' => $payable->payable_code]).
            </p>

        </div>


        <div>

            <a
                href="{{ route(
                    'admin.payables.show',
                    $payable->id
                ) }}"
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>

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
                        {{ \App\Models\AdminDisplay::text($error) }}
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
                    @include('admin.layouts.partials.record-code', ['code' => $payable->payable_code])
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Loại khoản thu
                </span>

                <strong>
                    {{ \App\Models\AdminDisplay::label($payable->payment_type) }}
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

                <strong>@include('admin.layouts.partials.status-badge', ['badgeCode' => $payable ->paymentStatus ?->name ?? '—', 'badgeContext' => 'payment'])</strong>

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
                    @include('admin.layouts.partials.record-code', ['code' => $payable
                        ->medicalVisit
                        ?->visit_code
                        ?? '—'])
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Phiếu chỉ định
                </span>

                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $order
                        ?->service_order_code
                        ?? '—'])
                </strong>

            </div>


            <div class="detail-row">

                <span>
                    Dịch vụ
                </span>

                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $service
                        ?->service_code
                        ?? '—'])

                    @if ($service)

                        -

                        {{ $service->name }}

                    @endif
                </strong>

            </div>

        </div>

    </div>


    <form novalidate
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
@error('payment_method')<div class="field-error">{{ $message }}</div>@enderror

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
@error('provider_transaction_code')<div class="field-error">{{ $message }}</div>@enderror

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
@error('payment_content')<div class="field-error">{{ $message }}</div>@enderror

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
@error('notes')<div class="field-error">{{ $message }}</div>@enderror

        </div>


        <div class="payment-warning">

            <strong>
                Xác nhận giao dịch
            </strong>

            <p>
                Khi xác nhận, hệ thống sẽ tạo giao dịch thành công
                và chuyển khoản phải thanh toán sang trạng thái đã thanh toán.
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
                class="admin-btn admin-btn-secondary"
            >Hủy</a>

            <button
                type="submit"
                class="admin-btn admin-btn-primary"
            >
                Xác nhận thanh toán
            </button>

        </div>

    </form>

@endsection
