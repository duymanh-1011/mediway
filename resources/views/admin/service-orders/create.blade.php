@extends('admin.layouts.app')

@section('title', 'Tạo chỉ định dịch vụ - Mediway Admin')

@section('breadcrumb')
    <div class="breadcrumb">
        Admin /
        Lượt khám /
        {{ $visit->visit_code }} /
        Tạo chỉ định dịch vụ
    </div>
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
                class="btn btn-secondary"
            >
                ← Quay lại lượt khám
            </a>
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
                        {{ $error }}
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

                <strong>
                    {{ $visit->status?->name ?? '—' }}
                </strong>
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
                <strong>PENDING</strong>.
            </p>

        </div>

    </div>


    <form
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

                                        @if ($service->serviceGroup)
                                            •
                                            {{ $service->serviceGroup->name }}
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
                class="btn btn-secondary"
            >
                Hủy
            </a>

            <button
                type="submit"
                class="btn btn-primary"
                {{ $services->isEmpty() ? 'disabled' : '' }}
            >
                Tạo phiếu chỉ định
            </button>

        </div>

    </form>

@endsection


@push('styles')

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 20px;
    }

    .header-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .section-spacing {
        margin-top: 20px;
    }

    .section-title {
        margin-top: 0;
        margin-bottom: 15px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .detail-row {
        display: grid;
        grid-template-columns:
            145px minmax(0, 1fr);
        gap: 15px;
        padding: 11px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-row span {
        color: #64748b;
    }

    .info-text {
        margin: 0 0 12px;
        line-height: 1.6;
    }

    .info-text:last-child {
        margin-bottom: 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 7px;
        font-weight: 700;
    }

    .form-control {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;

        font-family:
            "Times New Roman",
            Times,
            serif;

        resize: vertical;
    }

    .services-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
        margin-top: 28px;
        margin-bottom: 14px;
    }

    .service-title {
        margin-bottom: 4px;
    }

    .muted-text {
        margin: 0;
        color: #64748b;
    }

    .selected-summary {
        padding: 8px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        white-space: nowrap;
    }

    .service-list {
        display: grid;
        gap: 10px;
    }

    .service-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;

        padding: 15px;

        border:
            1px solid #e2e8f0;

        border-radius: 10px;

        cursor: pointer;

        transition:
            border-color 0.15s ease,
            background 0.15s ease;
    }

    .service-item:hover {
        border-color: #94a3b8;
        background: #f8fafc;
    }

    .service-checkbox {
        width: 18px;
        height: 18px;
        margin-top: 3px;
        flex-shrink: 0;
    }

    .service-content {
        flex: 1;
        min-width: 0;
    }

    .service-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
    }

    .service-name {
        display: block;
        font-size: 16px;
    }

    .service-code {
        margin-top: 4px;
        color: #64748b;
        font-size: 13px;
    }

    .service-price {
        white-space: nowrap;
    }

    .service-meta {
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        margin-top: 10px;
        color: #475569;
        font-size: 13px;
    }

    .service-note {
        margin-top: 10px;
        padding: 9px 11px;
        background: #f8fafc;
        border-radius: 7px;
        line-height: 1.5;
    }

    .empty-state {
        padding: 20px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border-radius: 8px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
    }

    .alert {
        margin-bottom: 20px;
        padding: 14px 16px;
        border-radius: 8px;
    }

    .alert-danger {
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    .alert-danger ul {
        margin-bottom: 0;
    }

    @media (max-width: 800px) {
        .page-header,
        .services-header,
        .service-top {
            flex-direction: column;
        }

        .detail-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@endpush


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