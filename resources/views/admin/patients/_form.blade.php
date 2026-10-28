
@php
    $editing = isset($patient);
    $detail = $editing ? $patient->detail : null;
    $selectedUserId = old('user_id', $editing ? $patient->user_id : '');

    $birthDate = $editing && $patient->date_of_birth
        ? $patient->date_of_birth->format('Y-m-d')
        : '';

    $documents = [
        [
            'input' => 'avatar',
            'column' => 'avatar_path',
            'label' => 'Ảnh đại diện',
            'type' => 'avatar',
            'image' => true,
        ],
        [
            'input' => 'citizen_id_front',
            'column' => 'citizen_id_front_path',
            'label' => 'CCCD mặt trước',
            'type' => 'citizen-id-front',
            'image' => true,
        ],
        [
            'input' => 'citizen_id_back',
            'column' => 'citizen_id_back_path',
            'label' => 'CCCD mặt sau',
            'type' => 'citizen-id-back',
            'image' => true,
        ],
        [
            'input' => 'profile_pdf',
            'column' => 'profile_pdf_path',
            'label' => 'Hồ sơ PDF',
            'type' => 'profile-pdf',
            'image' => false,
        ],
    ];
@endphp

<div class="pf-section">
    <h2>Thông tin bệnh nhân</h2>
    <p>Các trường có dấu * là bắt buộc.</p>
</div>

<div class="pf-grid">
    @if($editing)
        <div class="pf-field">
            <label>Mã bệnh nhân</label>
            <input class="admin-record-code" type="text" value="{{ $patient->patient_code }}" disabled>
        </div>
    @endif

    <div class="pf-field">
        <label for="full_name">Họ và tên <span>*</span></label>
        <input
            type="text"
            id="full_name"
            name="full_name"
            value="{{ old('full_name', $editing ? $patient->full_name : '') }}"
            maxlength="120"
            required
        >
        @error('full_name')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="date_of_birth">Ngày sinh</label>
        <input
            type="date"
            id="date_of_birth"
            name="date_of_birth"
            value="{{ old('date_of_birth', $birthDate) }}"
            max="{{ now()->toDateString() }}"
        >
        @error('date_of_birth')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="gender">Giới tính</label>
        <select id="gender" name="gender">
            <option value="">-- Chọn giới tính --</option>

            @foreach(['Nam', 'Nữ', 'Khác'] as $gender)
                <option
                    value="{{ $gender }}"
                    @selected(old('gender', $editing ? $patient->gender : '') === $gender)
                >
                    {{ $gender }}
                </option>
            @endforeach
        </select>

        @error('gender')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field pf-full">
        <label for="user_id">Liên kết tài khoản Patient</label>

        <select id="user_id" name="user_id">
            <option value="">-- Chưa liên kết tài khoản --</option>

            @foreach($availableUsers as $account)
                @if(!$editing || $account->business_code === $patient->patient_code)
                    <option
                        value="{{ $account->id }}"
                        @selected((string) $selectedUserId === (string) $account->id)
                    >
                        {{ $account->business_code }} - {{ $account->phone }}
                    </option>
                @endif
            @endforeach
        </select>

        @error('user_id')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror

        <small class="pf-help">
            Chỉ chọn tài khoản Patient chưa có hồ sơ.
            Khi chỉnh sửa, tài khoản phải có mã trùng mã bệnh nhân.
        </small>
    </div>
</div>

<div class="pf-section pf-section-gap">
    <h2>Thông tin giấy tờ và liên hệ</h2>
    <p>Thông tin được lưu tại bảng patient_details.</p>
</div>

