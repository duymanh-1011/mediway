@extends('admin.layouts.app')

@section('title', 'Chi tiết chẩn đoán')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Chẩn đoán /
        {{ $diagnosis->medicalVisit?->visit_code }}
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
                Chi tiết chẩn đoán
            </h1>

            <p class="page-description">
                Lượt khám:
                {{ $diagnosis->medicalVisit?->visit_code }}
            </p>

        </div>


        <div style="
            display: flex;
            gap: 8px;
        ">

            <a
                href="{{ route('admin.diagnoses.index') }}"
                class="btn btn-secondary"
            >
                Quay lại
            </a>


            @if (
                $diagnosis->medicalVisit?->status?->name
                !==
                'COMPLETED'
            )

                <a
                    href="{{ route(
                        'admin.diagnoses.edit',
                        $diagnosis
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
                    {{ $diagnosis->medicalVisit?->visit_code ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bệnh nhân</strong>

                <div>
                    {{ $diagnosis->medicalVisit?->patient?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bác sĩ</strong>

                <div>
                    {{ $diagnosis->medicalVisit?->doctor?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Chuyên khoa</strong>

                <div>
                    {{ $diagnosis->medicalVisit?->specialty?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Phòng khám</strong>

                <div>
                    {{ $diagnosis->medicalVisit?->examinationRoom?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Trạng thái</strong>

                <div>
                    {{ $diagnosis->medicalVisit?->status?->name ?: '—' }}
                </div>
            </div>

        </div>


        @if ($diagnosis->medicalVisit)

            <div style="margin-top: 18px;">

                <a
                    href="{{ route(
                        'admin.visits.show',
                        $diagnosis->medicalVisit
                    ) }}"
                    class="btn btn-secondary"
                >
                    Xem lượt khám
                </a>

            </div>

        @endif

    </div>


    <div class="card">

        <div style="
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        ">

            <div>
                <strong>Bệnh</strong>

                <div style="margin-top: 5px;">
                    {{ $diagnosis->disease?->name ?: '—' }}
                </div>
            </div>


            <div>
                <strong>Loại chẩn đoán</strong>

                <div style="margin-top: 5px;">
                    {{ $diagnosis->diagnosis_type ?: '—' }}
                </div>
            </div>


            <div>
                <strong>Mức độ</strong>

                <div style="margin-top: 5px;">
                    {{ $diagnosis->severity ?: '—' }}
                </div>
            </div>


            <div>
                <strong>Thời điểm chẩn đoán</strong>

                <div style="margin-top: 5px;">
                    {{ $diagnosis->diagnosed_at?->format(
                        'd/m/Y H:i:s'
                    ) ?: '—' }}
                </div>
            </div>

        </div>


        <div style="margin-bottom: 22px;">

            <strong>
                Mô tả chẩn đoán
            </strong>

            <div style="
                margin-top: 7px;
                white-space: pre-line;
                line-height: 1.7;
            ">
                {{ $diagnosis->diagnosis_description ?: '—' }}
            </div>

        </div>


        <div>

            <strong>
                Ghi chú
            </strong>

            <div style="
                margin-top: 7px;
                white-space: pre-line;
                line-height: 1.7;
            ">
                {{ $diagnosis->notes ?: '—' }}
            </div>

        </div>

    </div>

@endsection
