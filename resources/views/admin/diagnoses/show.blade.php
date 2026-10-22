@extends('admin.layouts.app')
@section('admin-page', 'diagnoses-show')

@section('title', 'Chi tiết chẩn đoán')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Chẩn đoán</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ $diagnosis->medicalVisit?->visit_code }}</strong>
@endsection

@section('content')

    <div class="admin-u-e0f559a1">

        <div>

            <h1 class="page-title">
                Chi tiết chẩn đoán
            </h1>

            <p class="page-description">
                Lượt khám:
                {{ $diagnosis->medicalVisit?->visit_code }}
            </p>

        </div>


        <div class="admin-u-5786f359">

            <a
                href="{{ route('admin.diagnoses.index') }}"
                class="admin-btn admin-btn-secondary btn btn-secondary"
            >Quay lại</a>


            @if ($diagnosis->medicalVisit?->canRecordClinicalData())

                <a
                    href="{{ route(
                        'admin.diagnoses.edit',
                        $diagnosis
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
            Lượt khám
        </h2>

        <div class="admin-u-5811d829">

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
                    {{ \App\Models\AdminDisplay::label($diagnosis->medicalVisit?->status?->name ?: '—') }}
                </div>
            </div>

        </div>


        @if ($diagnosis->medicalVisit)

            <div class="admin-u-6840f0a6">

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

        <div class="admin-u-88def7f5">

            <div>
                <strong>Bệnh</strong>

                <div class="admin-u-035790fa">
                    {{ $diagnosis->disease?->name ?: '—' }}
                </div>
            </div>


            <div>
                <strong>Loại chẩn đoán</strong>

                <div class="admin-u-035790fa">
                    {{ \App\Models\AdminDisplay::label($diagnosis->diagnosis_type ?: '—') }}
                </div>
            </div>


            <div>
                <strong>Mức độ</strong>

                <div class="admin-u-035790fa">
                    {{ \App\Models\AdminDisplay::label($diagnosis->severity ?: '—') }}
                </div>
            </div>


            <div>
                <strong>Thời điểm chẩn đoán</strong>

                <div class="admin-u-035790fa">
                    {{ $diagnosis->diagnosed_at?->format(
                        'd/m/Y H:i:s'
                    ) ?: '—' }}
                </div>
            </div>

        </div>


        <div class="admin-u-ed1667a9">

            <strong>
                Mô tả chẩn đoán
            </strong>

            <div class="admin-u-4b947e64">
                {{ $diagnosis->diagnosis_description ?: '—' }}
            </div>

        </div>


        <div>

            <strong>
                Ghi chú
            </strong>

            <div class="admin-u-4b947e64">
                {{ $diagnosis->notes ?: '—' }}
            </div>

        </div>

    </div>

@endsection
