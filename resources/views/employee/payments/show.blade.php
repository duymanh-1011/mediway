@extends('employee.layout')

@section('title', 'Chi tiết khoản phải thu')

@section('content')

    @php
        $visit = $payable->medicalVisit;
        $patient = $visit?->patient;
        $status = $payable->paymentStatus?->name;

        $successful = $payable->transactions
            ->where('transaction_result', 'SUCCESS');

        $received = (float) $successful->sum('amount');

        $remaining = max(
            0,
            (float) $payable->amount - $received
        );
    @endphp

    <style>
        .f08-detail {
            color: #19354a;
        }

        .f08-detail .header {
            background: linear-gradient(120deg, #0f3551, #0f766e);
            color: white;
            padding: 26px;
            border-radius: 20px;
            margin-bottom: 18px;
        }

        .f08-detail .header a {
            color: #cafff7;
        }

        .f08-detail .header h1 {
            color: white;
            margin: 13px 0 6px;
            font-size: clamp(24px, 3vw, 32px);
        }

        .f08-detail .header p {
            color: #e1f3f3;
            margin: 0;
        }

        .f08-detail .panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 17px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 4px 24px #1b39510d;
        }

        .f08-detail h2 {
            margin: 0 0 17px;
            font-size: 20px;
        }

        .f08-detail .facts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
        }

        .f08-detail .fact {
            background: #f8fafc;
            border: 1px solid #ecf0f5;
            border-radius: 12px;
            padding: 13px;
            min-width: 0;
        }

        .f08-detail .fact dt {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 7px;
        }

        .f08-detail .fact dd {
            margin: 0;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .f08-detail .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .f08-detail .amount {
            background: #f0fdfa;
            border: 1px solid #ccfbf1;
            padding: 18px;
            border-radius: 14px;
        }

        .f08-detail .amount span {
            display: block;
            color: #50657a;
            font-size: 12px;
        }

        .f08-detail .amount strong {
            font-size: 22px;
            color: #115e59;
        }

        .f08-detail .tablewrap {
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .f08-detail table {
            width: 100%;
            min-width: 1060px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .f08-detail th {
            background: #edf7f7;
            text-align: left;
            color: #445b68;
        }

        .f08-detail th,
        .f08-detail td {
            padding: 12px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: top;
        }

        .f08-detail tr:last-child td {
            border-bottom: 0;
        }

        .f08-detail .minor {
            color: #64748b;
            font-size: 12px;
        }

        .f08-detail .note {
            background: #f8fafc;
            padding: 13px;
            border-radius: 10px;
            color: #475569;
        }

        .f08-detail .back {
            display: inline-flex;
            margin-top: 9px;
            padding: 10px 16px;
            border: 1px solid #0f766e;
            border-radius: 10px;
            color: #0f766e;
            text-decoration: none;
            font-weight: 750;
        }

        @media (max-width: 800px) {
            .f08-detail .facts {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 540px) {

            .f08-detail .facts,
            .f08-detail .summary {
                grid-template-columns: 1fr;
            }

            .f08-detail .panel {
                padding: 15px;
            }
        }
    </style>

    <div class="f08-detail">


        <section class="header">

            <a href="{{ route('employee.payments.index', $backQuery) }}">
                ← Quay lại đúng danh sách đã lọc
            </a>

            <h1>Chi tiết khoản phải thu</h1>

            <p>
                {{ $payable->payable_code }} ·
                Thông tin đối soát và lịch sử giao dịch
            </p>

        </section>

        @if (in_array($status, ['UNPAID', 'PENDING'], true) && $payable->serviceOrderItem?->serviceStatus?->name !== 'CANCELLED' && $received == 0 && (float) $payable->amount > 0)
            <p><a class="portal-btn portal-btn--primary" href="{{ route('employee.payments.collect.create', array_merge(['payable' => $payable->id], $backQuery)) }}">Ghi nhận thanh toán</a></p>
        @endif

        <div class="summary">

            <div class="amount">
                <span>Tổng phải thu</span>

                <strong>
                    {{
        number_format(
            (float) $payable->amount,
            0,
            ',',
            '.'
        )
                    }} đ
                </strong>
            </div>

            <div class="amount">
                <span>Đã thu thành công</span>

                <strong>
                    {{
        number_format(
            $received,
            0,
            ',',
            '.'
        )
                    }} đ
                </strong>
            </div>

            <div class="amount">
                <span>
                    Còn phải thu (theo giao dịch thành công)
                </span>

                <strong>
                    {{
        number_format(
            $remaining,
            0,
            ',',
            '.'
        )
                    }} đ
                </strong>
            </div>

        </div>

        <section class="panel">

            <h2>Thông tin khoản thu và bệnh nhân</h2>

            <dl class="facts">

                @foreach([
                            'Mã khoản phải thu' =>
                                $payable->payable_code,

                            'Mã lượt khám' =>
                                $visit?->visit_code,

                            'Mã bệnh nhân' =>
                                $patient?->patient_code,

                            'Họ tên bệnh nhân' =>
                                $patient?->full_name,

                            'Loại khoản thu' =>
                                match ($payable->payment_type) {
                                    'EXAMINATION_FEE' => 'Phí khám',
                                    'SERVICE_FEE' => 'Phí dịch vụ',
                                    'MEDICINE_FEE' => 'Tiền thuốc',
                                    default => $payable->payment_type
                                },

                            'Trạng thái' =>
                                match ($status) {
                                    'UNPAID' => 'Chưa thanh toán',
                                    'PENDING' => 'Chờ xác nhận',
                                    'PAID' => 'Đã thanh toán',
                                    'CANCELLED' => 'Đã hủy',
                                    default => $status
                                },

                            'Ngày phát sinh' =>
                                $payable->generated_at?->format(
                                    'd/m/Y H:i:s'
                                ),

                            'Hạn thanh toán' =>
                                $payable->due_at?->format(
                                    'd/m/Y H:i:s'
                                ),

                            'Ngày hoàn tất' =>
                                $payable->paid_at?->format(
                                    'd/m/Y H:i:s'
                                ),

                            'Mã chỉ định dịch vụ' =>
                                $payable->service_order_item_id,

                            'Mã đơn thuốc' =>
                                $payable->prescription_id,

                        ] as $label => $value)

                        <div class="fact">
                            <dt>{{ $label }}</dt>

                            <dd>
                                {{
                    filled($value)
                    ? $value
                    : '—'
                                    }}
                            </dd>
                        </div>

                @endforeach

            </dl>

            <h3 style="margin-top:22px">
                Nội dung khoản thu
            </h3>

            <p>
                {{
        $payable->description
        ?: 'Không có mô tả.'
                }}
            </p>

            <h3>Ghi chú</h3>

            <p>
                {{
        $payable->notes
        ?: 'Không có ghi chú.'
                }}
            </p>

        </section>

        <section class="panel">

            <h2>Lịch sử giao dịch và người thu tiền</h2>

            <p class="minor">
                Hiển thị đầy đủ giao dịch thành công,
                thất bại và đang xử lý.

                Thông tin người thu được lưu từ giao dịch gốc;
                giao dịch QR tự động có thể không gắn nhân viên.
            </p>

            <div class="tablewrap">

                <table>

                    <thead>
                        <tr>
                            <th>Mã giao dịch</th>
                            <th>Phương thức</th>
                            <th>Kết quả</th>
                            <th>Số tiền</th>
                            <th>Người thu / Mã NV</th>
                            <th>Tham chiếu cổng thanh toán</th>
                            <th>Bắt đầu</th>
                            <th>Thanh toán</th>
                            <th>Xác nhận</th>
                            <th>Nội dung / ghi chú</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse(
                                            $payable->transactions as $transaction
                                        )

                                        <tr>

                                            <td>
                                                <strong>
                                                    {{
                            $transaction->transaction_code
                                                        }}
                                                </strong>
                                            </td>

                                            <td>
                                                {{
                            match ($transaction->payment_method) {
                                'CASH' => 'Tiền mặt',
                                'QR' => 'Chuyển khoản QR',
                                default => $transaction->payment_method
                            }
                                                    }}
                                            </td>

                                            <td>
                                                {{
                            match ($transaction->transaction_result) {
                                'SUCCESS' => 'Thành công',
                                'FAILED' => 'Thất bại',
                                'PENDING' => 'Đang chờ',
                                default => $transaction->transaction_result
                            }
                                                    }}
                                            </td>

                                            <td>
                                                <strong>
                                                    {{
                            number_format(
                                (float) $transaction->amount,
                                0,
                                ',',
                                '.'
                            )
                                                        }} đ
                                                </strong>
                                            </td>

                                            <td>
                                                {{
                            $transaction->collector?->full_name
                            ?? 'Không ghi nhận / tự động'
                                                    }}

                                                <span class="minor" style="display:block">
                                                    {{
                            $transaction->collector?->employee_code
                            ?? '—'
                                                        }}
                                                </span>
                                            </td>

                                            <td>
                                                {{
                            $transaction->provider_transaction_code
                            ?: '—'
                                                    }}
                                            </td>

                                            <td>
                                                {{
                            $transaction->started_at?->format(
                                'd/m/Y H:i:s'
                            ) ?? '—'
                                                    }}
                                            </td>

                                            <td>
                                                {{
                            $transaction->paid_at?->format(
                                'd/m/Y H:i:s'
                            ) ?? '—'
                                                    }}
                                            </td>

                                            <td>
                                                {{
                            $transaction->confirmed_at?->format(
                                'd/m/Y H:i:s'
                            ) ?? '—'
                                                    }}
                                            </td>

                                            <td>
                                                {{
                            $transaction->payment_content
                            ?: '—'
                                                    }}

                                                @if($transaction->failure_reason)
                                                                    <p class="minor">
                                                                        Lý do lỗi:
                                                                        {{
                                                    $transaction->failure_reason
                                                                                }}
                                                                    </p>
                                                @endif

                                                @if($transaction->notes)
                                                                    <p class="minor">
                                                                        Ghi chú:
                                                                        {{
                                                    $transaction->notes
                                                                                }}
                                                                    </p>
                                                @endif
                                            </td>

                                        </tr>

                        @empty

                            <tr>
                                <td colspan="10" style="text-align:center;padding:24px">
                                    Chưa phát sinh giao dịch thanh toán.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>

            </div>

            <p class="note" style="margin-top:16px">
                Thông tin chỉ phục vụ tra cứu và đối soát.

                Không sửa hoặc xóa giao dịch lịch sử
                tại màn hình này.

                Số dư trên đây tính từ các giao dịch thành công,
                cần đối chiếu với trạng thái khoản thu khi
                xử lý hoàn tiền hoặc điều chỉnh.
            </p>

        </section>

        <a class="back" href="{{ route('employee.payments.index', $backQuery) }}">
            ← Trở lại danh sách đã lọc
        </a>

    </div>

@endsection
