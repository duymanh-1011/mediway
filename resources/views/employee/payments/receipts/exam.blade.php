
@php
    /*
    |--------------------------------------------------------------------------
    | HÓA ĐƠN TIỀN KHÁM — MEDIWAY F08
    |--------------------------------------------------------------------------
    | Dữ liệu $examPatient và $amountWords đã được chuẩn bị
    | từ controller / ReceiptDataBuilder hiện tại.
    |
    | Không sửa dữ liệu thanh toán, không tự tạo dữ liệu BHYT,
    | không tự thay đổi các giao dịch đã ghi nhận.
    */

    $source = $receiptData ?? $receipt ?? [];

    $patientName = filled($examPatient['name'] ?? null)
        ? $examPatient['name']
        : '—';

    $address = filled($examPatient['address'] ?? null)
        ? $examPatient['address']
        : '—';

    $age = $examPatient['age'] ?? null;

    $gender = filled($examPatient['gender'] ?? null)
        ? $examPatient['gender']
        : '—';

    $phone = filled($examPatient['phone'] ?? null)
        ? $examPatient['phone']
        : '—';

    $examDate = $examPatient['exam_date'] ?? null;

    if ($examDate instanceof \DateTimeInterface) {
        $examDate = $examDate->format('d/m/Y H:i');
    }

    $diagnosis = filled($examPatient['diagnosis'] ?? null)
        ? $examPatient['diagnosis']
        : '—';

    /*
    |--------------------------------------------------------------------------
    | Phương thức nộp tiền
    |--------------------------------------------------------------------------
    */

    $paymentMethod = data_get(
        $source,
        'payment_method',
        data_get($transaction, 'payment_method')
    );

    $paymentLabel = match ($paymentMethod) {
        'CASH' => 'Tiền mặt',
        'QR' => 'QR',
        default => '—',
    };

    /*
    |--------------------------------------------------------------------------
    | Số tiền
    |--------------------------------------------------------------------------
    */

    $money = static function ($amount) {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return number_format(
            (float) $amount,
            0,
            ',',
            '.'
        );
    };

    $fee = data_get($source, 'exam_fee_amount')
        ?? data_get($payable, 'amount');

    $moneyWords = $amountWords ?? null;

    /*
    |--------------------------------------------------------------------------
    | Phân bổ nguồn thanh toán / BHYT
    |--------------------------------------------------------------------------
    | Chỉ hiển thị bảng khi có dữ liệu thực tế.
    | Khi không có dữ liệu, không hiển thị khung thông báo.
    */

    $insurance = data_get(
        $source,
        'insurance_allocation'
    );

    $insuranceFields = [
        'government_budget' => 'Nguồn ngân sách chi trả',
        'insurance_unit_price' => 'Đơn giá BHYT',
        'insurance_amount' => 'Thành tiền BHYT',
        'insurance_paid' => 'BHYT thanh toán',
        'patient_copay' => 'BN cùng chi trả',
        'patient_self_pay' => 'BN tự trả',
        'cancelled_service' => 'DV hủy',
    ];

    $hasInsurance = is_array($insurance)
        && collect($insuranceFields)
            ->keys()
            ->contains(function ($key) use ($insurance) {
                $value = data_get($insurance, $key);

                return $value !== null && $value !== '';
            });

    $patientPayableAmount = data_get(
        $source,
        'patient_payable_amount'
    );
@endphp



