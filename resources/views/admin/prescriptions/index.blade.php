@extends('admin.layouts.app')
@section('admin-page', 'prescriptions-index')

@section('title', 'Đơn thuốc')

@section('content')

    <div class="prescription-page">

        {{-- =====================================================
        BREADCRUMB
        ====================================================== --}}

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Đơn thuốc</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Đơn thuốc
            </strong>

        </nav>


        {{-- =====================================================
        HEADER
        ====================================================== --}}

        <div class="admin-info-header prescription-header">

            <div>


                <h1>
                    Danh sách đơn thuốc
                </h1>

                <p>
                    Theo dõi đơn thuốc đã kê theo lượt khám,
                    bác sĩ, thời gian và trạng thái.
                </p>

            </div>


        </div>


        {{-- =====================================================
        FLASH MESSAGE
        ====================================================== --}}

        @if (session('success'))

            <div class="prescription-alert prescription-alert-success">
                {{ session('success') }}
            </div>

        @endif


        @if (session('error'))

            <div class="prescription-alert prescription-alert-error">
                {{ session('error') }}
            </div>

        @endif


        {{-- =====================================================
        FILTER
        ====================================================== --}}

        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc đơn thuốc',
            'description' => 'Lọc theo từ khóa, trạng thái và khoảng thời gian.',
        ])
            <form data-filter-fields="4" method="GET" action="{{ route('admin.prescriptions.index') }}" class="prescription-filter-form admin-filter-form">

                {{-- Search --}}
                <div class="prescription-form-group prescription-search-group admin-filter-field">

                    <label for="q">
                        Tìm kiếm
                    </label>

                    <input id="q" type="text" name="q" value="{{ request('q') }}" class="prescription-control"
                        placeholder="Mã đơn, lượt khám, mã hoặc tên bác sĩ...">
