@extends('admin.layouts.app')
@section('admin-page', 'conclusions-index')

@section('title', 'Kết luận lượt khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Kết luận</strong>
@endsection

@section('content')

    <div class="admin-u-ed1667a9">

        <h1 class="page-title">
            Kết luận lượt khám
        </h1>

        <p class="page-description">
            Tra cứu kết luận chuyên môn,
            hướng xử trí và lịch tái khám.
        </p>

    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc kết luận lượt khám',
        'description' => 'Lọc theo từ khóa, bác sĩ, hướng xử trí, tái khám và khoảng thời gian.',
    ])
        <form
            method="GET"
            action="{{ route('admin.conclusions.index') }}"
         class="admin-filter-form">

            <div class="admin-u-c6c96973 admin-filter-layout">

                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Tìm kiếm
                    </label>

                    <input class="admin-u-19b29622"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Lượt khám, bệnh nhân, kết luận..."

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
                        Hướng xử trí
                    </label>

                    <select class="admin-u-181f32ca"
                        name="disposition"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($dispositions as $disposition)

                            <option
                                value="{{ $disposition }}"
                                @selected(
                                    request('disposition')
                                    ===
                                    $disposition
                                )
                            >
                                {{ \App\Models\AdminDisplay::label($disposition) }}
                            </option>

                        @endforeach

                    </select>
@error('disposition')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Tái khám
                    </label>

                    <select class="admin-u-181f32ca"
                        name="has_follow_up"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        <option
                            value="1"
                            @selected(
                                request('has_follow_up') === '1'
                            )
                        >
                            Có tái khám
                        </option>

                        <option
                            value="0"
                            @selected(
                                request('has_follow_up') === '0'
                            )
                        >
                            Không tái khám
                        </option>

                    </select>
@error('has_follow_up')<div class="field-error">{{ $message }}</div>@enderror

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


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.conclusions.index'), 'searchOnly' => false])
        </form>
    @endcomponent


    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách kết luận lượt khám'])


        @if ($conclusions->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Kết luận</th>
                        <th>Xử trí</th>
                        <th>Tái khám</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>


                    <tbody>

                    @foreach ($conclusions as $conclusion)

                        <tr>

                            @include('admin.layouts.partials.row-number', ['rows' => $conclusions, 'index' => $loop->index])
                            <td>
                                <strong>
                                    {{ $conclusion->medicalVisit?->visit_code ?: '—' }}
                                </strong>
                            </td>

                            <td>
                                {{ $conclusion->medicalVisit?->patient?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ $conclusion->doctor?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $conclusion->professional_conclusion,
                                    70
                                ) }}
                            </td>

                            <td>
                                {{ \App\Models\AdminDisplay::label($conclusion->disposition ?: '—') }}
                            </td>

                            <td>

                                @if ($conclusion->has_follow_up)

                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'YES'])

                                    @if ($conclusion->follow_up_date)
                                        <div class="admin-u-536f3c69">
                                            {{ $conclusion
                                                ->follow_up_date
                                                ->format('d/m/Y') }}
                                        </div>
                                    @endif

                                @else

                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'NO'])

                                @endif

                            </td>

                            <td>
                                {{ $conclusion->concluded_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td><div class="admin-actions admin-table-actions">

                                <div class="admin-u-c9035c8e">

                                    <a
                                        href="{{ route(
                                            'admin.conclusions.show',
                                            $conclusion
                                        ) }}"
                                        class="admin-btn admin-btn-detail"
                                    >Chi tiết</a>


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

                            </div></td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div class="admin-u-6840f0a6">
                {{ $conclusions->links() }}
            </div>

        @else

            <div class="admin-u-caa0d3af">
                Không tìm thấy kết luận phù hợp.
            </div>

        @endif

    </div>

@endsection
