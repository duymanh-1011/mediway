@extends('admin.layouts.app')
@section('admin-page', 'service-orders-create')

@section('title', 'Tạo chỉ định dịch vụ - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Dịch vụ</span>
    <span aria-hidden="true">/</span>
    <span>Lượt khám</span>
    <span aria-hidden="true">/</span>
    <span>{{ $visit->visit_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Tạo chỉ định dịch vụ</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>
            <h1 class="page-title">
                Tạo chỉ định dịch vụ
            </h1>

            <p class="page-description">
                Lập phiếu chỉ định dịch vụ cho lượt khám
                {{ $visit->visit_code }}.
            </p>
        </div>

        <div class="header-actions">
            <a
                href="{{ route('admin.visits.show', $visit) }}"
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>
        </div>

    </div>


    @if ($errors->any())

        <div class="alert alert-danger">
            <strong>
                Không thể lưu phiếu chỉ định.
            </strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ \App\Models\AdminDisplay::text($error) }}
                    </li>
                @endforeach
            </ul>
        </div>

    @endif


    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin lượt khám
            </h3>

            <div class="detail-row">
                <span>Mã lượt khám</span>

                <strong>
                    {{ $visit->visit_code }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Bệnh nhân</span>

                <strong>
                    {{ $visit->patient?->patient_code ?? '—' }}

                    -

                    {{ $visit->patient?->full_name ?? '—' }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Bác sĩ chỉ định</span>

                <strong>
                    {{ $visit->doctor?->doctor_code ?? '—' }}

                    -

                    {{ $visit->doctor?->full_name ?? '—' }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Chuyên khoa</span>

                <strong>
                    {{ $visit->specialty?->name ?? '—' }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Phòng khám</span>

                <strong>
                    {{ $visit->examinationRoom?->room_code ?? '—' }}

                    -

                    {{ $visit->examinationRoom?->name ?? '—' }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Trạng thái</span>

                <strong>@include('admin.layouts.partials.status-badge', ['badgeCode' => $visit->status?->name ?? '—'])</strong>
            </div>

        </div>


        <div class="card">

            <h3 class="section-title">
                Nguyên tắc phiếu chỉ định
            </h3>

            <p class="info-text">
                Bác sĩ chỉ định được lấy tự động từ lượt khám.
            </p>

            <p class="info-text">
                Chỉ hiển thị các dịch vụ đang hoạt động.
            </p>

            <p class="info-text">
                Giá dịch vụ được lưu tại thời điểm tạo chỉ định
                để không bị thay đổi nếu bảng giá được cập nhật sau này.
            </p>

            <p class="info-text">
                Dịch vụ mới sẽ bắt đầu ở trạng thái
                <strong>Chờ xử lý</strong>.
            </p>

        </div>

    </div>


    <form novalidate
        method="POST"
        action="{{ route(
            'admin.visits.service-orders.store',
            $visit
        ) }}"
        class="card section-spacing"
    >

        @csrf


        <h3 class="section-title">
            Thông tin chỉ định
        </h3>


        <div class="form-grid">

            <div class="form-group">

                <label for="reason">
                    Lý do chỉ định
                </label>

                <textarea
                    id="reason"
                    name="reason"
                    class="form-control"
                    rows="4"
                    placeholder="Nhập lý do cần thực hiện dịch vụ..."
                >{{ old('reason') }}</textarea>
@error('reason')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="form-group">

                <label for="instructions">
                    Hướng dẫn thực hiện
                </label>

                <textarea
                    id="instructions"
                    name="instructions"
                    class="form-control"
                    rows="4"
                    placeholder="Nhập hướng dẫn hoặc lưu ý cho nhân viên thực hiện..."
                >{{ old('instructions') }}</textarea>
@error('instructions')<div class="field-error">{{ $message }}</div>@enderror

            </div>

        </div>


        <div class="services-header">

            <div>
                <h3 class="section-title service-title">
                    Chọn dịch vụ
                </h3>

                <p class="muted-text">
                    Có thể chọn nhiều dịch vụ trong cùng một phiếu.
                </p>
            </div>

            <div class="selected-summary">
                Đã chọn:
                <strong id="selected-count">
                    0
                </strong>
                dịch vụ
            </div>

        </div>


        @if ($services->isNotEmpty())

            <div class="service-list">

                @foreach ($services as $service)

                    @php
                        $checked = in_array(
                            $service->id,
                            old(
                                'medical_service_ids',
                                []
                            )
                        );
                    @endphp

                    <label class="service-item">

                        <input
                            type="checkbox"
                            name="medical_service_ids[]"
                            value="{{ $service->id }}"
                            class="service-checkbox"
                            {{ $checked ? 'checked' : '' }}
                        >

                        <div class="service-content">

                            <div class="service-top">

                                <div>

                                    <strong class="service-name">
                                        {{ $service->name }}
                                    </strong>

                                    <div class="service-code">
                                        {{ $service->service_code }}

                                        @if ($service->serviceGroups->isNotEmpty())
                                            •
                                            {{ $service->serviceGroups->pluck('name')->join(', ') }}
                                        @endif
                                    </div>

                                </div>


                                <strong class="service-price">
                                    {{ number_format(
                                        (float) $service->price,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                    đ
                                </strong>

                            </div>


                            <div class="service-meta">

                                @if ($service->unit)
                                    <span>
                                        Đơn vị:
                                        {{ $service->unit }}
                                    </span>
                                @endif

                                @if ($service->estimated_duration_minutes)
                                    <span>
                                        Thời gian dự kiến:
                                        {{ $service->estimated_duration_minutes }}
                                        phút
                                    </span>
                                @endif

                            </div>


                            @if ($service->preparation_instructions)

                                <div class="service-note">
                                    <strong>
                                        Chuẩn bị:
                                    </strong>

                                    {{ $service->preparation_instructions }}
                                </div>

                            @endif

                        </div>

                    </label>

                @endforeach

            </div>

        @else

            <div class="empty-state">
                Hiện chưa có dịch vụ y tế đang hoạt động.
            </div>

        @endif


        <div class="form-actions">

            <a
                href="{{ route('admin.visits.show', $visit) }}"
                class="admin-btn admin-btn-secondary"
            >Hủy</a>

            <button
                type="submit"
                class="admin-btn admin-btn-primary"
                {{ $services->isEmpty() ? 'disabled' : '' }}
            >
                Tạo phiếu chỉ định
            </button>

        </div>

    </form>

@endsection





@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes =
            document.querySelectorAll('.service-checkbox');

        const counter =
            document.getElementById('selected-count');

        function updateSelectedCount() {
            let count = 0;

            checkboxes.forEach(function (checkbox) {
                if (checkbox.checked) {
                    count++;
                }
            });

            counter.textContent = count;
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener(
                'change',
                updateSelectedCount
            );
        });

        updateSelectedCount();
    });
</script>

@endpush