@error('q')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                {{-- Status --}}
                <div class="prescription-form-group admin-filter-field">

                    <label for="status">
                        Trạng thái
                    </label>

                    <select id="status" name="status" class="prescription-control">

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($statuses as $status)

                            <option value="{{ $status }}" @selected(
                                request('status') === $status
                            )>
                                {{ \App\Models\AdminDisplay::label($status) }}
                            </option>

                        @endforeach

                    </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                {{-- Date from --}}
                <div class="prescription-form-group admin-filter-field">

                    <label for="date_from">
                        Từ ngày
                    </label>

                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                        class="prescription-control">
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                {{-- Date to --}}
                <div class="prescription-form-group admin-filter-field">

                    <label for="date_to">
                        Đến ngày
                    </label>

                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                        class="prescription-control">
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                {{-- Buttons --}}
                <div class="prescription-filter-actions admin-filter-layout">





                </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.prescriptions.index'), 'searchOnly' => false])
            </form>
        @endcomponent


        {{-- =====================================================
        TABLE
        ====================================================== --}}

        <div class="prescription-card">

            <div class="prescription-table-header">

                <div>

                    <h2 class="admin-list-title">Danh sách đơn thuốc</h2>

                    <p>
                        Tổng cộng {{ $prescriptions->total() }} bản ghi
                    </p>

                </div>

            </div>


            <div class="prescription-table-wrapper">

                <table class="prescription-table">

                    <thead>

                        <tr>

                            <th scope="col" class="admin-row-number">STT</th>
                            <th>
                                Mã đơn
                            </th>

                            <th>
                                Lượt khám
                            </th>

                            <th>
                                Bác sĩ kê đơn
                            </th>

                            <th>
                                Thời gian kê
                            </th>

                            <th class="text-center">
                                Số thuốc
                            </th>

                            <th>
                                Hướng dẫn chung
                            </th>

                            <th>
                                Trạng thái
                            </th>

                            <th class="text-right">
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($prescriptions as $prescription)

                                            <tr>

                                                {{-- Prescription code --}}
                                                @include('admin.layouts.partials.row-number', ['rows' => $prescriptions, 'index' => $loop->index])
                                                <td>

                                                    <div class="prescription-code">
                                                        @include('admin.layouts.partials.record-code', ['code' => $prescription->prescription_code])
                                                    </div>

                                                </td>


                                                {{-- Visit --}}
                                                <td>

                                                    @if ($prescription->visit_code)

                                                        <span class="prescription-secondary-code">
                                                            @include('admin.layouts.partials.record-code', ['code' => $prescription->visit_code])
                                                        </span>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Doctor --}}
                                                <td>

                                                    @if (
                                                            $prescription->doctor_code
                                                            ||
                                                            $prescription->doctor_name
                                                        )

                                                        <div class="prescription-doctor">

                                                            <strong>
                                                                {{ $prescription->doctor_name ?? '—' }}
                                                            </strong>

                                                            @if ($prescription->doctor_code)

                                                                <span>
                                                                    @include('admin.layouts.partials.record-code', ['code' => $prescription->doctor_code])
                                                                </span>

                                                            @endif

                                                        </div>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Prescribed at --}}
                                                <td>

                                                    @if ($prescription->prescribed_at)

                                                        <div class="prescription-time">

                                                            <strong>
                                                                {{ $prescription->prescribed_at->format('d/m/Y') }}
                                                            </strong>

                                                            <span>
                                                                {{ $prescription->prescribed_at->format('H:i') }}
                                                            </span>

                                                        </div>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Item count --}}
                                                <td class="text-center">

                                                    <span class="prescription-count">
                                                        {{ $prescription->items_count }}
                                                    </span>

                                                </td>


                                                {{-- General instructions --}}
                                                <td>

                                                    @if ($prescription->general_instructions)

                                                                        <div class="prescription-instruction">
                                                                            {{
                                                        \Illuminate\Support\Str::limit(
                                                            $prescription->general_instructions,
                                                            70
                                                        )
                                                                                    }}
                                                                        </div>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Status --}}
                                                <td>



                                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $prescription->status ?? '—'])

                                                </td>


                                                {{-- Action --}}
                                                <td class="text-right"><div class="admin-actions admin-table-actions">

                                                    <a href="{{ route(
                                'admin.prescriptions.show',
                                $prescription->id
                            ) }}" class="admin-btn admin-btn-detail">Chi tiết</a>

                                                </div></td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="9" class="prescription-empty-state">

                                    <div class="prescription-empty-icon">
                                        Rx
                                    </div>

                                    <strong>
                                        Chưa có đơn thuốc phù hợp
                                    </strong>

                                    <p>
                                        Không tìm thấy dữ liệu theo
                                        điều kiện tìm kiếm hiện tại.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =====================================================
            PAGINATION
            ====================================================== --}}

            @if ($prescriptions->hasPages())

                <div class="prescription-pagination">

                    <div class="prescription-pagination-info">

                        Hiển thị

                        <strong>
                            {{ $prescriptions->firstItem() }}
                        </strong>

                        -

                        <strong>
                            {{ $prescriptions->lastItem() }}
                        </strong>

                        trên

                        <strong>
                            {{ $prescriptions->total() }}
                        </strong>

                        bản ghi

                    </div>


                    <div class="prescription-pagination-actions">

                        @if ($prescriptions->onFirstPage())

                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>

                        @else

                            <a href="{{ $prescriptions->previousPageUrl() }}" class="admin-btn admin-btn-secondary">Trước</a>

                        @endif


                        <span class="prescription-page-current">

                            Trang
                            {{ $prescriptions->currentPage() }}
                            /
                            {{ $prescriptions->lastPage() }}

                        </span>


                        @if ($prescriptions->hasMorePages())

                            <a href="{{ $prescriptions->nextPageUrl() }}" class="admin-btn admin-btn-secondary">Sau</a>

                        @else

                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>




@endsection
