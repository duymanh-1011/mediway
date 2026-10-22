@extends('admin.layouts.app')
@section('admin-page', 'service-statuses-index')

@section('title', 'Trạng thái dịch vụ')

@section('content')

    <div class="status-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Cấu hình trạng thái</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Trạng thái dịch vụ
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header status-header">

            <div>


                <h1>
                    Trạng thái dịch vụ
                </h1>

                <p>
                    Quản lý cấu hình luồng xử lý và trạng thái sử dụng
                    của các chỉ định dịch vụ.
                </p>

            </div>


        </div>


        {{-- Filter --}}
        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc trạng thái dịch vụ',
            'description' => 'Lọc theo từ khóa và trạng thái sử dụng.',
        ])
            <form method="GET" action="{{ route('admin.service-statuses.index') }}" class="admin-filter-form">

                    <div class="status-filter-grid admin-filter-layout">

                        {{-- Search --}}
                        <div class="status-form-group admin-filter-field">

                            <label for="q">
                                Tìm kiếm
                            </label>

                            <input id="q" type="text" name="q" class="status-control" value="{{ request('q') }}"
                                placeholder="Tên trạng thái hoặc mô tả...">
@error('q')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- Usage Status --}}
                        <div class="status-form-group admin-filter-field">

                            <label for="is_active">
                                Trạng thái sử dụng
                            </label>

                            <select id="is_active" name="is_active" class="status-control">

                                <option value="">
                                    Tất cả
                                </option>

                                <option value="1" @selected(request('is_active') === '1')>
                                    Đang sử dụng
                                </option>

                                <option value="0" @selected(request('is_active') === '0')>
                                    Ngừng sử dụng
                                </option>

                            </select>
@error('is_active')<div class="field-error">{{ $message }}</div>@enderror

                        </div>

                    </div>


                    <div class="status-filter-actions admin-filter-layout">





                    </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.service-statuses.index'), 'searchOnly' => false])
            </form>
        @endcomponent


        {{-- Table --}}
        <div class="status-card">

            <div class="status-card-header status-list-header">

                <div>

                    <h2>
                        Danh sách trạng thái dịch vụ
                    </h2>

                    <p>
                        Các trạng thái được sắp xếp theo thứ tự xử lý.
                    </p>

                </div>


                <div class="status-count">

                    Tổng cộng

                    <strong>
                        {{ $statuses->total() }}
                    </strong>

                    trạng thái

                </div>

            </div>


            <div class="status-table-wrapper">

                <table class="status-table">

                    <thead>

                        <tr>

                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Thứ tự xử lý</th>

                            <th>
                                Tên trạng thái
                            </th>

                            <th>
                                Mô tả
                            </th>

                            <th>
                                Cho phép chuyển tiếp
                            </th>

                            <th>
                                Trạng thái sử dụng
                            </th>

                            <th>
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($statuses as $status)

                                            <tr>

                                                {{-- Process Order --}}
                                                @include('admin.layouts.partials.row-number', ['rows' => $statuses, 'index' => $loop->index])
                                                <td>

                                                    <div class="status-order">
                                                        {{ $status->process_order }}
                                                    </div>

                                                </td>


                                                {{-- Name --}}
                                                <td>

                                                    <div class="status-name">
                                                        {{ \App\Models\AdminDisplay::label($status->name) }}
                                                    </div>

                                                </td>


                                                {{-- Description --}}
                                                <td>

                                                    <div class="status-description">
                                                        {{ $status->description ?? '—' }}
                                                    </div>

                                                </td>


                                                {{-- Allows Transition --}}
                                                <td>

                                                    @if ($status->allows_transition)

                                                        <span class="status-badge badge-yes">
                                                            Cho phép
                                                        </span>

                                                    @else

                                                        <span class="status-badge badge-no">
                                                            Không cho phép
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Usage Status --}}
                                                <td>

                                                    @if ($status->is_active)

                                                        <span class="status-badge badge-active">
                                                            Đang sử dụng
                                                        </span>

                                                    @else

                                                        <span class="status-badge badge-inactive">
                                                            Ngừng sử dụng
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Actions --}}
                                                <td>

                                                    <div class="status-actions">

                                                        <a href="{{ route(
                                'admin.service-statuses.show',
                                $status->id
                            ) }}" class="admin-btn admin-btn-detail status-action-btn action-view">Chi tiết</a>

                                                        <a href="{{ route(
                                'admin.service-statuses.edit',
                                $status->id
                            ) }}" class="admin-btn admin-btn-secondary status-action-btn action-edit">Sửa</a>

                                                    </div>

                                                </td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="status-empty">

                                    <div class="status-empty-icon">
                                        !
                                    </div>

                                    <strong>
                                        Không tìm thấy trạng thái
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
            @if ($statuses->hasPages())

                <div class="status-pagination">

                    <div class="status-pagination-info">

                        Trang

                        <strong>
                            {{ $statuses->currentPage() }}
                        </strong>

                        /

                        {{ $statuses->lastPage() }}

                    </div>


                    <div class="status-pagination-buttons">

                        @if ($statuses->onFirstPage())

                            <span class="status-page-btn disabled">
                                ← Trước
                            </span>

                        @else

                            <a href="{{ $statuses->previousPageUrl() }}" class="status-page-btn">
                                ← Trước
                            </a>

                        @endif


                        @if ($statuses->hasMorePages())

                            <a href="{{ $statuses->nextPageUrl() }}" class="status-page-btn">
                                Sau →
                            </a>

                        @else

                            <span class="status-page-btn disabled">
                                Sau →
                            </span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>




@endsection
