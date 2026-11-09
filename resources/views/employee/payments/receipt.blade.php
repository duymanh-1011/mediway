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

    $money = static fn ($value) => $value === null ? '—' : number_format((float) $value, 0, ',', '.');
    $isMedicine = $receiptType === 'prescription';
    $isExam = $receiptType === 'exam';
    $examFee = data_get($receiptData ?? $receipt ?? [], 'exam_fee_amount', $payable->amount);
    $payerAmount = data_get($receiptData ?? $receipt ?? [], 'patient_payable_amount');
    $insurance = data_get($receiptData ?? $receipt ?? [], 'insurance_allocation');
    $insuranceLabels = [
        'government_budget' => 'Nguồn ngân sách chi trả',
        'insurance_unit_price' => 'Đơn giá bảo hiểm y tế',
        'insurance_amount' => 'Thành tiền bảo hiểm y tế',
        'insurance_paid' => 'Bảo hiểm y tế thanh toán',
        'patient_copay' => 'Bệnh nhân cùng chi trả',
        'patient_self_pay' => 'Bệnh nhân tự trả',
        'cancelled_service' => 'Dịch vụ đã hủy',
    ];
    $insuranceRows = [];
    if ($isExam && is_array($insurance)) {
        foreach ($insuranceLabels as $key => $label) {
            if (filled($insurance[$key] ?? null)) {
                $insuranceRows[$label] = $money($insurance[$key]).' đ';
            }
        }
    }
    $patientFields = [
        'Họ và tên' => $patient->full_name,
        'Ngày sinh' => $patient->date_of_birth?->format('d/m/Y'),
        'Giới tính' => $patient->gender ? \App\Models\AdminDisplay::label($patient->gender) : null,
        'Số CCCD' => $patient->detail?->citizen_id_number,
        'Số điện thoại' => $patient->user?->phone,
        'Địa chỉ' => $patient->detail?->current_address ?: $patient->detail?->permanent_address,
    ];
    $identifiers = ['Số giao dịch:' => $transaction->transaction_code, 'Mã bệnh nhân:' => $patient->patient_code];
    if ($isMedicine) {
        $identifiers['Mã đơn thuốc:'] = $prescription->prescription_code;
    }
    $rows = match ($receiptType) {
        'exam' => [['Phí khám bệnh', 'Lần', 1, $examFee, $examFee]],
        // Mỗi khoản thu dịch vụ lưu giá chỉ định, không có số lượng riêng.
        'service' => [[$serviceItem->medicalService->name, $serviceItem->medicalService->unit, null,
            $serviceItem->price_at_order, $serviceItem->price_at_order]],
        'prescription' => $prescription->items->map(fn ($item) => [
            $item->medicine->name.($item->medicine->strength ? ' · '.$item->medicine->strength : ''),
            $item->usage_unit, $item->quantity, $item->unit_price, $item->line_total,
        ]),
    };
@endphp

<x-print.document
    :title="'Phiếu thu '.$transaction->transaction_code"
    heading="HÓA ĐƠN"
    brand-subtitle="Chứng từ thanh toán"
    :facility="[$clinicName, $visit->examinationRoom?->name, $clinicAddress, $clinicPhone]"
    :identifiers="$identifiers"
    :back-url="$receiptBackUrl ?? route('employee.payments.show', ['payable' => $payable->id])"
    :back-label="$receiptBackLabel ?? 'Quay lại chi tiết'"
    print-label="In phiếu thu A4"
>
    <x-slot:titleDetails>
        <div class="print-receipt-title-details">
            <span class="status">ĐÃ THANH TOÁN</span>
        </div>
    </x-slot:titleDetails>

    <section class="print-section" aria-labelledby="receipt-patient">
        <h2 class="print-heading" id="receipt-patient"><span>I.</span> Thông tin bệnh nhân</h2>
        <x-print.fields :values="$patientFields" :wide="['Họ và tên', 'Địa chỉ']" />
    </section>

    <section class="print-section" aria-labelledby="receipt-details">
        <div class="print-section-head">
            <h2 class="print-heading" id="receipt-details"><span>II.</span> Chi phí khám, chữa bệnh</h2>
        </div>
        <div class="print-table-wrap" tabindex="0" role="region" aria-labelledby="receipt-details">
            <table class="print-table" aria-labelledby="receipt-details">
                <thead>
                    <tr>
                        <th class="print-table-index" scope="col">STT</th>
                        <th class="print-table-label" scope="col">Nội dung</th>
                        <th class="print-table-unit" scope="col">Đơn vị</th>
                        <th class="print-table-quantity" scope="col">Số lượng</th>
                        <th scope="col">Đơn giá (đ)</th>
                        <th scope="col">Thành tiền (đ)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as [$label, $unit, $quantity, $price, $total])
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <th scope="row">{{ $label }}</th>
                            <td>{{ $unit ?: '—' }}</td>
                            <td>{{ $quantity !== null ? str_replace('.', ',', (string) (float) $quantity) : '—' }}</td>
                            <td class="print-table-number">{{ $money($price) }}</td>
                            <td class="print-table-number">{{ $money($total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th scope="row" colspan="5">TỔNG CHI PHÍ KHÁM, CHỮA BỆNH</th>
                        <td class="print-table-number">{{ $amountFormatted }} đ</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="print-document-text"><strong>Số tiền bằng chữ</strong> {{ $amountWords }}.</p>

        @if ($insuranceRows)
            <h3 class="print-heading print-subheading">Phân bổ nguồn thanh toán</h3>
            <x-print.fields class="print-facts--wide" :values="$insuranceRows" :wide="array_keys($insuranceRows)" />
        @endif
        @if ($isExam && filled($payerAmount))
            <p><strong>Số tiền bệnh nhân phải trả</strong> {{ $money($payerAmount) }} đ</p>
        @endif
    </section>

    @if ($paidAt)
        <p class="sign-date">Ngày {{ $paidAt->format('d') }} tháng {{ $paidAt->format('m') }} năm {{ $paidAt->format('Y') }}</p>
    @endif
    <section class="signature-grid" aria-label="Khu vực chữ ký">
        <div>
            <div class="signature-title">XÁC NHẬN CỦA NGƯỜI BỆNH</div>
            <div class="signature-note">(Ký, ghi rõ họ tên)</div>
            <div class="signature-space" aria-hidden="true"></div>
        </div>
        <div>
            <div class="signature-title">NGƯỜI LẬP BẢNG KÊ / NGƯỜI THU TIỀN</div>
            <div class="signature-note">(Ký, ghi rõ họ tên)</div>
            <div class="signature-space" aria-hidden="true"></div>
            <div class="signature-name">{{ $collectorName }}</div>
        </div>
    </section>

    <footer class="receipt-footer">
        <p>Phiếu thu được lập từ giao dịch đã ghi nhận trong Mediway và không thay thế hóa đơn điện tử hoặc hóa đơn giá trị gia tăng.</p>
    </footer>
</x-print.document>