<div class="pf-grid">
    <div class="pf-field">
        <label for="citizen_id_number">Số CCCD</label>
        <input
            type="text"
            id="citizen_id_number"
            name="citizen_id_number"
            maxlength="12"
            inputmode="numeric"
            value="{{ old('citizen_id_number', $detail?->citizen_id_number) }}"
            placeholder="12 chữ số"
        >
        @error('citizen_id_number')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            value="{{ old('email', $detail?->email) }}"
        >
        @error('email')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="citizen_id_issue_date">Ngày cấp CCCD</label>
        <input
            type="date"
            id="citizen_id_issue_date"
            name="citizen_id_issue_date"
            max="{{ now()->toDateString() }}"
            value="{{ old('citizen_id_issue_date', $detail?->citizen_id_issue_date?->format('Y-m-d')) }}"
        >
        @error('citizen_id_issue_date')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="citizen_id_issue_place">Nơi cấp CCCD</label>
        <input
            type="text"
            id="citizen_id_issue_place"
            name="citizen_id_issue_place"
            maxlength="150"
            value="{{ old('citizen_id_issue_place', $detail?->citizen_id_issue_place) }}"
        >
        @error('citizen_id_issue_place')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="nationality">Quốc tịch</label>
        <input
            type="text"
            id="nationality"
            name="nationality"
            maxlength="80"
            value="{{ old('nationality', $detail?->nationality ?? 'Việt Nam') }}"
        >
        @error('nationality')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="ethnicity">Dân tộc</label>
        <input
            type="text"
            id="ethnicity"
            name="ethnicity"
            maxlength="80"
            value="{{ old('ethnicity', $detail?->ethnicity) }}"
        >
        @error('ethnicity')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="occupation">Nghề nghiệp</label>
        <input
            type="text"
            id="occupation"
            name="occupation"
            maxlength="120"
            value="{{ old('occupation', $detail?->occupation) }}"
        >
        @error('occupation')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="blood_type">Nhóm máu</label>
        <select id="blood_type" name="blood_type">
            <option value="">-- Chưa xác định --</option>

            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $blood)
                <option
                    value="{{ $blood }}"
                    @selected(old('blood_type', $detail?->blood_type) === $blood)
                >
                    {{ $blood }}
                </option>
            @endforeach
        </select>

        @error('blood_type')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field pf-full">
        <label for="hometown">Quê quán</label>
        <input
            type="text"
            id="hometown"
            name="hometown"
            maxlength="255"
            value="{{ old('hometown', $detail?->hometown) }}"
        >
        @error('hometown')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field pf-full">
        <label for="permanent_address">Địa chỉ thường trú</label>
        <input
            type="text"
            id="permanent_address"
            name="permanent_address"
            maxlength="255"
            value="{{ old('permanent_address', $detail?->permanent_address) }}"
        >
        @error('permanent_address')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field pf-full">
        <label for="current_address">Địa chỉ hiện tại</label>
        <input
            type="text"
            id="current_address"
            name="current_address"
            maxlength="255"
            value="{{ old('current_address', $detail?->current_address) }}"
        >
        @error('current_address')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>
</div>

<div class="pf-section pf-section-gap">
    <h2>Liên hệ khẩn cấp</h2>
    <p>Thông tin người thân hoặc người có thể liên hệ khi cần.</p>
</div>

<div class="pf-grid">
    <div class="pf-field">
        <label for="emergency_contact_name">Tên người liên hệ</label>
        <input
            type="text"
            id="emergency_contact_name"
            name="emergency_contact_name"
            maxlength="120"
            value="{{ old('emergency_contact_name', $detail?->emergency_contact_name) }}"
        >
        @error('emergency_contact_name')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field">
        <label for="emergency_contact_phone">Số điện thoại</label>
        <input
            type="text"
            id="emergency_contact_phone"
            name="emergency_contact_phone"
            maxlength="10"
            inputmode="numeric"
            value="{{ old('emergency_contact_phone', $detail?->emergency_contact_phone) }}"
        >
        @error('emergency_contact_phone')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field pf-full">
        <label for="emergency_contact_relationship">Mối quan hệ</label>
        <input
            type="text"
            id="emergency_contact_relationship"
            name="emergency_contact_relationship"
            maxlength="80"
            value="{{ old('emergency_contact_relationship', $detail?->emergency_contact_relationship) }}"
        >
        @error('emergency_contact_relationship')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="pf-field pf-full">
        <label for="notes">Ghi chú</label>
        <textarea
            id="notes"
            name="notes"
            rows="4"
            maxlength="5000"
            placeholder="Thông tin bổ sung nếu có..."
        >{{ old('notes', $detail?->notes) }}</textarea>

        @error('notes')
            <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>
</div>

{{-- TÀI LIỆU ĐÍNH KÈM --}}
<div class="pf-section pf-section-gap">
    <h2>Tài liệu đính kèm</h2>
    <p>
        Xem ảnh hiện tại và chọn ảnh mới nếu muốn thay thế.
        Mỗi ảnh tối đa 2 MB; hồ sơ PDF tối đa 5 MB.
    </p>
