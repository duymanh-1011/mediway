@extends('admin.layouts.app')
@section('admin-page', 'clinical-notes-index')

@section('title', 'Nội dung khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Nội dung khám</strong>
@endsection

@section('content')

    <div class="admin-u-ed1667a9">

        <h1 class="page-title">
            Nội dung khám
        </h1>

        <p class="page-description">
            Tra cứu nội dung lâm sàng
            được ghi nhận trong từng lượt khám.
        </p>

    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc nội dung khám',
        'description' => 'Lọc theo từ khóa, bác sĩ, trạng thái lượt khám và khoảng thời gian.',
    ])
        <form
            method="GET"
            action="{{ route('admin.clinical-notes.index') }}"
         class="admin-filter-form">

            <div class="admin-u-e6828601 admin-filter-layout">

                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Tìm kiếm
                    </label>

                    <input class="admin-u-19b29622"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Mã lượt khám, bệnh nhân, triệu chứng..."

                    >
@error('search')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Bác sĩ
                    </label>

                    <select class="admin-u-181f32ca"
                        name="doctor_id"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($doctors as $doctor)

                            <option
                                value="{{ $doctor->id }}"
                                @selected(
                                    (string) request('doctor_id')
                                    ===
                                    (string) $doctor->id
                                )
                            >
                                {{ $doctor->full_name }}
                            </option>

                        @endforeach

                    </select>
@error('doctor_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Trạng thái lượt khám
                    </label>

                    <select class="admin-u-181f32ca"
                        name="visit_status_id"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($visitStatuses as $status)

                            <option
                                value="{{ $status->id }}"
                                @selected(
                                    (string) request(
                                        'visit_status_id'
                                    )
                                    ===
                                    (string) $status->id
                                )
                            >
                                {{ \App\Models\AdminDisplay::label($status->name) }}
                            </option>

                        @endforeach

                    </select>
@error('visit_status_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Từ ngày
                    </label>

                    <input class="admin-u-19b29622"
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"

                    >
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Đến ngày
                    </label>

                    <input class="admin-u-19b29622"
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"

                    >
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror

                </div>

            </div>


            <div class="admin-u-0c24989a admin-filter-layout">





            </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route(
                        'admin.clinical-notes.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent


    <div class="card">

        @if ($clinicalNotes->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Lý do khám</th>
                        <th>Nhận định ban đầu</th>
                        <th>Ghi nhận lúc</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>


                    <tbody>

                    @foreach ($clinicalNotes as $note)

                        <tr>

                            @include('admin.layouts.partials.row-number', ['rows' => $clinicalNotes, 'index' => $loop->index])
                            <td>
                                <strong>
                                    {{ $note->medicalVisit?->visit_code ?: '—' }}
                                </strong>
                            </td>

                            <td>

                                {{ $note->medicalVisit?->patient?->full_name ?: '—' }}

                                @if ($note->medicalVisit?->patient)

                                    <div class="admin-u-3e8f8985">
                                        {{ $note->medicalVisit->patient->patient_code }}
                                    </div>

                                @endif

                            </td>

                            <td>
                                {{ $note->medicalVisit?->doctor?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $note->reason_for_visit ?: '—',
                                    60
                                ) }}
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $note->initial_assessment ?: '—',
                                    60
                                ) }}
                            </td>

                            <td>
                                {{ $note->recorded_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td>

                                <div class="admin-u-c9035c8e">

                                    <a
                                        href="{{ route(
                                            'admin.clinical-notes.show',
                                            $note
                                        ) }}"
                                        class="admin-btn admin-btn-detail btn btn-secondary"
                                    >Chi tiết</a>

                                    @if ($note->medicalVisit?->canRecordClinicalData())

                                        <a
                                            href="{{ route(
                                                'admin.clinical-notes.edit',
                                                $note
                                            ) }}"
                                            class="admin-btn btn btn-primary admin-btn-primary"
                                        >Sửa</a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div class="admin-u-6840f0a6">
                {{ $clinicalNotes->links() }}
            </div>

        @else

            <div class="admin-u-caa0d3af">
                Không tìm thấy nội dung khám phù hợp.
            </div>

        @endif

    </div>

@endsection
