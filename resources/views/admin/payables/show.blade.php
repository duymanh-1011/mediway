@extends('admin.layouts.app')
@section('admin-page', 'payables-show')

@section('title', 'Chi tiết khoản thanh toán')

@section('content')

    @php
        $statusName = strtoupper(
            $payable->paymentStatus?->name ?? ''
        );



        $typeLabel = match ($payable->payment_type) {
            'EXAMINATION_FEE' => 'Phí khám',
            'SERVICE_FEE' => 'Phí dịch vụ',
            default => $payable->payment_type,
        };

        $canCancel = !in_array($statusName, ['PAID', 'CANCELLED'], true)
            && !$payable->paid_at && !$payable->transactions->contains('transaction_result', 'SUCCESS')
            && (!$payable->serviceOrderItem || $payable->serviceOrderItem->canBeCancelled());
    @endphp


    <div class="payable-detail-page">

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Thanh toán</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.payables.index') }}">
                Khoản phải thanh toán
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                @include('admin.layouts.partials.record-code', ['code' => $payable->payable_code])
            </strong>

        </nav>


        <div class="admin-info-header payable-detail-header">

            <div>

                <h1>
                    Chi tiết khoản phải thanh toán
                </h1>

                <p>
                    Thông tin tài chính và lịch sử giao dịch liên quan.
                </p>
            </div>

            @include('admin.layouts.partials.status-badge', ['badgeCode' => $payable->paymentStatus?->name ?? '—', 'badgeContext' => 'payment'])

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

            <a href="{{ route('admin.payables.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>


            @if (
                    !in_array(
                        $payable->paymentStatus?->name,
                        ['PAID', 'CANCELLED']
                    )
                )

                <label for="status-modal" class="admin-btn admin-btn-primary">
                    Đổi trạng thái
                </label>

                @if ($canCancel)
                <label for="cancel-modal" class="admin-btn admin-btn-danger">
                    Hủy khoản thu
                </label>
                @endif

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
                        @include('admin.layouts.partials.record-code', ['code' => $payable->payable_code])
                    </strong>
                </div>


                <div>
                    <span>Lượt khám</span>

                    <strong>
                        @include('admin.layouts.partials.record-code', ['code' => $visit?->visit_code ?? '—'])
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

                    <strong>@include('admin.layouts.partials.status-badge', ['badgeCode' => $payable->paymentStatus?->name ?? '—', 'badgeContext' => 'payment'])</strong>
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
                            @include('admin.layouts.partials.record-code', ['code' => $serviceContext?->service_order_code ?? '—'])
                        </strong>
                    </div>


                    <div>
                        <span>Mã dịch vụ</span>

                        <strong>
                            @include('admin.layouts.partials.record-code', ['code' => $serviceContext?->service_code ?? '—'])
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
                            <th scope="col" class="admin-row-number">STT</th>
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



                                            <tr>

                                                @include('admin.layouts.partials.row-number', ['rows' => $payable->transactions, 'index' => $loop->index])
                                                <td>
                                                    <strong class="payable-code">
                                                        @include('admin.layouts.partials.record-code', ['code' => $transaction->transaction_code])
                                                    </strong>
                                                </td>


                                                <td>
                                                    {{ \App\Models\AdminDisplay::label($transaction->payment_method) }}
                                                </td>


                                                <td>
                                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $transaction->transaction_result, 'badgeContext' => 'payment'])
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


                                                <td class="text-right"><div class="admin-actions admin-table-actions">

                                                    <a href="{{ route(
                                'admin.payment-transactions.show',
                                $transaction->id
                            ) }}" class="admin-btn admin-btn-detail">Chi tiết</a>

                                                </div></td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="8" class="payable-empty-state">
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

            <form novalidate method="POST" action="{{ route(
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
                                @include('admin.layouts.partials.record-code', ['code' => $payable->payable_code])
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
                {{ \App\Models\AdminDisplay::label($status->name, 'payment') }}
            </option>

        @endif

    @endforeach

</select>

@error('payment_status_id')
    <div class="payable-error">
        {{ \App\Models\AdminDisplay::text($message) }}
    </div>
@enderror

                        </div>


                        <div class="payable-modal-actions">

                            <label for="status-modal" class="admin-btn admin-btn-secondary">← Quay lại</label>

                            <button type="submit" name="confirm_update" value="1" class="admin-btn admin-btn-primary">
                                Xác nhận
                            </button>

                        </div>

                    </div>

                </div>

            </form>


            {{-- MODAL HỦY NGHIỆP VỤ --}}

            <form novalidate method="POST" action="{{ route(
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
                            <strong>Đã hủy</strong>.
                            Nếu khoản thu gắn với dịch vụ chưa bắt đầu, dịch vụ đó cũng được hủy.
                            Khi không còn dịch vụ cần chờ, bệnh nhân được đưa vào hàng đợi quay lại bác sĩ.
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
        {{ \App\Models\AdminDisplay::text($message) }}
    </div>
@enderror
                        </div>


                        <div class="payable-modal-actions">

                            <label for="cancel-modal" class="admin-btn admin-btn-secondary">← Quay lại</label>

                            <button type="submit" name="confirm_cancel" value="1" class="admin-btn admin-btn-danger">
                                Xác nhận hủy
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        @endif

    </div>




@endsection
