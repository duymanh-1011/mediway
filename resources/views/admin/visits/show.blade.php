@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết lượt khám - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin /
        Lượt khám /
        {{ $medicalVisit->visit_code }}
    </div>

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
                {{ $medicalVisit->visit_code }}
            </h1>

            <p class="page-description">
                Chi tiết lượt khám và hồ sơ lâm sàng.
            </p>
        </div>


        <div class="header-actions">

            <a
                href="{{ route('admin.visits.index') }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>


            @if ($medicalVisit->appointment)

                <a
                    href="{{ route(
                        'admin.appointments.show',
                        $medicalVisit->appointment
                    ) }}"
                    class="btn btn-secondary"
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

            <strong class="status-badge">
                {{ $currentStatus ?? '—' }}
            </strong>

        </div>


        @if ($allowedVisitStatuses->isNotEmpty())

            <div class="status-actions">

                <form
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
                                    {{ $status->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Cập nhật trạng thái
                    </button>

                </form>


                @if ($completedStatus)

                    <form
                        method="POST"
                        action="{{ route(
                            'admin.visits.update-status',
                            $medicalVisit
                        ) }}"
                        onsubmit="
                            return confirm(
                                'Xác nhận hoàn tất lượt khám này?'
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
                            class="btn-complete"
                        >
                            ✓ Hoàn tất lượt khám
                        </button>

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
                    {{ $medicalVisit->visit_code }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Nguồn tiếp nhận</span>

                <strong>
                    {{ $medicalVisit->reception_source ?? '—' }}
                </strong>
            </div>


            <div class="detail-row">
                <span>Check-in lúc</span>

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
                    {{ $medicalVisit->reception_notes ?? '—' }}
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
                        {{ $medicalVisit
                            ->appointment
                            ->appointment_code }}
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


                <div style="margin-top: 15px;">

                    <a
                        href="{{ route(
                            'admin.appointments.show',
                            $medicalVisit->appointment
                        ) }}"
                        class="btn btn-secondary"
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
                    {{ $medicalVisit
                        ->patient
                        ?->patient_code
                        ?? '—' }}
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

                <div style="margin-top: 15px;">

                    <a
                        href="{{ route(
                            'admin.patients.show',
                            $medicalVisit->patient
                        ) }}"
                        class="btn btn-secondary"
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
                    {{ $medicalVisit
                        ->doctor
                        ?->doctor_code
                        ?? '—' }}

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
                    {{ $medicalVisit
                        ->examinationRoom
                        ?->room_code
                        ?? '—' }}

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
                class="btn btn-secondary"
            >
                Danh sách hàng đợi
            </a>

        </div>


        @if ($medicalVisit->queues->isNotEmpty())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Số</th>
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

                                <td>
                                    {{ $queue->queue_number }}
                                </td>

                                <td>
                                    {{ $queue->queue_type }}
                                </td>

                                <td>
                                    {{ $queue
                                        ->status
                                        ?->name
                                        ?? '—' }}
                                </td>

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

                                <td>

                                    <a
                                        href="{{ route(
                                            'admin.queues.show',
                                            $queue
                                        ) }}"
                                        class="btn btn-secondary"
                                    >
                                        Chi tiết
                                    </a>

                                </td>

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
                    class="btn btn-secondary"
                >
                    Xem nội dung khám
                </a>

            </div>

        @else

            <p class="muted-text">
                Chưa có nội dung khám.
            </p>

            @if ($currentStatus !== 'COMPLETED')

                <div class="section-link">

                    <a
                        href="{{ route(
                            'admin.visits.clinical-note.create',
                            $medicalVisit
                        ) }}"
                        class="btn btn-primary"
                    >
                        + Thêm nội dung khám
                    </a>

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

                                <td>
                                    {{ $diagnosis
                                        ->disease
                                        ?->name
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ $diagnosis
                                        ->diagnosis_type
                                        ?? '—' }}
                                </td>

                                <td>
                                    {{ $diagnosis
                                        ->severity
                                        ?? '—' }}
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

                                <td>

                                    <a
                                        href="{{ route(
                                            'admin.diagnoses.show',
                                            $diagnosis
                                        ) }}"
                                        class="btn btn-secondary"
                                    >
                                        Chi tiết
                                    </a>

                                </td>

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


        @if ($currentStatus !== 'COMPLETED')

            <div class="section-link">

                <a
                    href="{{ route(
                        'admin.visits.diagnoses.create',
                        $medicalVisit
                    ) }}"
                    class="btn btn-primary"
                >
                    + Thêm chẩn đoán
                </a>

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
                        {{ $medicalVisit
                            ->conclusion
                            ->disposition
                            ?? '—' }}
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
                    class="btn btn-secondary"
                >
                    Xem kết luận
                </a>

            </div>

        @else

            <p class="muted-text">
                Chưa có kết luận.
            </p>

            @if ($currentStatus !== 'COMPLETED')

                <div class="section-link">

                    <a
                        href="{{ route(
                            'admin.visits.conclusion.create',
                            $medicalVisit
                        ) }}"
                        class="btn btn-primary"
                    >
                        + Thêm kết luận
                    </a>

                </div>

            @endif

        @endif

    </div>

@endsection


@push('styles')

<style>

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 20px;
    }


    .header-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }


    .section-spacing {
        margin-top: 20px;
    }


    .section-title {
        margin-top: 0;
        margin-bottom: 15px;
    }


    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }


    .status-card {
        margin-bottom: 20px;
    }


    .status-current {
        margin-bottom: 18px;
    }


    .status-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #e2e8f0;
    }


    .status-actions {
        display: flex;
        align-items: flex-end;
        gap: 14px;
        flex-wrap: wrap;
    }


    .status-form {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }


    .form-group {
        min-width: 260px;
    }


    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 700;
    }


    .form-control {
        width: 100%;
        padding: 9px 11px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;

        font-family:
            "Times New Roman",
            Times,
            serif;
    }


    .btn-complete {
        padding: 10px 18px;
        border: none;
        border-radius: 8px;

        background: #16a34a;
        color: #ffffff;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 15px;
        font-weight: 700;

        cursor: pointer;
    }


    .btn-complete:hover {
        opacity: 0.9;
    }


    .detail-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 20px;
    }


    .detail-row {
        display: grid;

        grid-template-columns:
            145px minmax(0, 1fr);

        gap: 15px;

        padding: 11px 0;

        border-bottom: 1px solid #f1f5f9;
    }


    .detail-row:last-child {
        border-bottom: none;
    }


    .detail-row span {
        color: #64748b;
    }


    .clinical-grid {
        display: grid;

        grid-template-columns:
            repeat(
                auto-fit,
                minmax(250px, 1fr)
            );

        gap: 18px;
    }


    .clinical-item {
        padding: 14px;

        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }


    .clinical-item p {
        margin-bottom: 0;

        line-height: 1.6;
        white-space: pre-line;
    }


    .section-link {
        margin-top: 16px;
    }


    .muted-text {
        color: #64748b;
    }


    .table-wrapper {
        overflow-x: auto;
    }


    @media (max-width: 800px) {

        .page-header {
            flex-direction: column;
        }


        .detail-grid {
            grid-template-columns: 1fr;
        }


        .status-actions,
        .status-form {
            flex-direction: column;
            align-items: stretch;
        }


        .form-group {
            min-width: 0;
        }

    }

</style>

@endpush