</div>

<div class="pf-grid pf-document-grid">
    @foreach($documents as $document)
        @php
            $hasCurrentFile = $editing
                && $detail
                && !empty($detail->{$document['column']});

            $documentUrl = $hasCurrentFile
                ? route('admin.patients.document', [$patient, $document['type']])
                : null;

            $accept = $document['image']
                ? '.jpg,.jpeg,.png,.webp'
                : '.pdf';
        @endphp

        <div class="pf-field pf-document-card">
            <label for="{{ $document['input'] }}">
                {{ $document['label'] }}
            </label>

            {{-- HIỂN THỊ TÀI LIỆU HIỆN TẠI --}}
            @if($document['image'])
                <div class="pf-preview-area">
                    <img
                        id="preview-{{ $document['input'] }}"
                        src="{{ $documentUrl ?? '' }}"
                        alt="{{ $document['label'] }}"
                        class="pf-current-image"
                        @if (! $hasCurrentFile) hidden @endif
                    >

                    <div
                        id="empty-{{ $document['input'] }}"
                        class="pf-image-empty"
                        @if ($hasCurrentFile) hidden @endif
                    >
                        <span>Chưa có ảnh</span>
                    </div>
                </div>
            @else
                <div class="pf-preview-area pf-pdf-preview">
                    <div class="pf-pdf-symbol">PDF</div>
                    <div
                        id="pdf-label-{{ $document['input'] }}"
                        class="pf-pdf-name"
                    >
                        {{ $hasCurrentFile ? 'Đã có hồ sơ PDF' : 'Chưa có hồ sơ PDF' }}
                    </div>
                </div>
            @endif

            @if($hasCurrentFile)
                <a
                    href="{{ $documentUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pf-current-link"
                >
                    Xem {{ $document['label'] }} hiện tại
                </a>
            @endif

            {{-- CHỌN FILE MỚI --}}
            <input
                type="file"
                id="{{ $document['input'] }}"
                name="{{ $document['input'] }}"
                accept="{{ $accept }}"
                class="pf-file-input"
                data-preview="{{ $document['image'] ? 'image' : 'pdf' }}"
            >

            @error($document['input'])
                <small class="pf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror

            <small class="pf-help">
                {{ $document['image'] ? 'JPG, PNG, WEBP – tối đa 2 MB.' : 'PDF – tối đa 5 MB.' }}
                @if($editing)
                    Không chọn tệp mới thì giữ nguyên tệp hiện tại.
                @endif
            </small>
        </div>
    @endforeach
</div>

{{-- CSS RIÊNG CHO KHUNG XEM TRƯỚC --}}


{{-- JAVASCRIPT XEM TRƯỚC ẢNH MỚI --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('.pf-file-input[data-preview]');

    fileInputs.forEach(function (input) {
        const originalImage = input.dataset.preview === 'image'
            ? document.getElementById('preview-' + input.id)
            : null;

        const emptyBox = document.getElementById('empty-' + input.id);
        const pdfLabel = document.getElementById('pdf-label-' + input.id);

        const originalSrc = originalImage
            ? originalImage.getAttribute('src')
            : null;

        let objectUrl = null;

        input.addEventListener('change', function () {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            const file = input.files && input.files.length
                ? input.files[0]
                : null;

            if (!file) {
                if (originalImage) {
                    originalImage.src = originalSrc || '';
                    originalImage.hidden = !originalSrc;
                }

                if (emptyBox) {
                    emptyBox.hidden = Boolean(originalSrc);
                }

                if (pdfLabel) {
                    pdfLabel.textContent = pdfLabel.dataset.original
                        || pdfLabel.textContent;
                }

                return;
            }

            if (input.dataset.preview === 'image') {
                if (!file.type.startsWith('image/')) {
                    input.value = '';
                    alert('Vui lòng chọn tệp ảnh hợp lệ.');
                    return;
                }

                objectUrl = URL.createObjectURL(file);

                if (originalImage) {
                    originalImage.src = objectUrl;
                    originalImage.hidden = false;
                }

                if (emptyBox) {
                    emptyBox.hidden = true;
                }
            } else if (pdfLabel) {
                pdfLabel.textContent = 'Đã chọn: ' + file.name;
            }
        });

        if (pdfLabel) {
            pdfLabel.dataset.original = pdfLabel.textContent.trim();
        }
    });
});
</script>
