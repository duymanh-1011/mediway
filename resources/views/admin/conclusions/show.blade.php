@extends('admin.layouts.app')
@section('admin-page', 'conclusions-show')

@section('title', 'Chi tiết kết luận lượt khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Kết luận</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">@include('admin.layouts.partials.record-code', ['code' => $conclusion->medicalVisit?->visit_code])</strong>
@endsection

@section('content')

    <div class="admin-u-e0f559a1">

        <div>

            <h1 class="page-title">
                Kết luận lượt khám
            </h1>

            <p class="page-description">
                @include('admin.layouts.partials.record-code', ['code' => $conclusion->medicalVisit?->visit_code])
            </p>

        </div>


        <div class="admin-u-5786f359">

            <a
                href="{{ route('admin.conclusions.index') }}"
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>


            @if ($conclusion->medicalVisit?->canRecordClinicalData())

                <a
                    href="{{ route(
                        'admin.conclusions.edit',
                        $conclusion
                    ) }}"
                    class="admin-btn admin-btn-secondary"
                >Sửa</a>

            @endif

        </div>

    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <h2 class="admin-u-ec0f7ffa">
            Lượt khám
        </h2>

        <div class="admin-u-5811d829">

            <div>
                <strong>Mã lượt khám</strong>
                <div>
                    @include('admin.layouts.partials.record-code', ['code' => $conclusion->medicalVisit?->visit_code ?: '—'])
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
                <div>@include('admin.layouts.partials.status-badge', ['badgeCode' => $conclusion->medicalVisit?->status?->name ?: '—'])</div>
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

            <div class="admin-u-6840f0a6">

                <a
                    href="{{ route(
                        'admin.visits.show',
                        $conclusion->medicalVisit
                    ) }}"
                    class="admin-btn admin-btn-detail"
                >
                    Xem lượt khám
                </a>

            </div>

        @endif

    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <h2 class="admin-u-ec0f7ffa">
            Kết luận chuyên môn
        </h2>

        <div class="admin-u-0c8bc1bc">
            {{ $conclusion->professional_conclusion }}
        </div>


        <div class="admin-u-ed1667a9">

            <strong>Hướng xử trí</strong>

            <div class="admin-u-a5595d11">
                {{ \App\Models\AdminDisplay::label($conclusion->disposition ?: '—') }}
            </div>

        </div>


        <div class="admin-u-ed1667a9">

            <strong>Kế hoạch điều trị</strong>

            <div class="admin-u-411f0bf0">
                {{ $conclusion->treatment_plan ?: '—' }}
            </div>

        </div>


        <div class="admin-u-ed1667a9">

            <strong>Dặn dò</strong>

            <div class="admin-u-411f0bf0">
                {{ $conclusion->instructions ?: '—' }}
            </div>

        </div>


        <div>

            <strong>Ghi chú</strong>

            <div class="admin-u-411f0bf0">
                {{ $conclusion->notes ?: '—' }}
            </div>

        </div>

    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <h2 class="admin-u-ec0f7ffa">
            Tái khám
        </h2>

        @if ($conclusion->has_follow_up)

            <div class="admin-u-5933b926">

                <div>

                    <strong>Ngày tái khám</strong>

                    <div class="admin-u-035790fa">
                        {{ $conclusion->follow_up_date?->format(
                            'd/m/Y'
                        ) ?: '—' }}
                    </div>

                </div>


                <div>

                    <strong>Lý do tái khám</strong>

                    <div class="admin-u-7041c3b9">
                        {{ $conclusion->follow_up_reason ?: '—' }}
                    </div>

                </div>

            </div>

        @else

            <p class="admin-u-a6f48144">
                Không yêu cầu tái khám.
            </p>

        @endif

    </div>


    <div class="card">

        <h2 class="admin-u-ec0f7ffa">
            Phiếu kết luận
        </h2>

        @if ($conclusion->conclusion_pdf_path)

            <div>
                {{ $conclusion->conclusion_pdf_path }}
            </div>

        @else

            <p class="admin-u-a6f48144">
                Chưa có tệp PDF kết luận.
            </p>

        @endif

    </div>

@endsection
