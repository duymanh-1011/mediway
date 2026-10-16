@extends('admin.layouts.app')

@section('title', 'Chi tiết kết luận lượt khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Kết luận /
        {{ $conclusion->medicalVisit?->visit_code }}
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
                Kết luận lượt khám
            </h1>

            <p class="page-description">
                {{ $conclusion->medicalVisit?->visit_code }}
            </p>

        </div>


        <div style="
            display: flex;
            gap: 8px;
        ">

            <a
                href="{{ route('admin.conclusions.index') }}"
                class="btn btn-secondary"
            >
                Quay lại
            </a>


            @if (
                $conclusion->medicalVisit?->status?->name
                !==
                'COMPLETED'
            )

                <a
                    href="{{ route(
                        'admin.conclusions.edit',
                        $conclusion
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
            Lượt khám
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
                    {{ $conclusion->medicalVisit?->visit_code ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bệnh nhân</strong>
                <div>
                    {{ $conclusion->medicalVisit?->patient?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bác sĩ khám</strong>
                <div>
                    {{ $conclusion->medicalVisit?->doctor?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bác sĩ kết luận</strong>
                <div>
                    {{ $conclusion->doctor?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Chuyên khoa</strong>
                <div>
                    {{ $conclusion->medicalVisit?->specialty?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Phòng khám</strong>
                <div>
                    {{ $conclusion->medicalVisit?->examinationRoom?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Trạng thái</strong>
                <div>
                    {{ $conclusion->medicalVisit?->status?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Thời điểm kết luận</strong>
                <div>
                    {{ $conclusion->concluded_at?->format(
                        'd/m/Y H:i:s'
                    ) ?: '—' }}
                </div>
            </div>

        </div>


        @if ($conclusion->medicalVisit)

            <div style="margin-top: 18px;">

                <a
                    href="{{ route(
                        'admin.visits.show',
                        $conclusion->medicalVisit
                    ) }}"
                    class="btn btn-secondary"
                >
                    Xem toàn bộ lượt khám
                </a>

            </div>

        @endif

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <h2 style="margin-top: 0;">
            Kết luận chuyên môn
        </h2>

        <div style="
            white-space: pre-line;
            line-height: 1.7;
            margin-bottom: 22px;
        ">
            {{ $conclusion->professional_conclusion }}
        </div>


        <div style="margin-bottom: 22px;">

            <strong>Hướng xử trí</strong>

            <div style="margin-top: 6px;">
                {{ $conclusion->disposition ?: '—' }}
            </div>

        </div>


        <div style="margin-bottom: 22px;">

            <strong>Kế hoạch điều trị</strong>

            <div style="
                margin-top: 6px;
                white-space: pre-line;
                line-height: 1.7;
            ">
                {{ $conclusion->treatment_plan ?: '—' }}
            </div>

        </div>


        <div style="margin-bottom: 22px;">

            <strong>Dặn dò</strong>

            <div style="
                margin-top: 6px;
                white-space: pre-line;
                line-height: 1.7;
            ">
                {{ $conclusion->instructions ?: '—' }}
            </div>

        </div>


        <div>

            <strong>Ghi chú</strong>

            <div style="
                margin-top: 6px;
                white-space: pre-line;
                line-height: 1.7;
            ">
                {{ $conclusion->notes ?: '—' }}
            </div>

        </div>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <h2 style="margin-top: 0;">
            Tái khám
        </h2>

        @if ($conclusion->has_follow_up)

            <div style="
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(220px, 1fr));
                gap: 18px;
            ">

                <div>

                    <strong>Ngày tái khám</strong>

                    <div style="margin-top: 5px;">
                        {{ $conclusion->follow_up_date?->format(
                            'd/m/Y'
                        ) ?: '—' }}
                    </div>

                </div>


                <div>

                    <strong>Lý do tái khám</strong>

                    <div style="
                        margin-top: 5px;
                        white-space: pre-line;
                    ">
                        {{ $conclusion->follow_up_reason ?: '—' }}
                    </div>

                </div>

            </div>

        @else

            <p style="color: #64748b;">
                Không yêu cầu tái khám.
            </p>

        @endif

    </div>


    <div class="card">

        <h2 style="margin-top: 0;">
            Phiếu kết luận
        </h2>

        @if ($conclusion->conclusion_pdf_path)

            <div>
                {{ $conclusion->conclusion_pdf_path }}
            </div>

        @else

            <p style="color: #64748b;">
                Chưa có file PDF kết luận.
            </p>

        @endif

    </div>

@endsection