<section class="mw-exam">

    {{-- ============================================
         I. THÔNG TIN HÀNH CHÍNH
    ============================================= --}}

    <h3 class="mw-exam-title">
        I. HÀNH CHÍNH
    </h3>

    <div class="mw-exam-info">

        <div class="mw-exam-info-column">

            <p>
                <strong>Họ tên người bệnh:</strong>
                {{ $patientName }}
            </p>

            <p>
                <strong>Địa chỉ:</strong>
                {{ $address }}
            </p>

            <p>
                <strong>Ngày khám:</strong>
                {{ $examDate ?: '—' }}
            </p>

            <p>
                <strong>Chẩn đoán:</strong>
                {{ $diagnosis }}
            </p>

        </div>

        <div class="mw-exam-info-column">

            <p>
                <strong>Tuổi:</strong>
                {{ $age ?? '—' }}
            </p>

            <p>
                <strong>Giới tính:</strong>
                {{ $gender }}
            </p>

            <p>
                <strong>Số điện thoại:</strong>
                {{ $phone }}
            </p>

        </div>

    </div>

    {{-- ============================================
         II. CHI PHÍ KHÁM, CHỮA BỆNH
         Hình thức nộp tiền nằm cùng hàng tiêu đề
    ============================================= --}}

    <div class="mw-exam-section-heading">

        <h3>
            II. CHI PHÍ KHÁM, CHỮA BỆNH
        </h3>

        <div class="mw-exam-payment-method">
            <strong>Hình thức nộp tiền:</strong>
            <span>{{ $paymentLabel }}</span>
        </div>

    </div>

    {{-- ============================================
         BẢNG CHI PHÍ KHÁM
    ============================================= --}}

    <table class="mw-exam-table">

        <colgroup>
            <col class="mw-col-8">
            <col class="mw-col-34">
            <col class="mw-col-10">
            <col class="mw-col-8">
            <col class="mw-col-20">
            <col class="mw-col-20">
        </colgroup>

        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Nội dung</th>
                <th scope="col">ĐVT</th>
                <th scope="col">SL</th>
                <th scope="col">Đơn giá DV</th>
                <th scope="col">Thành tiền DV</th>
            </tr>
        </thead>

        <tbody>

            <tr class="group-row">
                <td colspan="6">
                    KHÁM
                </td>
            </tr>

            <tr>
                <td class="center">
                    1
                </td>

                <td>
                    Phí khám bệnh
                </td>

                <td class="center">
                    Lần
                </td>

                <td class="center">
                    1
                </td>

                <td class="right">
                    {{ $money($fee) }}
                </td>

                <td class="right">
                    {{ $money($fee) }}
                </td>
            </tr>

            <tr class="total-row">

                <td colspan="5">
                    TỔNG CHI PHÍ KHÁM, CHỮA BỆNH
                </td>

                <td class="right">
                    {{ $money($fee) }}
                </td>

            </tr>

        </tbody>

    </table>

    {{-- ============================================
         PHÂN BỔ BHYT (CHỈ KHI CÓ DỮ LIỆU)

         Không còn ô thông báo toàn dấu —
         khi thiếu dữ liệu.
    ============================================= --}}

    @if($hasInsurance)

        <div class="mw-exam-insurance">

            <h4 class="mw-exam-insurance-title">
                Phân bổ nguồn thanh toán
            </h4>

            <table class="mw-exam-insurance-table">

                <tbody>

                    @foreach($insuranceFields as $key => $label)

                        @php
                            $insuranceValue = data_get(
                                $insurance,
                                $key
                            );
                        @endphp

                        @if(
                            $insuranceValue !== null
                            && $insuranceValue !== ''
                        )
                            <tr>

                                <th scope="row">
                                    {{ $label }}
                                </th>

                                <td>
                                    {{ $money($insuranceValue) }}
                                </td>

                            </tr>
                        @endif

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif

    {{-- ============================================
         TỔNG HỢP THANH TOÁN

         Đã bỏ dòng "TỔNG SỐ TIỀN ĐÃ THU".
    ============================================= --}}

    <div class="mw-exam-summary">

        <div class="mw-exam-summary-line">

            <span>
                Tổng chi phí khám, chữa bệnh
            </span>

            <span>
                {{ $money($fee) }} đ
            </span>

        </div>

        @if(
            $patientPayableAmount !== null
            && $patientPayableAmount !== ''
        )
            <div class="mw-exam-summary-line">

                <span>
                    Số tiền bệnh nhân phải trả
                </span>

                <span>
                    {{ $money($patientPayableAmount) }} đ
                </span>

            </div>
        @endif

        @if(filled($moneyWords))

            <div class="mw-exam-words">
                <strong>Số tiền bằng chữ:</strong>
                {{ $moneyWords }}
            </div>

        @endif

    </div>

</section>
