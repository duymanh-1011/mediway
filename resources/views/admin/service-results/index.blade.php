@extends('admin.layouts.app')
@section('admin-page', 'service-results-index')

@section('title', 'Kết quả dịch vụ')

@section('content')

    <div class="service-result-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Dịch vụ</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Kết quả dịch vụ
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header result-header">

            <div>


                <h1>
                    Kết quả dịch vụ
                </h1>

                <p>
                    Tra cứu kết quả của các dịch vụ đã thực hiện
                    trong quá trình khám bệnh.
                </p>

            </div>


        </div>


        {{-- Flash --}}
        @if (session('success'))

            <div class="result-alert result-alert-success">
                {{ session('success') }}
            </div>

        @endif


        @if (session('error'))

            <div class="result-alert result-alert-error">
                {{ session('error') }}
            </div>

        @endif


        {{-- Filter --}}
        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc kết quả dịch vụ',
            'description' => 'Lọc theo từ khóa, người xác nhận và khoảng thời gian.',
        ])
            <form data-filter-fields="4" method="GET" action="{{ route('admin.service-results.index') }}" class="admin-filter-form">

                    <div class="result-filter-grid admin-filter-layout">

                        {{-- Search --}}
                        <div class="result-form-group result-search-group admin-filter-field">

                            <label for="q">
                                Tìm kiếm
                            </label>

                            <input id="q" type="text" name="q" class="result-control" value="{{ request('q') }}"
                                placeholder="Mã phiếu, lượt khám, dịch vụ, kết quả...">
@error('q')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- Verifier --}}
                        <div class="result-form-group admin-filter-field">

                            <label for="verified_by_user_id">
                                Người xác nhận
                            </label>

                            <select id="verified_by_user_id" name="verified_by_user_id" class="result-control">

                                <option value="">
                                    Tất cả
                                </option>

                                @foreach ($verifiers as $verifier)

                                    <option value="{{ $verifier->id }}" @selected(
                                        (string) request('verified_by_user_id')
                                        ===
                                        (string) $verifier->id
                                    )>
                                        {{ $verifier->business_code }}
                                    </option>

                                @endforeach

                            </select>
