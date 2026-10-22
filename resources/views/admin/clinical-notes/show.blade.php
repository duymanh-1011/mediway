@extends('admin.layouts.app')
@section('admin-page', 'clinical-notes-show')

@section('title', 'Chi tiết nội dung khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Nội dung khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ $clinicalNote->medicalVisit?->visit_code }}</strong>
@endsection

@section('content')

    <div class="admin-u-e0f559a1">

        <div>

            <h1 class="page-title">
                Nội dung khám
            </h1>

            <p class="page-description">
                {{ $clinicalNote->medicalVisit?->visit_code }}
            </p>

        </div>


        <div class="admin-u-5786f359">

            <a
                href="{{ route(
                    'admin.clinical-notes.index'
                ) }}"
                class="admin-btn admin-btn-secondary btn btn-secondary"
            >Quay lại</a>

            @if ($clinicalNote->medicalVisit?->canRecordClinicalData())

                <a
                    href="{{ route(
                        'admin.clinical-notes.edit',
                        $clinicalNote
                    ) }}"
                    class="admin-btn btn btn-primary admin-btn-primary"
                >Sửa</a>

            @endif

        </div>

    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <h2 class="admin-u-ec0f7ffa">
            Thông tin lượt khám
        </h2>

        <div class="admin-u-5811d829">

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
                    {{ \App\Models\AdminDisplay::label($clinicalNote->medicalVisit?->status?->name ?: '—') }}
                </div>
            </div>

        </div>


        @if ($clinicalNote->medicalVisit)

            <div class="admin-u-6840f0a6">

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

            <div class="admin-u-ed1667a9">

                <strong>
                    {{ $label }}
                </strong>

                <div class="admin-u-8354f4bb">
                    {{ $value ?: '—' }}
                </div>

            </div>

        @endforeach


        <div>

            <strong>
                Thời điểm ghi nhận
            </strong>

            <div class="admin-u-ade9f648">
                {{ $clinicalNote->recorded_at?->format(
                    'd/m/Y H:i:s'
                ) ?: '—' }}
            </div>

        </div>

    </div>

@endsection
