@extends('admin.layouts.app')
@section('admin-page', 'prescriptions-edit')

@section('title', 'Sửa đơn thuốc')

@section('content')

    <div class="prescription-edit-page">

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Đơn thuốc</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.prescriptions.index') }}">
                Đơn thuốc
            </a>

            <span aria-hidden="true">/</span>

            <a href="{{ route(
        'admin.prescriptions.show',
        $prescription->id
    ) }}">
                {{ $prescription->prescription_code }}
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Sửa
            </strong>

        </nav>


        <div class="admin-info-header prescription-header">

            <div>


                <h1>
                    Sửa đơn thuốc
                </h1>

                <p>
                    Chỉnh sửa có kiểm soát.
                    Mọi thay đổi sẽ được ghi nhật ký hoạt động.
                </p>

            </div>

        </div>


        @if ($errors->any())

            <div class="prescription-alert">

                <strong>
                    Không thể cập nhật đơn thuốc.
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


        <form novalidate enctype="multipart/form-data" method="POST" action="{{ route(
        'admin.prescriptions.update',
        $prescription->id
    ) }}">

            @csrf
            @method('PATCH')


            {{-- Context --}}
            <div class="prescription-card">

                <div class="prescription-card-header">

                    <h2>
                        Thông tin nguồn
                    </h2>

                    <p>
                        Mã đơn, lượt khám và bác sĩ không thay đổi tại đây.
                    </p>

                </div>


                <div class="prescription-context-grid">

                    <div>

                        <span>
                            Mã đơn thuốc
                        </span>

                        <strong>
                            {{ $prescription->prescription_code }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Lượt khám
                        </span>

                        <strong>
                            {{ $visit?->visit_code ?? '—' }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Bác sĩ kê đơn
                        </span>

                        <strong>
                            {{ $doctor?->full_name ?? '—' }}
                        </strong>

                        @if ($doctor?->doctor_code)

                            <small>
                                {{ $doctor->doctor_code }}
                            </small>

                        @endif

                    </div>


                    <div>

                        <span>
                            Số thuốc
                        </span>

                        <strong>
                            {{ $prescription->items_count }}
                        </strong>

                    </div>

                </div>

            </div>


            {{-- Main form --}}
            <div class="prescription-card">

                <div class="prescription-card-header">

                    <h2>
                        Nội dung đơn thuốc
                    </h2>

                </div>


                <div class="prescription-form-body">

                    <div class="prescription-form-grid">

                        <div class="prescription-form-group">

                            <label for="prescribed_at">
                                Thời gian kê đơn
                            </label>

                            <input id="prescribed_at" type="datetime-local" name="prescribed_at"
                                class="prescription-control" value="{{ old(
        'prescribed_at',
        $prescription->prescribed_at
        ? $prescription->prescribed_at->format(
            'Y-m-d\TH:i'
        )
        : ''
    ) }}">

                            @error('prescribed_at')

                                <div class="prescription-field-error">
                                    {{ \App\Models\AdminDisplay::text($message) }}
                                </div>

                            @enderror

                        </div>


                        <div class="prescription-form-group">

                            <label for="status">
                                Trạng thái
                            </label>

                            <select id="status" name="status" class="prescription-control">

                                @foreach ($statuses as $status)

                                    <option value="{{ $status }}" @selected(
                                        old(
                                            'status',
                                            $prescription->status
                                        ) === $status
                                    )>
                                        {{ \App\Models\AdminDisplay::label($status) }}
                                    </option>

                                @endforeach

                            </select>

                            @error('status')

                                <div class="prescription-field-error">
                                    {{ \App\Models\AdminDisplay::text($message) }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    <div class="prescription-form-group">

                        <label for="general_instructions">
                            Hướng dẫn chung
                        </label>

                        <textarea id="general_instructions" name="general_instructions"
                            class="prescription-control prescription-textarea" rows="5">{{ old(
        'general_instructions',
        $prescription->general_instructions
    ) }}</textarea>

                        @error('general_instructions')

                            <div class="prescription-field-error">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>

                        @enderror

                    </div>


                    <div class="prescription-form-group">

                        <label for="doctor_notes">
                            Ghi chú bác sĩ
                        </label>

                        <textarea id="doctor_notes" name="doctor_notes" class="prescription-control prescription-textarea"
                            rows="5">{{ old(
        'doctor_notes',
        $prescription->doctor_notes
    ) }}</textarea>

                        @error('doctor_notes')

                            <div class="prescription-field-error">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>

                        @enderror

                    </div>


                    <div class="prescription-form-group">

                        <label for="prescription_pdf">
                            Tệp PDF đơn thuốc
                        </label>

                        <input id="prescription_pdf" type="file" name="prescription_pdf" accept=".pdf" class="prescription-control">
                        <p class="form-help">PDF tối đa 5 MB, được lưu riêng tư. Không chọn tệp mới để giữ tệp hiện tại.</p>
                        @if ($prescription->hasPdf())
                            <a class="admin-btn admin-btn-detail" href="{{ route('admin.prescriptions.document', $prescription->id) }}" target="_blank" rel="noopener noreferrer">Xem PDF hiện tại</a>
                        @endif

                        @error('prescription_pdf')

                            <div class="prescription-field-error">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>

                        @enderror

                    </div>

                </div>

            </div>


            <div class="prescription-warning">

                <strong>
                    Lưu ý
                </strong>

                <p>
                    Đây là dữ liệu y tế. Mọi thay đổi sẽ được lưu
                    vào nhật ký hoạt động với dữ liệu trước và sau cập nhật.
                </p>

            </div>


            <div class="prescription-actions">

                <a href="{{ route(
        'admin.prescriptions.show',
        $prescription->id
    ) }}" class="admin-btn admin-btn-secondary">Hủy</a>

                <label for="prescription-confirm-modal" class="admin-btn admin-btn-primary">
                    Cập nhật
                </label>

            </div>


            {{-- CSS modal --}}
            <input id="prescription-confirm-modal" type="checkbox" class="prescription-modal-toggle">


            <div class="prescription-modal">

                <label for="prescription-confirm-modal" class="prescription-modal-backdrop"></label>


                <div class="prescription-modal-dialog">

                    <div class="prescription-modal-icon">
                        !
                    </div>

                    <h3>
                        Xác nhận cập nhật đơn thuốc
                    </h3>

                    <p>
                        Bạn đang thay đổi dữ liệu y tế của đơn
                        <strong>
                            {{ $prescription->prescription_code }}
                        </strong>.
                    </p>


                    <div class="prescription-modal-actions">

                        <label for="prescription-confirm-modal" class="admin-btn admin-btn-secondary">Hủy</label>


                        <button type="submit" name="confirm_update" value="1"
                            class="admin-btn admin-btn-primary">
                            Cập nhật
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>




@endsection
