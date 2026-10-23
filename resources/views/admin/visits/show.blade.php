@extends('admin.layouts.app')
@section('admin-page', 'visits-show')

@section(
    'title',
    'Chi tiết lượt khám - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Lượt khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">@include('admin.layouts.partials.record-code', ['code' => $medicalVisit->visit_code])</strong>
@endsection


@section('content')

    @php
        $currentStatus = $medicalVisit->status?->name;

        $completedStatus = $allowedVisitStatuses
            ->firstWhere('name', 'COMPLETED');
    @endphp


    <div class="page-header">

        <div>
            <h1 class="page-title">
                @include('admin.layouts.partials.record-code', ['code' => $medicalVisit->visit_code])
            </h1>

            <p class="page-description">
                Chi tiết lượt khám và hồ sơ lâm sàng.
            </p>
        </div>


        <div class="header-actions">

            <a
                href="{{ route('admin.visits.index') }}"
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>


            @if ($medicalVisit->appointment)

                <a
                    href="{{ route(
                        'admin.appointments.show',
                        $medicalVisit->appointment
                    ) }}"
                    class="admin-btn admin-btn-detail"
                >
                    Xem lịch hẹn
                </a>

            @endif

        </div>

    </div>


    {{-- TRẠNG THÁI LƯỢT KHÁM --}}

    <div class="card status-card">

        <h3 class="section-title">
            Trạng thái lượt khám
        </h3>


        <div class="status-current">

            <span>
                Hiện tại:
            </span>

            <strong class="status-badge">@include('admin.layouts.partials.status-badge', ['badgeCode' => $currentStatus ?? '—'])</strong>

        </div>


        @if ($allowedVisitStatuses->isNotEmpty())

            <div class="status-actions">

                <form novalidate
                    method="POST"
                    action="{{ route(
                        'admin.visits.update-status',
                        $medicalVisit
                    ) }}"
                    class="status-form"
                >

                    @csrf
                    @method('PATCH')


                    <div class="form-group">

                        <label>
                            Chuyển trạng thái
                        </label>

                        <select
                            name="visit_status_id"
                            class="form-control"
                            required
                        >

                            <option value="">
                                -- Chọn trạng thái --
                            </option>


                            @foreach ($allowedVisitStatuses as $status)

                                <option value="{{ $status->id }}">
                                    {{ \App\Models\AdminDisplay::label($status->name) }}
                                </option>

                            @endforeach

                        </select>
