@extends('employee.layout')

@section('title', 'Quản lý khoản phải thu')

@push('styles')
    @vite(['resources/css/employee-payments.css', 'resources/css/employee/payments/index.css'])
@endpush

@section('content')

    @php

        $hasCollectRoute = \Illuminate\Support\Facades\Route::has(
            'employee.payments.collect.create'
        );

        $hasDetailRoute = \Illuminate\Support\Facades\Route::has(
            'employee.payments.show'
        );
    @endphp



    <div class="f08">

        {{-- HEADER --}}
        <section class="f08-hero">

            <a href="{{ route('employee.home') }}">
                ← Tổng quan nhân viên
            </a>

            <h1>Quản lý khoản phải thu</h1>

            <p>
                Tra cứu phí khám, phí dịch vụ và tiền thuốc
                phát sinh hợp lệ tại phòng khám.
                Ghi nhận thanh toán tiền mặt và theo dõi
                lịch sử thu ngân.
            </p>

        </section>

        {{-- TÌM KIẾM VÀ BỘ LỌC --}}
        <section class="f08-panel">

            <div class="f08-panel-head">

                <h2>Tìm kiếm và bộ lọc</h2>

                <span class="f08-note">
                    Có thể kết hợp nhiều bộ lọc.
                    Kết quả luôn theo điều kiện đã chọn.
                </span>

            </div>

            <form method="POST" action="{{ route('employee.payments.filter') }}" id="f08-payable-filter" autocomplete="off">
                @csrf
                @isset($filters['search'])
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                @endisset

                <div class="f08-grid">

                    {{-- TÌM KIẾM --}}
                    <div class="f08-field">

                        <label for="f08-q">
                            Tìm kiếm
                        </label>

                        <input id="f08-q" type="search" name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Tên, mã, CCCD hoặc số điện thoại">

                    </div>

                    {{-- LOẠI KHOẢN THU --}}
                    <div class="f08-field">

                        <label for="f08-type">
                            Loại khoản thu
                        </label>

                        <select id="f08-type" name="payment_type">

                            <option value="">
                                Tất cả loại khoản thu
                            </option>

                            @foreach ($paymentTypes as $type)

                                                    <option value="{{ $type }}" @selected(
                                                        ($filters['payment_type'] ?? '') === $type
                                                    )>
                                                        {{ match ($type) {
                                    'EXAMINATION_FEE' => 'Phí khám',
                                    'SERVICE_FEE' => 'Phí dịch vụ',
                                    'MEDICINE_FEE' => 'Phí thuốc',
                                    default => $type,
                                } }}
                                                    </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- TRẠNG THÁI --}}
                    <div class="f08-field">

                        <label for="f08-status">
                            Trạng thái thanh toán
                        </label>

                        <select id="f08-status" name="payment_status_id">

                            <option value="">
                                Tất cả trạng thái
                            </option>

                            @foreach ($paymentStatuses as $paymentStatus)

                                                    <option value="{{ $paymentStatus->id }}" @selected(
                                                        (string) ($filters['payment_status_id'] ?? '')
                                                        === (string) $paymentStatus->id
                                                    )>
                                                        {{ match ($paymentStatus->name) {
                                    'UNPAID' => 'Chưa thanh toán',
                                    'PENDING' => 'Chờ xác nhận',
                                    'PAID' => 'Đã thanh toán',
                                    'CANCELLED' => 'Đã hủy',
                                    default => $paymentStatus->name,
                                } }}
                                                    </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- PHƯƠNG THỨC --}}
                    <div class="f08-field">

                        <label for="f08-method">
                            Phương thức thanh toán
                        </label>

                        <select id="f08-method" name="payment_method">

                            <option value="">
                                Tất cả phương thức
                            </option>

                            <option value="CASH" @selected(
                                ($filters['payment_method'] ?? '') === 'CASH'
                            )>
                                Tiền mặt
                            </option>

                            <option value="QR" @selected(
                                ($filters['payment_method'] ?? '') === 'QR'
                            )>
                                Chuyển khoản QR
                            </option>

                        </select>

                    </div>

                    {{-- TỪ NGÀY --}}
                    <div class="f08-field">

                        <label for="f08-from">
                            Từ ngày
                        </label>

                        <input id="f08-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">

                    </div>

                    {{-- ĐẾN NGÀY --}}
                    <div class="f08-field">

                        <label for="f08-to">
                            Đến ngày
                        </label>

                        <input id="f08-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">

                    </div>

                </div>

                <div class="f08-actions">

                    <button class="f08-btn f08-btn-primary" type="submit">
                        ⌕ Áp dụng bộ lọc
                    </button>

                    <button class="f08-btn" type="submit" name="reset" value="1" formnovalidate>
                        Xóa bộ lọc
                    </button>

                </div>

            </form>

        </section>

        {{-- DANH SÁCH KHOẢN PHẢI THU --}}
        <section class="f08-panel">

            <div class="f08-panel-head">

                <div>

                    <h2>Danh sách khoản phải thu</h2>

                    <span class="f08-note">
                        {{ number_format(
        $payables->total(),
        0,
        ',',
        '.'
    ) }}
                        kết quả · tối đa 10 khoản/trang
                    </span>

                </div>

            </div>

            <div class="f08-tablewrap" data-scroll-label="Bảng khoản phải thu, có thể cuộn ngang">

                <table data-mobile-records>

                    <thead>
                        <tr>
                            <th>Mã khoản thu</th>
                            <th>Bệnh nhân</th>
                            <th>Nội dung</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Phương thức</th>
                            <th>Người thu tiền</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($payables as $payable)

                                            @php
                                                $visit = $payable->medicalVisit;
                                                $patient = $visit?->patient;

                                                $transactions = $payable->transactions;

                                                $successfulTransactions = $transactions
                                                    ->where('transaction_result', 'SUCCESS');

                                                $received = (float) $successfulTransactions
                                                    ->sum('amount');

                                                $hasSuccessfulPayment = $successfulTransactions
                                                    ->isNotEmpty();

                                                $methods = $transactions
                                                    ->pluck('payment_method')
                                                    ->filter()
                                                    ->unique()
                                                    ->values();

                                                $collectors = $successfulTransactions
                                                    ->map(
                                                        fn($transaction) =>
                                                            $transaction->collector?->full_name
                                                    )
                                                    ->filter()
                                                    ->unique()
                                                    ->values();

                                                $status = $payable->paymentStatus?->name;
                                                $dateReason = app(\App\Services\AppointmentPaymentDateGuard::class)->unavailableReason($payable);

                                                $serviceCancelled =
                                                    $payable->serviceOrderItem?->serviceStatus?->name
                                                    === 'CANCELLED';

                                                /*
                                                 * Điều kiện hiển thị nút Thanh toán.
                                                 *
                                                 * Đây chỉ là điều kiện giao diện.
                                                 * Backend phải kiểm tra lại đầy đủ
                                                 * trong PaymentCollectionService.
                                                 */
                                                $canPay = in_array(
                                                    $status,
                                                    ['UNPAID', 'PENDING'],
                                                    true
                                                )
                                                    && (float) $payable->amount > 0
                                                    && !$hasSuccessfulPayment
                                                    && !$serviceCancelled;

                                                $isPaid =
                                                    $status === 'PAID'
                                                    || $hasSuccessfulPayment;

                                                $paymentType = match (
                                                $payable->payment_type
                                                ) {
                                                    'EXAMINATION_FEE' => 'Phí khám',
                                                    'SERVICE_FEE' => 'Phí dịch vụ',
                                                    'MEDICINE_FEE' => 'Tiền thuốc',
                                                    default =>
                                                        $payable->payment_type ?? 'Khác',
                                                };

                                                $statusLabel = match ($status) {
                                                    'UNPAID' => 'Chưa thanh toán',
                                                    'PENDING' => 'Chờ xác nhận',
                                                    'PAID' => 'Đã thanh toán',
                                                    'CANCELLED' => 'Đã hủy',
                                                    default => $status ?? 'Chưa xác định',
                                                };

                                                $statusClass = match ($status) {
                                                    'PAID' => 'paid',
                                                    'PENDING' => 'pending',
                                                    'CANCELLED' => 'cancelled',
                                                    default => '',
                                                };

                                                $detailParameters = array_merge(
                                                    ['payable' => $payable->id],
                                                    $backQuery
                                                );

                                                $collectParameters = array_merge(
                                                    ['payable' => $payable->id],
                                                    $backQuery
                                                );
                                            @endphp

                                            <tr>

                                                {{-- MÃ KHOẢN THU --}}
                                                <td>

                                                    <span class="f08-code">
                                                        {{ $payable->payable_code }}
                                                    </span>


                                                </td>

                                                {{-- BỆNH NHÂN --}}
                                                <td>

                                                    <strong>
                                                        {{ $patient?->full_name
                                ?? 'Chưa xác định' }}
                                                    </strong>

                                                    <span class="f08-sub">
                                                        {{ $patient?->patient_code ?? '—' }}
                                                    </span>

                                                </td>

                                                {{-- NỘI DUNG --}}
                                                <td>

                                                    <strong>
                                                        {{ $payable->displayContent() }}
                                                    </strong>


                                                </td>

                                                {{-- SỐ TIỀN --}}
                                                <td class="f08-money">

                                                    {{ number_format(
                                (float) $payable->amount,
                                0,
                                ',',
                                '.'
                            ) }} đ

                                                </td>

                                                {{-- TRẠNG THÁI --}}
                                                <td>

                                                    <span class="f08-pill {{ $statusClass }}">
                                                        {{ $statusLabel }}
                                                    </span>

                                                </td>

                                                {{-- PHƯƠNG THỨC --}}
                                                <td>

                                                    @forelse ($methods as $method)

                                                                            <span class="f08-pill {{
                                                            $method === 'QR'
                                                            ? 'qr'
                                                            : (
                                                                $method === 'CASH'
                                                                ? 'cash'
                                                                : ''
                                                            )
                                                                                                                                        }}">
                                                                                {{ match ($method) {
                                                            'CASH' => 'Tiền mặt',
                                                            'QR' => 'QR',
                                                            default => $method,
                                                        } }}
                                                                            </span>

                                                    @empty

                                                        <span class="f08-sub">
                                                            Chưa có phương thức
                                                        </span>

                                                    @endforelse

                                                </td>

                                                {{-- NGƯỜI THU --}}
                                                <td>

                                                    @if ($collectors->isNotEmpty())

                                                        {{ $collectors->join(', ') }}

                                                    @elseif ($successfulTransactions->isNotEmpty())

                                                        <span class="f08-sub">
                                                            Không ghi nhận người thu /
                                                            giao dịch tự động
                                                        </span>

                                                    @else

                                                        <span class="f08-sub">
                                                            Chưa có người thu
                                                        </span>

                                                    @endif

                                                </td>

                                                {{-- NGÀY TẠO --}}
                                                <td class="f08-nowrap">

                                                    {{ $payable->generated_at?->format(
                                'd/m/Y'
                            ) ?? '—' }}

                                                    <span class="f08-sub">
                                                        {{ $payable->generated_at?->format(
                                'H:i'
                            ) ?? '' }}
                                                    </span>

                                                </td>

                                                {{-- THAO TÁC --}}
                                                <td>

                                                    <div class="f08-row-actions">

                                                        @if ($isPaid)

                                                            <span class="f08-payment-done">
                                                                ✓ Đã thu tiền
                                                            </span>

                                                        @elseif ($status === 'CANCELLED')

                                                            <span class="f08-payment-disabled">
                                                                Đã hủy
                                                            </span>

                                                        @elseif ($serviceCancelled)

                                                            <span class="f08-payment-disabled">
                                                                Dịch vụ đã hủy
                                                            </span>

                                                        @elseif ($dateReason)

                                                            <span class="f08-payment-disabled f08-payment-warning">{{ $dateReason }}</span>

                                                        @elseif ($canPay && $hasCollectRoute)

                                                                                    <a class="f08-pay-btn" href="{{ route(
                                                                'employee.payments.collect.create',
                                                                $collectParameters
                                                            ) }}" title="Ghi nhận thanh toán tiền mặt">
                                                                                        <span aria-hidden="true">
                                                                                            ＋
                                                                                        </span>
                                                                                        Thanh toán
                                                                                    </a>

                                                        @elseif ($canPay)

                                                            <span class="f08-payment-disabled f08-payment-warning"
                                                                title="Chức năng ghi nhận thanh toán chưa sẵn sàng">
                                                                Chưa hỗ trợ thu
                                                            </span>

                                                        @else

                                                            <span class="f08-payment-disabled">
                                                                Không thể thanh toán
                                                            </span>

                                                        @endif

                                                        @if ($hasDetailRoute)

                                                                                    <a class="f08-detail-btn" href="{{ route(
                                                                'employee.payments.show',
                                                                $detailParameters
                                                            ) }}">
                                                                                        Xem chi tiết →
                                                                                    </a>

                                                        @endif

                                                    </div>

                                                </td>

                                            </tr>

                        @empty

                            <tr>

                                <td class="f08-empty" colspan="9">
                                    <strong>
                                        Không tìm thấy khoản phải thu
                                        phù hợp với điều kiện lọc.
                                    </strong>

                                    <span class="f08-sub">
                                        Hãy thay đổi bộ lọc hoặc nhấn
                                        "Xóa bộ lọc" để xem tất cả.
                                    </span>
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- PHÂN TRANG --}}
            <div class="f08-foot">

                <span>
                    Hiển thị
                    {{ $payables->firstItem() ?? 0 }}
                    –
                    {{ $payables->lastItem() ?? 0 }}
                    /
                    {{ $payables->total() }}
                    khoản
                </span>

                @if ($payables->hasPages())

                    <nav class="f08-pages" aria-label="Phân trang khoản phải thu">

                        @if (!$payables->onFirstPage())

                            <a class="f08-page" href="{{ $payables->previousPageUrl() }}">
                                ← Trước
                            </a>

                        @endif

                        @for (
                                    $page = max(
                                        1,
                                        $payables->currentPage() - 2
                                    );
                                    $page <= min(
                                        $payables->lastPage(),
                                        $payables->currentPage() + 2
                                    );
                                    $page++
                                )

                                <a class="f08-page {{
                            $page === $payables->currentPage()
                            ? 'current'
                            : ''
                                                            }}" @if ($page === $payables->currentPage()) aria-current="page" @endif
                                    href="{{ $payables->url($page) }}">
                                    {{ $page }}
                                </a>

                        @endfor

                        @if ($payables->hasMorePages())

                            <a class="f08-page" href="{{ $payables->nextPageUrl() }}">
                                Sau →
                            </a>

                        @endif

                    </nav>

                @endif

            </div>

        </section>

    </div>

    {{-- ĐỒNG BỘ BỘ LỌC KHI BACK/FORWARD --}}


@endsection
