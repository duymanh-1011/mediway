<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title>
        Phiếu thu - {{ $transaction->transaction_code }}
    </title>


    @vite(['resources/css/payment-receipt.css', 'resources/js/employee.js'])
</head>

<body @class(['medicine-receipt' => $receiptType === 'prescription'])>
    @php
        /*
         * Dữ liệu giao dịch lấy từ Controller.
         * Không ghi dữ liệu hoặc tạo giao dịch trong view.
         */

        $rawPaidAt = $transaction->paid_at
            ?? $transaction->confirmed_at
            ?? ($receiptType === 'prescription' ? null : $transaction->created_at);

        $paidAt = $rawPaidAt
            ? \Carbon\Carbon::parse($rawPaidAt)
                ->timezone('Asia/Ho_Chi_Minh')
            : null;

        $amount = (int) round(
            (float) ($transaction->amount ?? 0)
        );

        $amountFormatted = number_format(
            $amount,
            0,
            ',',
            '.'
        );

        /*
         * Chỉ hiển thị thông tin cơ sở khi đã cấu hình.
         * Không in địa chỉ hoặc số điện thoại giả.
         */

        $clinicName = config('mediway.clinic_name');
        $clinicAddress = config('mediway.clinic_address');
        $clinicPhone = config('mediway.clinic_phone');

        $paymentType = match ($payable->payment_type ?? null) {
            'EXAMINATION_FEE' => 'Phí khám bệnh',
            'SERVICE_FEE' => 'Phí dịch vụ kỹ thuật',
            'MEDICINE_FEE' => 'Tiền thuốc',
            default => 'Khoản thu khám chữa bệnh',
        };

        $receiptSubtitle = match ($receiptType) {
            'exam' => 'Hóa đơn khám bệnh',
            'service' => 'Hóa đơn dịch vụ',
            'prescription' => 'HÓA ĐƠN TIỀN THUỐC',
        };

        $description = $payable->displayContent();

        /*
         * Đọc tiền thành chữ.
         *
         * Ví dụ:
         * 120000  => Một trăm hai mươi nghìn đồng
         * 1400000 => Một triệu bốn trăm nghìn đồng
         * 1000001 => Một triệu không trăm lẻ một đồng
         */

        $digits = [
            'không',
            'một',
            'hai',
            'ba',
            'bốn',
            'năm',
            'sáu',
            'bảy',
            'tám',
            'chín',
        ];

        $readThree = function (int $number, bool $readFull = false) use ($digits): string {
            $hundreds = intdiv($number, 100);
            $tens = intdiv($number % 100, 10);
            $ones = $number % 10;

            $words = [];

            if ($hundreds > 0 || $readFull) {
                $words[] = $digits[$hundreds] . ' trăm';
            }

            if ($tens > 1) {
                $words[] = $digits[$tens] . ' mươi';

                if ($ones === 1) {
                    $words[] = 'mốt';
                } elseif ($ones === 5) {
                    $words[] = 'lăm';
                } elseif ($ones > 0) {
                    $words[] = $digits[$ones];
                }
            } elseif ($tens === 1) {
                $words[] = 'mười';

                if ($ones === 5) {
                    $words[] = 'lăm';
                } elseif ($ones > 0) {
                    $words[] = $digits[$ones];
                }
            } elseif ($ones > 0) {
                if ($hundreds > 0 || $readFull) {
                    $words[] = 'lẻ';
                }

                $words[] = $digits[$ones];
            }

            return implode(' ', $words);
        };

        $amountWords = 'Chưa xác định';

        if ($amount >= 0 && $amount <= 999999999999999) {
            if ($amount === 0) {
                $amountWords = 'Không đồng';
            } else {
                $scaleNames = [
                    '',
                    'nghìn',
                    'triệu',
                    'tỷ',
                    'nghìn tỷ',
                ];

                $groups = [];
                $value = $amount;

                while ($value > 0) {
                    $groups[] = $value % 1000;
                    $value = intdiv($value, 1000);
                }

                $highest = count($groups) - 1;
                $pieces = [];

                for ($i = $highest; $i >= 0; $i--) {
                    $group = $groups[$i];

                    if ($group === 0) {
                        continue;
                    }

                    $readFull = (
                        $i < $highest
                        && $group < 100
                    );

                    $words = $readThree(
                        $group,
                        $readFull
                    );

                    $scale = $scaleNames[$i] ?? '';

                    $pieces[] = trim(
                        $words . ' ' . $scale
                    );
                }

                $amountWords = ucfirst(
                    implode(' ', $pieces)
                ) . ' đồng';
            }
        }

        $collectorName = $collector?->full_name
            ?? 'Chưa ghi nhận';

        $collectorCode = $collector?->employee_code
            ?? '—';

        $patientName = $patient?->full_name
            ?? 'Chưa ghi nhận';
    @endphp

    <div class="screen-wrap">

        <nav class="toolbar" aria-label="Thao tác phiếu thu">

            <a href="{{ route(
    'employee.payments.show',
    ['payable' => $payable->id]
) }}">
                ← Quay lại chi tiết
            </a>

            <button type="button" data-f08-print>
                In phiếu thu A4
            </button>

        </nav>

        <main class="paper">

            <!-- THÔNG TIN CƠ SỞ -->
            <header class="document-head">

                <div>
                    <div class="brand-line">

                        <span class="brand-icon" aria-hidden="true">+</span>

                        <div>
                            <h2 class="brand-name">
                                MEDIWAY
                            </h2>

                            <p class="brand-sub">
                                Hệ thống quản lý phòng khám
                            </p>
                        </div>

                    </div>

                    @if (
                            $clinicName ||
                            $clinicAddress ||
                            $clinicPhone
                        )
                        <div class="facility">

                            @if ($clinicName)
                                <div>
                                    <strong>
                                        {{ $clinicName }}
                                    </strong>
                                </div>
                            @endif

                            @if ($clinicAddress)
                                <div>
                                    Địa chỉ:
                                    {{ $clinicAddress }}
                                </div>
                            @endif

                            @if ($clinicPhone)
                                <div>
                                    Điện thoại:
                                    {{ $clinicPhone }}
                                </div>
                            @endif

                        </div>
                    @endif
                </div>

                <div class="document-meta">

                    <div>
                        <span class="meta-label">
                            Số giao dịch:
                        </span>

                        <strong>
                            {{ $transaction->transaction_code }}
                        </strong>
                    </div>

                    @if ($receiptType === 'prescription')
                        <div><span class="meta-label">Mã đơn thuốc:</span>
                            <strong>{{ $prescription?->prescription_code ?? '—' }}</strong>
                        </div>
                    @endif
                    @if ($receiptType === 'exam')
                        <div><span class="meta-label">Mã bệnh nhân:</span>
                            <strong>{{ $patient?->patient_code ?? '—' }}</strong>
                        </div>
                    @endif

                </div>

            </header>

            <!-- TIÊU ĐỀ -->
            <div class="document-title">

                <h1>
                    @if ($receiptType === 'exam')
                        HÓA ĐƠN
                    @elseif ($receiptType === 'service')
                        HÓA ĐƠN
                    @else
                        HÓA ĐƠN
                    @endif
                </h1>

                <p>
                    @if ($receiptType === 'exam')
                        Hóa đơn khám bệnh<br>Chứng từ ghi nhận thanh toán tại quầy.
                    @elseif ($receiptType === 'service')
                        Hóa đơn dịch vụ - Chứng từ ghi nhận thanh toán tại quầy
                    @else
                        {{ $receiptSubtitle }} · Chứng từ ghi nhận thanh toán tại quầy
                    @endif
                </p>

                <span class="status">
                    ĐÃ THANH TOÁN
                </span>

            </div>

            @if ($receiptType === 'prescription')
                @include('employee.payments.receipts.prescription')
            @elseif ($receiptType === 'exam')
                @include('employee.payments.receipts.exam')
            @elseif ($receiptType === 'service')
                @include('employee.payments.receipts.service')
            @else
                <!-- PHẦN I -->
                <section class="section">

                    <h3 class="section-title">
                        I. THÔNG TIN BỆNH NHÂN
                    </h3>

                    <div class="info-grid">

                        <div class="info-item">
                            <span class="field-label">
                                Họ và tên bệnh nhân
                            </span>

                            <strong class="field-value">
                                {{ $patientName }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span class="field-label">
                                Mã bệnh nhân
                            </span>

                            <strong class="field-value">
                                {{ $patient?->patient_code ?? '—' }}
                            </strong>
                        </div>

                        @if ($receiptType === 'service')
                            <div class="info-item">
                                <span class="field-label">
                                    Mã lượt khám
                                </span>

                                <strong class="field-value">
                                    {{ $visit?->visit_code ?? '—' }}
                                </strong>
                            </div>
                        @endif

                        <div class="info-item">
                            <span class="field-label">
                                Mã khoản phải thu
                            </span>

                            <strong class="field-value">
                                {{ $payable->payable_code ?? '—' }}
                            </strong>
                        </div>

                        <div @class(['info-item', 'full' => $receiptType !== 'prescription'])>
                            <span class="field-label">
                                Nội dung thu
                            </span>

                            <strong class="field-value">
                                {{ $description }}
                            </strong>
                        </div>

                    </div>

                </section>

                <!-- PHẦN II -->
                <section class="section">

                    <h3 class="section-title">
                        II. CHI TIẾT THANH TOÁN
                    </h3>

                    @include('employee.payments.receipts.' . $receiptType)

                    <div class="money-words">

                        <strong>
                            Số tiền bằng chữ:
                        </strong>

                        <em>
                            {{ $amountWords }}.
                        </em>

                    </div>

                    <div class="total-box">

                        <span class="total-label">
                            TỔNG SỐ TIỀN ĐÃ THU
                        </span>

                        <strong class="total-value">
                            {{ $amountFormatted }} đ
                        </strong>

                    </div>

                </section>

                <!-- PHẦN III -->
                <section class="section">

                    <h3 class="section-title">
                        III. THÔNG TIN GIAO DỊCH
                    </h3>

                    <div class="info-grid">

                        <div class="info-item">
                            <span class="field-label">
                                Phương thức thanh toán
                            </span>

                            <strong class="field-value">
                                Tiền mặt
                            </strong>
                        </div>

                        <div class="info-item">
                            <span class="field-label">
                                Trạng thái giao dịch
                            </span>

                            <strong class="field-value">
                                Thành công
                            </strong>
                        </div>

                        <div class="info-item">
                            <span class="field-label">
                                Nhân viên thu tiền
                            </span>

                            <strong class="field-value">
                                {{ $collectorName }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span class="field-label">
                                Mã nhân viên thu tiền
                            </span>

                            <strong class="field-value">
                                {{ $collectorCode }}
                            </strong>
                        </div>

                    </div>

                </section>

            @endif
            @if ($receiptType === 'prescription')
            <div class="medicine-approval">@endif
                <!-- NGÀY LẬP -->
                <div class="sign-date">

                    Ngày {{ $paidAt?->format('d') ?? '...' }}
                    tháng {{ $paidAt?->format('m') ?? '...' }}
                    năm {{ $paidAt?->format('Y') ?? '...' }}

                </div>

                <!-- CHỮ KÝ -->
                <section @class(['signature-grid', 'signature-grid--medicine' => $receiptType === 'prescription'])>

                    @if ($receiptType === 'prescription')
                        <div>
                            <div class="signature-title">NGƯỜI GIAO THUỐC</div>
                            <div class="signature-note">(Ký, ghi rõ họ tên)</div>
                            <div class="signature-space"></div>
                        </div>
                    @endif

                    <div>

                        <div class="signature-title">
                            {{ $receiptType === 'prescription' ? 'BỆNH NHÂN / NGƯỜI NHẬN THUỐC' : ($receiptType === 'exam' ? 'XÁC NHẬN CỦA NGƯỜI BỆNH' : 'NGƯỜI NỘP TIỀN') }}
                        </div>

                        <div class="signature-note">
                            (Ký, ghi rõ họ tên)
                        </div>

                        <div class="signature-space"></div>

                        <div class="signature-name">
                            <!-- Để người nộp thực tế ký và ghi tên -->
                        </div>

                    </div>

                    <div>

                        <div class="signature-title">
                            {{ $receiptType === 'exam' ? 'NGƯỜI LẬP BẢNG KÊ / NGƯỜI THU TIỀN' : 'NGƯỜI THU TIỀN' }}
                        </div>

                        <div class="signature-note">
                            (Ký, ghi rõ họ tên)
                        </div>

                        <div class="signature-space"></div>

                        <div class="signature-name">
                            {{ $collectorName }}
                        </div>

                    </div>

                </section>

                <!-- CHÂN TRANG -->
                <footer class="receipt-footer">

                    <div>
                        <strong>MEDIWAY</strong>
                    </div>

                    @if ($receiptType === 'prescription')
                        <div>Vui lòng kiểm tra thuốc trước khi rời quầy và sử dụng theo chỉ định của bác sĩ.</div>
                    @endif
                    <div>
                        Phiếu thu được lập từ dữ liệu giao dịch
                        đã ghi nhận trong hệ thống.
                    </div>

                    <div>
                        Phiếu thu không thay thế hóa đơn điện tử
                        hoặc hóa đơn giá trị gia tăng.
                    </div>

                </footer>
                @if ($receiptType === 'prescription')
                </div>@endif

        </main>

    </div>

</body>

</html>