@error('visit_status_id')<div class="field-error">{{ $message }}</div>@enderror
                        @if ($completedStatus)
                            <p class="form-help">Chỉ hoàn tất khi đã có đầy đủ chẩn đoán, kết luận và không còn dịch vụ cần chờ.</p>
                        @endif

                    </div>


                    <button
                        type="submit"
                        class="admin-btn admin-btn-primary"
                    >
                        Cập nhật trạng thái
                    </button>

                </form>


                @if ($completedStatus)

                    <form novalidate
                        method="POST"
                        action="{{ route(
                            'admin.visits.update-status',
                            $medicalVisit
                        ) }}"
                        onsubmit="
                            return confirm(
                                'Hoàn tất lượt khám này? Sau khi hoàn tất, bạn không thể sửa trực tiếp nội dung khám, chẩn đoán, kết luận hoặc đơn thuốc.'
                            );
                        "
                    >

                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="visit_status_id"
                            value="{{ $completedStatus->id }}"
                        >

                        <button
                            type="submit"
                            class="admin-btn admin-btn-success"
                        >Hoàn tất lượt khám</button>

                    </form>

                @endif

            </div>

        @else

            <p class="muted-text">
                Lượt khám không còn trạng thái tiếp theo.
            </p>

        @endif

    </div>


    {{-- THÔNG TIN LƯỢT KHÁM --}}

    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin lượt khám
            </h3>


            <div class="detail-row">
                <span>Mã lượt khám</span>

                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $medicalVisit->visit_code])
                </strong>
            </div>


            <div class="detail-row">
                <span>Nguồn tiếp nhận</span>

                <strong>
                    {{ \App\Models\AdminDisplay::label($medicalVisit->reception_source ?? '—') }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Tiếp nhận lúc</span>

                <strong>
                    {{ $medicalVisit
                        ->checked_in_at
                        ?->format('d/m/Y H:i')
                        ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Bắt đầu khám</span>

                <strong>
                    {{ $medicalVisit
                        ->examination_started_at
                        ?->format('d/m/Y H:i')
                        ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Kết thúc khám</span>

                <strong>
                    {{ $medicalVisit
                        ->examination_ended_at
                        ?->format('d/m/Y H:i')
                        ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Ghi chú tiếp nhận</span>

                <strong>
                    {{ \App\Models\AdminDisplay::text($medicalVisit->reception_notes ?? '—') }}
                </strong>
            </div>

        </div>


        <div class="card">

            <h3 class="section-title">
                Lịch hẹn nguồn
            </h3>


            @if ($medicalVisit->appointment)

                <div class="detail-row">
                    <span>Mã lịch hẹn</span>

                    <strong>
                        @include('admin.layouts.partials.record-code', ['code' => $medicalVisit
                            ->appointment
                            ->appointment_code])
                    </strong>
                </div>


                <div class="detail-row">
                    <span>Ngày khám</span>

                    <strong>
                        {{ $medicalVisit
                            ->appointment
                            ->appointment_date
                            ?->format('d/m/Y')
                            ?? '—' }}
                    </strong>
                </div>


                <div class="detail-row">
                    <span>Lý do khám</span>

                    <strong>
                        {{ $medicalVisit
                            ->appointment
                            ->reason_for_visit
                            ?? '—' }}
                    </strong>
                </div>


                <div class="admin-u-c0e2fdc5">

                    <a
                        href="{{ route(
                            'admin.appointments.show',
                            $medicalVisit->appointment
                        ) }}"
                        class="admin-btn admin-btn-detail"
                    >
                        Xem chi tiết lịch hẹn
                    </a>

                </div>

            @else

                <p class="muted-text">
                    Lượt khám này không có lịch hẹn nguồn.
                </p>

            @endif

        </div>

    </div>


    {{-- BỆNH NHÂN + THÔNG TIN KHÁM --}}

    <div class="detail-grid section-spacing">

        <div class="card">

            <h3 class="section-title">
                Bệnh nhân
            </h3>


            <div class="detail-row">
                <span>Mã bệnh nhân</span>

                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $medicalVisit
                        ->patient
                        ?->patient_code
                        ?? '—'])
                </strong>
            </div>


            <div class="detail-row">
                <span>Họ tên</span>

                <strong>
                    {{ $medicalVisit
                        ->patient
                        ?->full_name
                        ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Ngày sinh</span>

                <strong>
                    {{ $medicalVisit
                        ->patient
                        ?->date_of_birth
                        ?->format('d/m/Y')
                        ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Giới tính</span>

                <strong>
                    {{ $medicalVisit
                        ->patient
                        ?->gender
                        ?? '—' }}
                </strong>
            </div>


            @if ($medicalVisit->patient)

                <div class="admin-u-c0e2fdc5">

                    <a
                        href="{{ route(
                            'admin.patients.show',
                            $medicalVisit->patient
                        ) }}"
                        class="admin-btn admin-btn-detail"
                    >
                        Xem hồ sơ bệnh nhân
                    </a>

                </div>

            @endif

        </div>


        <div class="card">

            <h3 class="section-title">
                Thông tin khám
            </h3>


            <div class="detail-row">
                <span>Bác sĩ</span>

                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $medicalVisit
                        ->doctor
                        ?->doctor_code
                        ?? '—'])

                    -

                    {{ $medicalVisit
                        ->doctor
                        ?->full_name
                        ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Chuyên khoa</span>

                <strong>
                    {{ $medicalVisit
                        ->specialty
                        ?->name
                        ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Phòng khám</span>

                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $medicalVisit
                        ->examinationRoom
                        ?->room_code
                        ?? '—'])

                    -

                    {{ $medicalVisit
                        ->examinationRoom
                        ?->name
                        ?? '—' }}
                </strong>
            </div>

        </div>

    </div>


    {{-- HÀNG ĐỢI --}}

    <div class="card section-spacing">

        <div class="section-header">

            <h3 class="section-title">
                Hàng đợi phòng khám
            </h3>

            <a
                href="{{ route('admin.queues.index') }}"
                class="admin-btn admin-btn-detail"
            >
                Danh sách hàng đợi
            </a>

        </div>


        @if ($medicalVisit->queues->isNotEmpty())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Số hàng đợi</th>
                            <th>Loại</th>
                            <th>Trạng thái</th>
                            <th>Vào hàng</th>
                            <th>Được gọi</th>
                            <th>Bắt đầu</th>
                            <th>Hoàn tất</th>
                            <th>Lần gọi</th>
                            <th></th>
                        </tr>
                    </thead>


                    <tbody>

                        @foreach ($medicalVisit->queues as $queue)

                            <tr>

                                @include('admin.layouts.partials.row-number', ['rows' => $medicalVisit->queues, 'index' => $loop->index])
                                <td>
                                    {{ $queue->queue_number }}
                                </td>

                                <td>
                                    {{ \App\Models\AdminDisplay::label($queue->queue_type) }}
                                </td>

                                <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $queue->status?->name ?? '—'])</td>

                                <td>
                                    {{ $queue
                                        ->queued_at
                                        ?->format('d/m/Y H:i')
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ $queue
                                        ->called_at
                                        ?->format('d/m/Y H:i')
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ $queue
                                        ->service_started_at
                                        ?->format('d/m/Y H:i')
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ $queue
                                        ->completed_at
                                        ?->format('d/m/Y H:i')
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ $queue->call_count }}
                                </td>

                                <td><div class="admin-actions admin-table-actions">

                                    <a
                                        href="{{ route(
                                            'admin.queues.show',
                                            $queue
                                        ) }}"
                                        class="admin-btn admin-btn-detail"
                                    >Chi tiết</a>

                                </div></td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <p class="muted-text">
                Chưa có hàng đợi.
            </p>

        @endif

    </div>


    {{-- NỘI DUNG KHÁM --}}

    <div class="card section-spacing">

        <h3 class="section-title">
            Nội dung khám
        </h3>


        @if ($medicalVisit->clinicalNote)

            <div class="clinical-grid">

                <div class="clinical-item">
                    <strong>Lý do khám</strong>

                    <p>
                        {{ $medicalVisit
                            ->clinicalNote
                            ->reason_for_visit
                            ?? '—' }}
                    </p>
                </div>


                <div class="clinical-item">
                    <strong>Triệu chứng hiện tại</strong>

                    <p>
                        {{ $medicalVisit
                            ->clinicalNote
                            ->current_symptoms
                            ?? '—' }}
                    </p>
                </div>


                <div class="clinical-item">
                    <strong>Nhận định ban đầu</strong>

                    <p>
                        {{ $medicalVisit
                            ->clinicalNote
                            ->initial_assessment
                            ?? '—' }}
                    </p>
                </div>

            </div>


            <div class="section-link">

                <a
                    href="{{ route(
                        'admin.clinical-notes.show',
                        $medicalVisit->clinicalNote
                    ) }}"
                    class="admin-btn admin-btn-detail"
                >
                    Xem nội dung khám
                </a>

            </div>

        @else

            <p class="muted-text">
                Chưa có nội dung khám.
            </p>

            @if ($medicalVisit->canRecordClinicalData())

                <div class="section-link">

                    <a
                        href="{{ route(
                            'admin.visits.clinical-note.create',
                            $medicalVisit
                        ) }}"
                        class="admin-btn admin-btn-primary"
                    >Thêm nội dung khám</a>

                </div>

            @endif

        @endif

    </div>


    {{-- CHẨN ĐOÁN --}}

    <div class="card section-spacing">

        <h3 class="section-title">
            Chẩn đoán
        </h3>


        @if ($medicalVisit->diagnoses->isNotEmpty())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Bệnh</th>
                            <th>Loại</th>
                            <th>Mức độ</th>
                            <th>Mô tả</th>
                            <th>Thời gian</th>
                            <th></th>
                        </tr>
                    </thead>


                    <tbody>

                        @foreach ($medicalVisit->diagnoses as $diagnosis)

                            <tr>

                                @include('admin.layouts.partials.row-number', ['rows' => $medicalVisit->diagnoses, 'index' => $loop->index])
                                <td>
                                    {{ $diagnosis
                                        ->disease
                                        ?->name
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ \App\Models\AdminDisplay::label($diagnosis
                                        ->diagnosis_type
                                        ?? '—') }}
                                </td>

                                <td>
                                    {{ \App\Models\AdminDisplay::label($diagnosis
                                        ->severity
                                        ?? '—') }}
                                </td>

                                <td>
                                    {{ $diagnosis
                                        ->diagnosis_description
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ $diagnosis
                                        ->diagnosed_at
                                        ?->format('d/m/Y H:i')
                                        ?? '—' }}
                                </td>

                                <td><div class="admin-actions admin-table-actions">

                                    <a
                                        href="{{ route(
                                            'admin.diagnoses.show',
                                            $diagnosis
                                        ) }}"
                                        class="admin-btn admin-btn-detail"
                                    >Chi tiết</a>

                                </div></td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <p class="muted-text">
                Chưa có chẩn đoán.
            </p>

        @endif


        @if ($medicalVisit->canRecordClinicalData())

            <div class="section-link">

                <a
                    href="{{ route(
                        'admin.visits.diagnoses.create',
                        $medicalVisit
                    ) }}"
                    class="admin-btn admin-btn-primary"
                >Thêm chẩn đoán</a>

            </div>

        @endif

    </div>


    {{-- CHỈ ĐỊNH DỊCH VỤ --}}

    <div class="card section-spacing">

        <div class="section-header">

            <div>
                <h3 class="section-title">
                    Chỉ định dịch vụ
                </h3>

                <p class="muted-text section-description">
                    Các phiếu dịch vụ được chỉ định trong lượt khám này.
                </p>
            </div>


            @if (in_array($currentStatus, ['IN_EXAMINATION', 'RETURNING'], true))

                <a
                    href="{{ route(
                        'admin.visits.service-orders.create',
                        $medicalVisit
                    ) }}"
                    class="admin-btn admin-btn-primary"
                >Tạo chỉ định dịch vụ</a>

            @endif

        </div>


        @if ($medicalVisit->serviceOrders->isNotEmpty())

            <div class="table-wrapper service-orders-table">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Mã phiếu</th>
                            <th>Bác sĩ chỉ định</th>
                            <th>Thời gian</th>
                            <th>Số dịch vụ</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>


                    <tbody>

                        @foreach ($medicalVisit->serviceOrders as $order)

                            @php
                                $orderTotal = $order->items
                                    ->sum(
                                        fn ($item) =>
                                            (float) $item->price_at_order
                                    );
                            @endphp

                            <tr>

                                @include('admin.layouts.partials.row-number', ['rows' => $medicalVisit->serviceOrders, 'index' => $loop->index])
                                <td>
                                    <strong>
                                        @include('admin.layouts.partials.record-code', ['code' => $order->service_order_code])
                                    </strong>
                                </td>


                                <td>
                                    {{ $order
                                        ->orderedByDoctor
                                        ?->full_name
                                        ?? '—' }}
                                </td>


                                <td>
                                    {{ $order
                                        ->ordered_at
                                        ?->format('d/m/Y H:i')
                                        ?? '—' }}
                                </td>


                                <td>
                                    {{ $order->items->count() }}
                                </td>


                                <td>
                                    <strong>
                                        {{ number_format(
                                            $orderTotal,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                        đ
                                    </strong>
                                </td>


                                <td>
                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $order->status ?? '—'])
                                </td>


                                <td><div class="admin-actions admin-table-actions">

                                    <a
                                        href="{{ route(
                                            'admin.service-orders.show',
                                            $order->id
                                        ) }}"
                                        class="admin-btn admin-btn-detail"
                                    >Chi tiết</a>

                                </div></td>

                            </tr>


                            <tr class="service-items-row">

                                <td colspan="8">

                                    <div class="service-items">

                                        @foreach ($order->items as $item)

                                            <div class="service-item-summary">

                                                <div>

                                                    <strong>
                                                        {{ $item
                                                            ->medicalService
                                                            ?->name
                                                            ?? 'Dịch vụ không xác định' }}
                                                    </strong>

                                                    <span class="service-code">
                                                        @include('admin.layouts.partials.record-code', ['code' => $item
                                                            ->medicalService
                                                            ?->service_code
                                                            ?? ''])
                                                    </span>

                                                </div>


                                                <div class="service-item-meta">

                                                    <span>
                                                        {{ number_format(
                                                            (float) $item->price_at_order,
                                                            0,
                                                            ',',
                                                            '.'
                                                        ) }}
                                                        đ
                                                    </span>

                                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $item->serviceStatus?->name ?? '—'])

                                                </div>

                                            </div>

                                        @endforeach

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="empty-service-orders">

                <p>
                    Lượt khám này chưa có phiếu chỉ định dịch vụ.
                </p>

                @if (in_array($currentStatus, ['IN_EXAMINATION', 'RETURNING'], true))

                    <a
                        href="{{ route(
                            'admin.visits.service-orders.create',
                            $medicalVisit
                        ) }}"
                        class="admin-btn admin-btn-primary"
                    >Tạo chỉ định dịch vụ</a>

                @endif

            </div>

        @endif

    </div>


    {{-- KẾT LUẬN --}}

    <div class="card section-spacing">

        <h3 class="section-title">
            Kết luận lượt khám
        </h3>


        @if ($medicalVisit->conclusion)

            <div class="clinical-grid">

                <div class="clinical-item">
                    <strong>
                        Bác sĩ kết luận
                    </strong>

                    <p>
                        {{ $medicalVisit
                            ->conclusion
                            ->doctor
                            ?->full_name
                            ?? '—' }}
                    </p>
                </div>


                <div class="clinical-item">
                    <strong>
                        Kết luận chuyên môn
                    </strong>

                    <p>
                        {{ $medicalVisit
                            ->conclusion
                            ->professional_conclusion
                            ?? '—' }}
                    </p>
                </div>


                <div class="clinical-item">
                    <strong>
                        Hướng xử trí
                    </strong>

                    <p>
                        {{ \App\Models\AdminDisplay::label($medicalVisit
                            ->conclusion
                            ->disposition
                            ?? '—') }}
                    </p>
                </div>


                <div class="clinical-item">
                    <strong>
                        Kế hoạch điều trị
                    </strong>

                    <p>
                        {{ $medicalVisit
                            ->conclusion
                            ->treatment_plan
                            ?? '—' }}
                    </p>
                </div>


                <div class="clinical-item">
                    <strong>
                        Dặn dò
                    </strong>

                    <p>
                        {{ $medicalVisit
                            ->conclusion
                            ->instructions
                            ?? '—' }}
                    </p>
                </div>


                <div class="clinical-item">
                    <strong>
                        Tái khám
                    </strong>

                    <p>

                        @if ($medicalVisit->conclusion->has_follow_up)

                            Có

                            @if ($medicalVisit->conclusion->follow_up_date)

                                -
                                {{ $medicalVisit
                                    ->conclusion
                                    ->follow_up_date
                                    ->format('d/m/Y') }}

                            @endif

                        @else

                            Không

                        @endif

                    </p>
                </div>

            </div>


            <div class="section-link">

                <a
                    href="{{ route(
                        'admin.conclusions.show',
                        $medicalVisit->conclusion
                    ) }}"
                    class="admin-btn admin-btn-detail"
                >
                    Xem kết luận
                </a>

            </div>

        @else

            <p class="muted-text">
                Chưa có kết luận.
            </p>

            @if ($medicalVisit->canRecordClinicalData())

                <div class="section-link">

                    <a
                        href="{{ route(
                            'admin.visits.conclusion.create',
                            $medicalVisit
                        ) }}"
                        class="admin-btn admin-btn-primary"
                    >Thêm kết luận</a>

                </div>

            @endif

        @endif

    </div>

@endsection
