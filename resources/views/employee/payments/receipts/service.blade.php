@php
    /*
    |--------------------------------------------------------------------------
    | MEDIWAY — HÓA ĐƠN DỊCH VỤ CHỈ ĐỊNH
    |--------------------------------------------------------------------------
    | Giữ dữ liệu từ ReceiptDataBuilder hiện tại.
    | Không tự tính lại hoặc thay đổi giao dịch tài chính.
    */

    $patientName = $serviceReceipt['patient_name'] ?: '—';
    $age = $serviceReceipt['patient_age'];
    $gender = $serviceReceipt['patient_gender'] ?: '—';
    $address = $serviceReceipt['patient_address'] ?: '—';
    $phone = $serviceReceipt['patient_phone'] ?: '—';

    $doctorName = $serviceReceipt['doctor_name'] ?: '—';
    $department = $serviceReceipt['department_name'];
    $room = $serviceReceipt['room_name'];

    $serviceName = $serviceReceipt['service_name'] ?: '—';
    $serviceGroup = $serviceReceipt['service_group'];

    $unitName = $serviceReceipt['service_unit'] ?: '—';
    $quantity = $serviceReceipt['quantity'];
    $unitPrice = $serviceReceipt['unit_price'];
    $lineTotal = $serviceReceipt['line_total'];

    $receiptAmount = $transaction->amount;
    $paymentMethod = $transaction->payment_method;

    $paymentLabel = match ($paymentMethod) {
        'CASH' => 'Tiền mặt',
        'QR' => 'QR',
        default => '—',
    };

    $moneyWords = $amountWords;

    $money = static fn($amount) => $amount === null
        ? '—'
        : number_format((float) $amount, 0, ',', '.');

    $quantityLabel = $quantity === null
        ? '—'
        : number_format((float) $quantity, 0, ',', '.');

    $departmentRoom = collect([
        $department,
        $room,
    ])->filter(fn($value) => filled($value))
        ->join(' / ');

    $departmentRoom = $departmentRoom !== ''
        ? $departmentRoom
        : '—';
@endphp



<section class="mw-service">

    {{-- ======================================================
    I. THÔNG TIN BỆNH NHÂN
    ======================================================= --}}

    <div class="ms-section">

        <h3 class="ms-section-title">
            I. THÔNG TIN BỆNH NHÂN
        </h3>

        <div class="ms-info-grid">

            <div class="ms-info-column">

                <p class="ms-info-row">
                    <strong class="ms-label">
                        Họ và tên:
                    </strong>
                    <span class="ms-value">
                        {{ $patientName }}
                    </span>
                </p>

                <p class="ms-info-row">
                    <strong class="ms-label">
                        Tuổi:
                    </strong>
                    <span class="ms-value">
                        {{ $age ?? '—' }}
                    </span>
                </p>

                <p class="ms-info-row ms-info-row--long">
                    <strong class="ms-label">
                        Địa chỉ:
                    </strong>
                    <span class="ms-value">
                        {{ $address }}
                    </span>
                </p>

            </div>

            <div class="ms-info-column">

                <p class="ms-info-row">
                    <strong class="ms-label">
                        Giới tính:
                    </strong>
                    <span class="ms-value">
                        {{ $gender }}
                    </span>
                </p>

                <p class="ms-info-row">
                    <strong class="ms-label">
                        Số điện thoại:
                    </strong>
                    <span class="ms-value">
                        {{ $phone }}
                    </span>
                </p>

            </div>

        </div>

    </div>

    {{-- ======================================================
    II. THÔNG TIN CHỈ ĐỊNH
    ======================================================= --}}

    <div class="ms-section">

        <h3 class="ms-section-title">
            II. THÔNG TIN CHỈ ĐỊNH
        </h3>

        <div class="ms-info-grid">

            <div class="ms-info-column">

                <p class="ms-info-row ms-info-row--long">
                    <strong class="ms-label">
                        Bác sĩ chỉ định:
                    </strong>
                    <span class="ms-value">
                        {{ $doctorName }}
                    </span>
                </p>

                <p class="ms-info-row ms-info-row--long">
                    <strong class="ms-label">
                        Nội dung chỉ định:
                    </strong>
                    <span class="ms-value">
                        {{ $serviceName }}
                    </span>
                </p>

            </div>

            <div class="ms-info-column">

                <p class="ms-info-row ms-info-row--long">
                    <strong class="ms-label">
                        Chuyên khoa / phòng khám:
                    </strong>
                    <span class="ms-value">
                        {{ $departmentRoom }}
                    </span>
                </p>

                @if(filled($serviceGroup))
                    <p class="ms-info-row ms-info-row--long">
                        <strong class="ms-label">
                            Nhóm dịch vụ:
                        </strong>
                        <span class="ms-value">
                            {{ $serviceGroup }}
                        </span>
                    </p>
                @endif

            </div>

        </div>

    </div>

    {{-- ======================================================
    III. CHI TIẾT DỊCH VỤ
    ======================================================= --}}

    <div class="ms-service-heading">

        <h3>
            III. CHI TIẾT DỊCH VỤ
        </h3>

        <div class="ms-payment-method">
            <strong>
                Hình thức nộp tiền:
            </strong>
            <span>
                {{ $paymentLabel }}
            </span>
        </div>

    </div>

    <div class="ms-table-wrap">

        <table class="ms-table">

            <colgroup>
                <col class="mw-col-8">
                <col class="mw-col-34">
                <col class="mw-col-11">
                <col class="mw-col-8">
                <col class="mw-col-19">
                <col class="mw-col-20">
            </colgroup>

            <thead>
                <tr>
                    <th scope="col">STT</th>
                    <th scope="col">Dịch vụ</th>
                    <th scope="col">Đơn vị</th>
                    <th scope="col">SL</th>
                    <th scope="col">Đơn giá (VNĐ)</th>
                    <th scope="col">Thành tiền (VNĐ)</th>
                </tr>
            </thead>

            <tbody>

                <tr>

                    <td class="ms-center">
                        01
                    </td>

                    <td>
                        <span class="ms-service-name">
                            {{ $serviceName }}
                        </span>
                    </td>

                    <td class="ms-center">
                        {{ $unitName }}
                    </td>

                    <td class="ms-center">
                        {{ $quantityLabel }}
                    </td>

                    <td class="ms-right">
                        {{ $money($unitPrice) }}
                    </td>

                    <td class="ms-right">
                        {{ $money($lineTotal) }}
                    </td>

                </tr>

                <tr class="ms-total-row">

                    <td colspan="5" class="ms-total-label">
                        TỔNG TIỀN GIAO DỊCH
                    </td>

                    <td class="ms-right">
                        {{ $money($receiptAmount) }}
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    {{-- ======================================================
    SỐ TIỀN BẰNG CHỮ
    ======================================================= --}}

    @if(filled($moneyWords))

        <div class="ms-words">
            <strong>
                Số tiền bằng chữ:
            </strong>

            <span>
                {{ $moneyWords }}
            </span>
        </div>

    @endif

</section>