@error('verified_by_user_id')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- From date --}}
                        <div class="result-form-group admin-filter-field">

                            <label for="date_from">
                                Từ ngày
                            </label>

                            <input id="date_from" type="date" name="date_from" class="result-control"
                                value="{{ request('date_from') }}">
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- To date --}}
                        <div class="result-form-group admin-filter-field">

                            <label for="date_to">
                                Đến ngày
                            </label>

                            <input id="date_to" type="date" name="date_to" class="result-control"
                                value="{{ request('date_to') }}">
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror

                        </div>

                    </div>


                    <div class="result-filter-actions admin-filter-layout">





                    </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.service-results.index'), 'searchOnly' => false])
            </form>
        @endcomponent


        {{-- Result list --}}
        <div class="result-card">

            <div class="result-card-header result-list-header">

                <div>

                    <h2 class="admin-list-title">Danh sách kết quả dịch vụ</h2>

                    <p>
                        Kết quả được sắp xếp theo thời gian trả kết quả mới nhất.
                    </p>

                </div>


                <div class="result-count">

                    Tổng cộng

                    <strong>
                        {{ $results->total() }}
                    </strong>

                    kết quả

                </div>

            </div>


            <div class="result-table-wrapper">

                <table class="result-table">

                    <thead>

                        <tr>

                            <th scope="col" class="admin-row-number">STT</th>
                            <th>
                                Phiếu chỉ định
                            </th>

                            <th>
                                Lượt khám
                            </th>

                            <th>
                                Dịch vụ
                            </th>

                            <th>
                                Tóm tắt kết quả
                            </th>

                            <th>
                                Kết luận
                            </th>

                            <th>
                                Người xác nhận
                            </th>

                            <th>
                                Thời gian trả
                            </th>

                            <th>
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($results as $result)

                                            @php
                                                $item = $result->serviceOrderItem;
                                                $order = $item?->serviceOrder;
                                                $visit = $order?->medicalVisit;
                                                $service = $item?->medicalService;
                                            @endphp

                                            <tr>

                                                {{-- Order --}}
                                                @include('admin.layouts.partials.row-number', ['rows' => $results, 'index' => $loop->index])
                                                <td>

                                                    @if ($order)

                                                        <div class="result-code">
                                                            @include('admin.layouts.partials.record-code', ['code' => $order->service_order_code])
                                                        </div>

                                                    @else

                                                        <span class="result-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Visit --}}
                                                <td>

                                                    @if ($visit)

                                                        <div class="result-visit-code">
                                                            @include('admin.layouts.partials.record-code', ['code' => $visit->visit_code])
                                                        </div>

                                                    @else

                                                        <span class="result-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Service --}}
                                                <td>

                                                    @if ($service)

                                                        <div class="result-service">

                                                            <strong>
                                                                {{ $service->name }}
                                                            </strong>

                                                            <span>
                                                                @include('admin.layouts.partials.record-code', ['code' => $service->service_code])
                                                            </span>

                                                        </div>

                                                    @else

                                                        <span class="result-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Summary --}}
                                                <td>

                                                    <div class="result-text result-text-summary">

                                                        {{ \Illuminate\Support\Str::limit(
                                $result->result_summary ?? '—',
                                80
                            ) }}

                                                    </div>

                                                </td>


                                                {{-- Conclusion --}}
                                                <td>

                                                    <div class="result-text">

                                                        {{ \Illuminate\Support\Str::limit(
                                $result->result_conclusion ?? '—',
                                80
                            ) }}

                                                    </div>

                                                </td>


                                                {{-- Verifier --}}
                                                <td>

                                                    @if ($result->verifier)

                                                        <span class="result-verifier">
                                                            @include('admin.layouts.partials.record-code', ['code' => $result->verifier->business_code])
                                                        </span>

                                                    @else

                                                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'UNVERIFIED'])

                                                    @endif

                                                </td>


                                                {{-- Resulted at --}}
                                                <td>

                                                    <div class="result-date">

                                                        @if ($result->resulted_at)

                                                                                    {{ $result->resulted_at->format(
                                                                'd/m/Y H:i'
                                                            ) }}

                                                        @else

                                                            —

                                                        @endif

                                                    </div>

                                                </td>


                                                {{-- Action --}}
                                                <td><div class="admin-actions admin-table-actions">

                                                    <div class="result-actions">

                                                        <a href="{{ route(
                                'admin.service-results.show',
                                $result->id
                            ) }}" class="admin-btn admin-btn-detail">Chi tiết</a>

                                                    </div>

                                                </div></td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="9" class="result-empty">

                                    <div class="result-empty-icon">
                                        !
                                    </div>

                                    <strong>
                                        Không tìm thấy kết quả dịch vụ
                                    </strong>

                                    <p>
                                        Không có dữ liệu phù hợp với
                                        điều kiện tìm kiếm hiện tại.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            @if ($results->hasPages())

                <div class="result-pagination">

                    <div class="result-pagination-info">

                        Trang

                        <strong>
                            {{ $results->currentPage() }}
                        </strong>

                        /

                        {{ $results->lastPage() }}

                    </div>


                    <div class="result-pagination-buttons">

                        @if ($results->onFirstPage())

                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>

                        @else

                            <a href="{{ $results->previousPageUrl() }}" class="admin-btn admin-btn-secondary">Trước</a>

                        @endif


                        @if ($results->hasMorePages())

                            <a href="{{ $results->nextPageUrl() }}" class="admin-btn admin-btn-secondary">Sau</a>

                        @else

                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>

                        @endif

                    </div>

                </div>

            @endif

        </div>


        {{-- Policy --}}
        <div class="result-policy-box">

            <div class="result-policy-icon">
                !
            </div>

            <div>

                <strong>
                    Chính sách dữ liệu
                </strong>

                <p>
                    Kết quả dịch vụ đã được xác nhận không được xóa vĩnh viễn.
                    Các chỉnh sửa nghiệp vụ phải được thực hiện có kiểm soát.
                </p>

            </div>

        </div>

    </div>




@endsection
