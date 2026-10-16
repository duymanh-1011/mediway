@extends('admin.layouts.app')

@section('title', 'Chi tiết nội dung khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Nội dung khám /
        {{ $clinicalNote->medicalVisit?->visit_code }}
    </div>
@endsection

@section('content')

    <div style="
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px;
    ">

        <div>

            <h1 class="page-title">
                Nội dung khám
            </h1>

            <p class="page-description">
                {{ $clinicalNote->medicalVisit?->visit_code }}
            </p>

        </div>


        <div style="
            display: flex;
            gap: 8px;
        ">

            <a
                href="{{ route(
                    'admin.clinical-notes.index'
                ) }}"
                class="btn btn-secondary"
            >
                Quay lại
            </a>

            @if (
                $clinicalNote->medicalVisit?->status?->name
                !==
                'COMPLETED'
            )

                <a
                    href="{{ route(
                        'admin.clinical-notes.edit',
                        $clinicalNote
                    ) }}"
                    class="btn btn-primary"
                >
                    Sửa
                </a>

            @endif

        </div>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <h2 style="margin-top: 0;">
            Thông tin lượt khám
        </h2>

        <div style="
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        ">

            <div>
                <strong>Mã lượt khám</strong>

                <div>
                    {{ $clinicalNote->medicalVisit?->visit_code ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bệnh nhân</strong>

                <div>
                    {{ $clinicalNote->medicalVisit?->patient?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bác sĩ</strong>

                <div>
                    {{ $clinicalNote->medicalVisit?->doctor?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Chuyên khoa</strong>

                <div>
                    {{ $clinicalNote->medicalVisit?->specialty?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Phòng khám</strong>

                <div>
                    {{ $clinicalNote->medicalVisit?->examinationRoom?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Trạng thái</strong>

                <div>
                    {{ $clinicalNote->medicalVisit?->status?->name ?: '—' }}
                </div>
            </div>

        </div>


        @if ($clinicalNote->medicalVisit)

            <div style="margin-top: 18px;">

                <a
                    href="{{ route(
                        'admin.visits.show',
                        $clinicalNote->medicalVisit
                    ) }}"
                    class="btn btn-secondary"
                >
                    Xem lượt khám
                </a>

            </div>

        @endif

    </div>


    <div class="card">

        @php
            $fields = [
                'Lý do khám' =>
                    $clinicalNote->reason_for_visit,

                'Triệu chứng hiện tại' =>
                    $clinicalNote->current_symptoms,

                'Bệnh sử hiện tại' =>
                    $clinicalNote->present_illness_history,

                'Tiền sử liên quan' =>
                    $clinicalNote->relevant_medical_history,

                'Khám lâm sàng' =>
                    $clinicalNote->physical_examination,

                'Nhận định ban đầu' =>
                    $clinicalNote->initial_assessment,

                'Ghi chú' =>
                    $clinicalNote->notes,
            ];
        @endphp


        @foreach ($fields as $label => $value)

            <div style="
                margin-bottom: 22px;
            ">

                <strong>
                    {{ $label }}
                </strong>

                <div style="
                    margin-top: 7px;
                    line-height: 1.7;
                    white-space: pre-line;
                ">
                    {{ $value ?: '—' }}
                </div>

            </div>

        @endforeach


        <div>

            <strong>
                Thời điểm ghi nhận
            </strong>

            <div style="margin-top: 7px;">
                {{ $clinicalNote->recorded_at?->format(
                    'd/m/Y H:i:s'
                ) ?: '—' }}
            </div>

        </div>

    </div>

@endsection
