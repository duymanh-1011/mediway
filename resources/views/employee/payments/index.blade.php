@extends('employee.layout')

@section('title', 'Quản lý khoản phải thu')

@section('content')

    <style>
        .f08 {
            --ink: #102a43;
            --muted: #64748b;
            --line: #e2e8f0;
            --accent: #0d9488;
            color: var(--ink);
        }

        .f08 .f08-hero {
            background: linear-gradient(115deg, #0f3551, #0f766e);
            color: white;
            border-radius: 22px;
            padding: 28px 30px;
            margin-bottom: 22px;
            box-shadow: 0 12px 30px #0f355126;
        }

        .f08 .f08-hero a {
            color: #d9fffa;
        }

        .f08 .f08-hero h1 {
            font-size: clamp(24px, 3vw, 34px);
            margin: 13px 0 7px;
            color: white;
        }

        .f08 .f08-hero p {
            color: #d7e8ee;
            margin: 0;
        }

        .f08 .f08-panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 5px 25px #183b5610;
        }

        .f08 .f08-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .f08 h2 {
            margin: 0;
            font-size: 20px;
            color: #17324b;
        }

        .f08 .f08-note {
            color: var(--muted);
            font-size: 13px;
        }

        .f08 .f08-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .f08 label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #40556b;
        }

        .f08 input,
        .f08 select {
            width: 100%;
            min-height: 44px;
            padding: 9px 12px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            color: var(--ink);
            font: inherit;
        }

        .f08 input:focus,
        .f08 select:focus {
            outline: 3px solid #99f6e4;
            border-color: #0d9488;
            background: #fff;
        }

        .f08 .f08-actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 19px;
        }

        .f08 .f08-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 39px;
            padding: 9px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #16415a;
            cursor: pointer;
        }

        .f08 .f08-btn-primary {
            background: #0f766e;
            border-color: #0f766e;
            color: white;
        }

        .f08 .f08-btn:hover {
            filter: brightness(.96);
        }

        .f08 .f08-tablewrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 13px;
        }

        .f08 table {
            border-collapse: collapse;
            width: 100%;
            min-width: 1050px;
            font-size: 13px;
        }

        .f08 th {
            text-align: left;
            background: #f0f7f8;
            color: #42566a;
            white-space: nowrap;
            font-size: 12px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .f08 th,
        .f08 td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
        }

        .f08 tbody tr:hover {
            background: #f8fdfc;
        }

        .f08 tbody tr:last-child td {
            border-bottom: 0;
        }

        .f08 .f08-sub {
            display: block;
            color: #64748b;
            margin-top: 4px;
            font-size: 12px;
        }

        .f08 .f08-code {
            font-weight: 800;
            color: #08766f;
        }

        .f08 .f08-money {
            font-weight: 800;
            white-space: nowrap;
        }

        .f08 .f08-pill {
            display: inline-flex;
            align-items: center;
            background: #eef2f6;
            border-radius: 99px;
            padding: 5px 9px;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            white-space: nowrap;
        }

        .f08 .f08-pill.paid {
            background: #dcfce7;
            color: #166534;
        }

        .f08 .f08-pill.pending {
            background: #fff7ed;
            color: #9a3412;
        }

        .f08 .f08-pill.qr {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .f08 .f08-pill.cash {
            background: #e6faf4;
            color: #047857;
        }

        .f08 .f08-foot {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
            color: #64748b;
            font-size: 13px;
        }

        .f08 .f08-pages {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .f08 .f08-page {
            border: 1px solid #d1dce6;
            padding: 7px 11px;
            border-radius: 8px;
            text-decoration: none;
            color: #0f766e;
            font-weight: 700;
        }

        .f08 .f08-page.current {
            background: #0f766e;
            color: #fff;
            border-color: #0f766e;
        }

        .f08 .f08-page.disabled {
            opacity: .4;
        }

        .f08 .f08-empty {
            text-align: center;
            padding: 42px 12px !important;
            color: #64748b;
        }

        @media (max-width: 900px) {
            .f08 .f08-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .f08 .f08-grid {
                grid-template-columns: 1fr;
            }

            .f08 .f08-panel {
                padding: 16px;
            }

            .f08 .f08-hero {
                padding: 22px;
            }

            .f08 .f08-btn {
                flex: 1;
            }
        }
    </style>

    <div class="f08">

        <section class="f08-hero">
            <a href="{{ route('employee.home') }}">
                ← Tổng quan nhân viên
            </a>

            <h1>Quản lý khoản phải thu</h1>

            <p>
                Tra cứu khoản thu, xác định bệnh nhân,
                phương thức thanh toán và nhân viên thu tiền.
            </p>
        </section>

        <section class="f08-panel">

            <div class="f08-panel-head">
                <h2>Tìm kiếm và bộ lọc</h2>

                <span class="f08-note">
                    Các bộ lọc kết hợp với nhau ·
                    kết quả luôn theo đúng điều kiện đã chọn
                </span>
            </div>

            <form method="GET" action="{{ route('employee.payments.index') }}" id="f08-payable-filter" autocomplete="off">
                <div class="f08-grid">

                    <div>
                        <label for="f08-q">
                            Tìm kiếm khoản thu, bệnh nhân, người thu
                        </label>

                        <input id="f08-q" type="search" name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Tên bệnh nhân, người thu, mã giao dịch...">
                    </div>

                    <div>
                        <label for="f08-type">Loại khoản thu</label>

                        <select id="f08-type" name="payment_type">
                            <option value="">
                                Tất cả loại khoản thu
                            </option>

                            @foreach($paymentTypes as $type)
                                                <option value="{{ $type }}" @selected(($filters['payment_type'] ?? '') === $type)>
                                                    {{
                                match ($type) {
                                    'EXAMINATION_FEE' => 'Phí khám',
                                    'SERVICE_FEE' => 'Phí dịch vụ',
                                    'MEDICINE_FEE' => 'Tiền thuốc',
                                    default => $type
                                }
                                                        }}
                                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f08-status">
                            Trạng thái thanh toán
                        </label>

                        <select id="f08-status" name="payment_status_id">
                            <option value="">
                                Tất cả trạng thái
                            </option>

                            @foreach($paymentStatuses as $status)
                                                <option value="{{ $status->id }}" @selected(
                                                    (string) ($filters['payment_status_id'] ?? '')
                                                    === (string) $status->id
                                                )>
                                                    {{
                                match ($status->name) {
                                    'UNPAID' => 'Chưa thanh toán',
                                    'PENDING' => 'Chờ xác nhận',
                                    'PAID' => 'Đã thanh toán',
                                    'CANCELLED' => 'Đã hủy',
                                    default => $status->name
                                }
                                                        }}
                                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f08-method">
                            Phương thức thanh toán
                        </label>

                        <select id="f08-method" name="payment_method">
                            <option value="">
                                Tất cả phương thức
                            </option>

                            <option value="CASH" @selected(($filters['payment_method'] ?? '') === 'CASH')>
                                Tiền mặt
                            </option>

                            <option value="QR" @selected(($filters['payment_method'] ?? '') === 'QR')>
                                Chuyển khoản QR
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="f08-from">
                            Từ ngày tạo
                        </label>

                        <input id="f08-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div>
                        <label for="f08-to">
                            Đến ngày tạo
                        </label>

                        <input id="f08-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                </div>

                <div class="f08-actions">
                    <button class="f08-btn f08-btn-primary" type="submit">
                        ⌕ Áp dụng bộ lọc
                    </button>

                    <a class="f08-btn" href="{{ route('employee.payments.index') }}">
                        Xóa bộ lọc
                    </a>
                </div>
            </form>
        </section>

        <section class="f08-panel">

            <div class="f08-panel-head">
                <div>
                    <h2>Danh sách khoản phải thu</h2>

                    <span class="f08-note">
                        {{
        number_format(
            $payables->total(),
            0,
            ',',
            '.'
        )
                        }}
                        kết quả · tối đa 10 khoản/trang
                    </span>
                </div>
            </div>

            <div class="f08-tablewrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mã khoản thu / lượt khám</th>
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
                        @forelse($payables as $payable)

                                        @php
                                            $visit = $payable->medicalVisit;
                                            $patient = $visit?->patient;

                                            $successfulTransactions =
                                                $payable->transactions
                                                    ->where(
                                                        'transaction_result',
                                                        'SUCCESS'
                                                    );

                                            $methods = $payable->transactions
                                                ->pluck('payment_method')
                                                ->filter()
                                                ->unique()
                                                ->values();

                                            $collectors = $successfulTransactions
                                                ->map(
                                                    fn($tx) => $tx->collector?->full_name
                                                )
                                                ->filter()
                                                ->unique()
                                                ->values();

                                            $status = $payable->paymentStatus?->name;

                                            $paymentType = match (
                                            $payable->payment_type
                                            ) {
                                                'EXAMINATION_FEE' => 'Phí khám',
                                                'SERVICE_FEE' => 'Phí dịch vụ',
                                                'MEDICINE_FEE' => 'Tiền thuốc',
                                                default => $payable->payment_type ?? 'Khác'
                                            };
                                        @endphp

                                        <tr>
                                            <td>
                                                <span class="f08-code">
                                                    {{ $payable->payable_code }}
                                                </span>

                                                <span class="f08-sub">
                                                    {{
                            $visit?->visit_code
                            ?? 'Chưa có lượt khám'
                                                        }}
                                                </span>
                                            </td>

                                            <td>
                                                <strong>
                                                    {{
                            $patient?->full_name
                            ?? 'Chưa xác định'
                                                        }}
                                                </strong>

                                                <span class="f08-sub">
                                                    {{
                            $patient?->patient_code
                            ?? '—'
                                                        }}
                                                </span>
                                            </td>

                                            <td>
                                                {{ $paymentType }}

                                                <span class="f08-sub">
                                                    {{
                            \Illuminate\Support\Str::limit(
                                $payable->description
                                ?: 'Không có mô tả',
                                50
                            )
                                                        }}
                                                </span>
                                            </td>

                                            <td class="f08-money">
                                                {{
                            number_format(
                                (float) $payable->amount,
                                0,
                                ',',
                                '.'
                            )
                                                    }} đ
                                            </td>

                                            <td>
                                                <span class="f08-pill {{
                            $status === 'PAID'
                            ? 'paid'
                            : (
                                $status === 'PENDING'
                                ? 'pending'
                                : ''
                            )
                                                        }}">
                                                    {{
                            match ($status) {
                                'UNPAID' => 'Chưa thanh toán',
                                'PENDING' => 'Chờ xác nhận',
                                'PAID' => 'Đã thanh toán',
                                'CANCELLED' => 'Đã hủy',
                                default => $status ?? 'Chưa xác định'
                            }
                                                        }}
                                                </span>
                                            </td>

                                            <td>
                                                @forelse($methods as $method)

                                                                    <span class="f08-pill {{
                                                    $method === 'QR'
                                                    ? 'qr'
                                                    : 'cash'
                                                                                }}">
                                                                        {{
                                                    match ($method) {
                                                        'CASH' => 'Tiền mặt',
                                                        'QR' => 'QR',
                                                        default => $method
                                                    }
                                                                                }}
                                                                    </span>

                                                @empty
                                                    <span class="f08-sub">
                                                        Chưa có phương thức
                                                    </span>
                                                @endforelse
                                            </td>

                                            <td>
                                                @if($collectors->isNotEmpty())

                                                    {{ $collectors->join(', ') }}

                                                @elseif($successfulTransactions->isNotEmpty())

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

                                            <td style="white-space: nowrap">
                                                {{
                            $payable->generated_at?->format(
                                'd/m/Y'
                            ) ?? '—'
                                                    }}

                                                <span class="f08-sub">
                                                    {{
                            $payable->generated_at?->format(
                                'H:i'
                            ) ?? ''
                                                        }}
                                                </span>
                                            </td>

                                            <td>
                                                <a class="f08-btn" href="{{
                            route(
                                'employee.payments.show',
                                array_merge(
                                    [
                                        'payable' => $payable->id
                                    ],
                                    request()->only([
                                        'q',
                                        'payment_type',
                                        'payment_status_id',
                                        'payment_method',
                                        'date_from',
                                        'date_to',
                                        'page'
                                    ])
                                )
                            )
                                                        }}">
                                                    Xem chi tiết →
                                                </a>
                                            </td>
                                        </tr>

                        @empty
                            <tr>
                                <td class="f08-empty" colspan="9">
                                    Không tìm thấy khoản phải thu
                                    phù hợp với điều kiện lọc.

                                    Bạn có thể thay đổi bộ lọc
                                    hoặc xóa bộ lọc để xem tất cả.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

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

                @if($payables->hasPages())

                    <nav class="f08-pages" aria-label="Phân trang">

                        @if(!$payables->onFirstPage())
                            <a class="f08-page" href="{{ $payables->previousPageUrl() }}">
                                ← Trước
                            </a>
                        @endif

                        @for(
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
                                            }}" @if(
                                                $page === $payables->currentPage()
                                            ) aria-current="page" @endif
                                    href="{{ $payables->url($page) }}">
                                    {{ $page }}
                                </a>

                        @endfor

                        @if($payables->hasMorePages())
                            <a class="f08-page" href="{{ $payables->nextPageUrl() }}">
                                Sau →
                            </a>
                        @endif

                    </nav>

                @endif

            </div>
        </section>
    </div>

    <script>
        (function () {
            'use strict';

            const keys = [
                'q',
                'payment_type',
                'payment_status_id',
                'payment_method',
                'date_from',
                'date_to'
            ];

            const formId = 'f08-payable-filter';

            // URL là trạng thái lọc đã được áp dụng.
            // Mỗi khi quay lại trang, các ô lọc phải
            // khớp với URL đang hiển thị.
            function syncFromUrl() {
                const form = document.getElementById(formId);

                if (!form) {
                    return;
                }

                const search = new URLSearchParams(
                    window.location.search
                );

                for (const key of keys) {
                    const field = form.elements.namedItem(key);

                    if (!field) {
                        continue;
                    }

                    const requested = search.get(key) || '';

                    if (field.tagName === 'SELECT') {
                        const optionExists = Array.from(
                            field.options
                        ).some(function (option) {
                            return option.value === requested;
                        });

                        field.value = optionExists
                            ? requested
                            : '';
                    } else {
                        field.value = requested;
                    }
                }
            }

            // Đồng bộ sau khi DOM đã tải.
            document.addEventListener(
                'DOMContentLoaded',
                syncFromUrl
            );

            // Xử lý khi trình duyệt phục hồi trang
            // từ Back/Forward Cache.
            window.addEventListener(
                'pageshow',
                function (event) {
                    if (event.persisted) {
                        // Lấy lại dữ liệu từ server.
                        // Tránh bảng cache cũ nhưng form mới.
                        window.location.reload();
                        return;
                    }

                    syncFromUrl();

                    window.requestAnimationFrame(
                        syncFromUrl
                    );
                }
            );

            // Khi URL lịch sử thay đổi,
            // yêu cầu lại trang cho trạng thái đó.
            window.addEventListener(
                'popstate',
                function () {
                    window.location.reload();
                }
            );

            if (document.readyState !== 'loading') {
                syncFromUrl();
            }
        })();
    </script>

@endsection