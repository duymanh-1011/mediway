
@php
    $editing = isset($doctor);
    $detail = $editing ? $doctor->detail : null;

    $textFields = [
        ['name' => 'citizen_id_number', 'label' => 'Số CCCD', 'max' => 12],
        ['name' => 'citizen_id_issue_place', 'label' => 'Nơi cấp CCCD', 'max' => 150],
        ['name' => 'email', 'label' => 'Email', 'max' => 150],
        ['name' => 'hometown', 'label' => 'Quê quán', 'max' => 255],
        ['name' => 'permanent_address', 'label' => 'Địa chỉ thường trú', 'max' => 255],
        ['name' => 'current_address', 'label' => 'Địa chỉ hiện tại', 'max' => 255],
        ['name' => 'nationality', 'label' => 'Quốc tịch', 'max' => 80],
        ['name' => 'ethnicity', 'label' => 'Dân tộc', 'max' => 80],
        ['name' => 'education_level', 'label' => 'Trình độ học vấn', 'max' => 120],
        ['name' => 'professional_qualification', 'label' => 'Trình độ chuyên môn', 'max' => 150],
        ['name' => 'academic_title_degree', 'label' => 'Học hàm / Học vị', 'max' => 120],
        ['name' => 'professional_title', 'label' => 'Chức danh chuyên môn', 'max' => 120],
        ['name' => 'current_position', 'label' => 'Chức vụ hiện tại', 'max' => 120],
        ['name' => 'current_workplace', 'label' => 'Nơi làm việc', 'max' => 180],
        ['name' => 'practice_license_number', 'label' => 'Số giấy phép hành nghề', 'max' => 80],
        ['name' => 'practice_license_issue_place', 'label' => 'Nơi cấp giấy phép', 'max' => 180],
    ];

    $dateFields = [
        ['name' => 'citizen_id_issue_date', 'label' => 'Ngày cấp CCCD'],
        ['name' => 'work_start_date', 'label' => 'Ngày bắt đầu làm việc'],
        ['name' => 'practice_license_issue_date', 'label' => 'Ngày cấp giấy phép'],
        ['name' => 'practice_license_expiry_date', 'label' => 'Ngày hết hạn giấy phép'],
    ];

    $longFields = [
        ['name' => 'specialized_fields', 'label' => 'Lĩnh vực chuyên môn', 'max' => 5000],
        ['name' => 'professional_skills', 'label' => 'Kỹ năng chuyên môn', 'max' => 5000],
        ['name' => 'languages', 'label' => 'Ngoại ngữ', 'max' => 2000],
        ['name' => 'practice_scope', 'label' => 'Phạm vi hành nghề', 'max' => 5000],
        ['name' => 'professional_bio', 'label' => 'Giới thiệu chuyên môn', 'max' => 10000],
        ['name' => 'notes', 'label' => 'Ghi chú', 'max' => 5000],
    ];

    $documents = [
        ['input' => 'avatar', 'column' => 'avatar_path', 'label' => 'Ảnh đại diện', 'type' => 'avatar', 'image' => true, 'accept' => '.jpg,.jpeg,.png,.webp'],
        ['input' => 'citizen_id_front', 'column' => 'citizen_id_front_path', 'label' => 'CCCD mặt trước', 'type' => 'citizen-id-front', 'image' => true, 'accept' => '.jpg,.jpeg,.png,.webp'],
        ['input' => 'citizen_id_back', 'column' => 'citizen_id_back_path', 'label' => 'CCCD mặt sau', 'type' => 'citizen-id-back', 'image' => true, 'accept' => '.jpg,.jpeg,.png,.webp'],
        ['input' => 'practice_license_file', 'column' => 'practice_license_file_path', 'label' => 'Tệp giấy phép hành nghề', 'type' => 'practice-license', 'image' => false, 'accept' => '.pdf,.jpg,.jpeg,.png,.webp'],
        ['input' => 'profile_pdf', 'column' => 'profile_pdf_path', 'label' => 'Hồ sơ PDF', 'type' => 'profile-pdf', 'image' => false, 'accept' => '.pdf'],
    ];
@endphp

<div class="dr-section">
    <h2>Thông tin cơ bản</h2>
    <p>Thông tin hồ sơ bác sĩ. Dấu * là trường bắt buộc.</p>
</div>

<div class="dr-grid">
    @if($editing)
        <div class="dr-field">
            <label>Mã bác sĩ</label>
            <input class="admin-record-code" type="text" value="{{ $doctor->doctor_code }}" disabled>
        </div>

        <div class="dr-field">
            <label>Tài khoản liên kết</label>
            <input class="admin-record-code" type="text"
                   value="{{ $doctor->user?->business_code }} — {{ $doctor->user?->phone }}"
                   disabled>
        </div>
    @else
        <div class="dr-field dr-full">
            <label for="user_id">Tài khoản bác sĩ có sẵn</label>
            <select name="user_id" id="user_id">
                <option value="">-- Tạo tài khoản bác sĩ mới --</option>
                @foreach($availableUsers as $account)
                    <option value="{{ $account->id }}"
                            @selected((string)old('user_id') === (string)$account->id)>
                        {{ $account->business_code }} — {{ $account->phone }}
                        ({{ \App\Models\AdminDisplay::label($account->status) }})
                    </option>
                @endforeach
            </select>
            @error('user_id')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
            <small class="dr-help">Chọn tài khoản có sẵn hoặc để trống và nhập thông tin tài khoản mới bên dưới.</small>
        </div>

        <div class="dr-field dr-account-new">
            <label for="phone">Số điện thoại tài khoản mới</label>
            <input type="tel" id="phone" name="phone"
                   value="{{ old('phone') }}" maxlength="10"
                   placeholder="09xxxxxxxx">
            @error('phone')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
        </div>

        <div class="dr-field dr-account-new">
            <label for="password">Mật khẩu tài khoản mới</label>
            <input type="password" id="password" name="password"
                   autocomplete="new-password" placeholder="Tối thiểu 8 ký tự">
            @error('password')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
        </div>

        <div class="dr-field dr-account-new">
            <label for="password_confirmation">Xác nhận mật khẩu</label>
            <input type="password" id="password_confirmation"
                   name="password_confirmation" autocomplete="new-password">
@error('password_confirmation')<div class="field-error">{{ $message }}</div>@enderror
        </div>
    @endif

    <div class="dr-field">
        <label for="full_name">Họ và tên bác sĩ <span>*</span></label>
        <input type="text" id="full_name" name="full_name"
               value="{{ old('full_name', $editing ? $doctor->full_name : '') }}"
               maxlength="120" required>
        @error('full_name')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
    </div>

    <div class="dr-field">
        <label for="specialty_id">Chuyên khoa <span>*</span></label>
        <select id="specialty_id" name="specialty_id" required>
            <option value="">-- Chọn chuyên khoa --</option>
            @foreach($specialties as $specialty)
                <option value="{{ $specialty->id }}" @selected((string) old('specialty_id', $editing ? $doctor->specialty_id : '') === (string) $specialty->id)>{{ $specialty->name }}</option>
            @endforeach
        </select>
        @error('specialty_id')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="dr-field">
        <label for="date_of_birth">Ngày sinh</label>
        <input type="date" id="date_of_birth" name="date_of_birth"
               max="{{ now()->toDateString() }}"
               value="{{ old('date_of_birth', $editing ? $doctor->date_of_birth?->format('Y-m-d') : '') }}">
        @error('date_of_birth')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
    </div>

    <div class="dr-field">
        <label for="gender">Giới tính</label>
        <select id="gender" name="gender">
            <option value="">-- Chọn giới tính --</option>
            @foreach(['Nam','Nữ','Khác'] as $genderOption)
                <option value="{{ $genderOption }}"
                    @selected(old('gender', $editing ? $doctor->gender : '') === $genderOption)>
                    {{ $genderOption }}
                </option>
            @endforeach
        </select>
        @error('gender')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
    </div>

    <div class="dr-field">
        <label for="status">Trạng thái hồ sơ <span>*</span></label>
        <select id="status" name="status" required>
            <option value="ACTIVE"
                @selected(old('status', $editing ? $doctor->status : 'ACTIVE') === 'ACTIVE')>
                Hoạt động
            </option>
            <option value="INACTIVE"
                @selected(old('status', $editing ? $doctor->status : 'ACTIVE') === 'INACTIVE')>
                Không hoạt động
            </option>
        </select>
        @error('status')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
    </div>
</div>

<div class="dr-section dr-gap">
    <h2>Thông tin cá nhân và chuyên môn</h2>
</div>

<div class="dr-grid">
    @foreach($textFields as $field)
        <div class="dr-field">
            <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
            <input
                type="{{ $field['name'] === 'email' ? 'email' : 'text' }}"
                id="{{ $field['name'] }}"
                name="{{ $field['name'] }}"
                maxlength="{{ $field['max'] }}"
                value="{{ old($field['name'], $detail?->{$field['name']}) }}"
            >
            @error($field['name'])<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
        </div>
    @endforeach

    @foreach($dateFields as $field)
        <div class="dr-field">
            <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
            <input type="date"
                   id="{{ $field['name'] }}"
                   name="{{ $field['name'] }}"
                   value="{{ old($field['name'], $detail?->{$field['name']}?->format('Y-m-d')) }}">
            @error($field['name'])<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
        </div>
    @endforeach

    <div class="dr-field">
        <label for="years_of_experience">Số năm kinh nghiệm</label>
        <input type="number" id="years_of_experience"
               name="years_of_experience" min="0" max="100"
               value="{{ old('years_of_experience', $detail?->years_of_experience) }}">
        @error('years_of_experience')<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
    </div>

    @foreach($longFields as $field)
        <div class="dr-field dr-full">
            <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
            <textarea id="{{ $field['name'] }}"
                      name="{{ $field['name'] }}"
                      maxlength="{{ $field['max'] }}"
                      rows="3">{{ old($field['name'], $detail?->{$field['name']}) }}</textarea>
            @error($field['name'])<small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
        </div>
    @endforeach
</div>

<div class="dr-section dr-gap">
    <h2>Tài liệu đính kèm</h2>
    <p>Ảnh tối đa 2 MB, giấy phép và PDF tối đa 5 MB. Chọn tệp mới để thay thế tệp cũ.</p>
</div>

<div class="dr-documents">
    @foreach($documents as $document)
        @php
            $hasFile = $editing && $detail && filled($detail->{$document['column']});
            $fileUrl = $hasFile
                ? route('admin.doctors.document', [$doctor, $document['type']])
                : null;
        @endphp

        <div class="dr-document-card">
            <label for="{{ $document['input'] }}">{{ $document['label'] }}</label>

            <div class="dr-document-preview">
                @if($document['image'])
                    <img
                        id="dr-preview-{{ $document['input'] }}"
                        src="{{ $fileUrl ?: '' }}"
                        alt="{{ $document['label'] }}"
                        @if (! $hasFile) hidden @endif
                    >
                    <span
                        id="dr-empty-{{ $document['input'] }}"
                        @if ($hasFile) hidden @endif
                    >Chưa có ảnh</span>
                @else
                    <div class="dr-file-symbol">Tệp</div>
                    <span id="dr-file-label-{{ $document['input'] }}">
                        {{ $hasFile ? 'Đã có tài liệu' : 'Chưa có tài liệu' }}
                    </span>
                @endif
            </div>

            @if($hasFile)
                <a href="{{ $fileUrl }}" target="_blank"
                   rel="noopener noreferrer" class="dr-file-link">
                    Xem tài liệu hiện tại
                </a>
            @endif

            <input type="file"
                   id="{{ $document['input'] }}"
                   name="{{ $document['input'] }}"
                   accept="{{ $document['accept'] }}"
                   class="dr-upload"
                   data-kind="{{ $document['image'] ? 'image' : 'file' }}">

            @error($document['input'])
                <small class="dr-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror

            @if($editing)
                <small class="dr-help">Để trống để giữ tệp cũ.</small>
            @endif
        </div>
    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const accountSelect = document.getElementById('user_id');

    if (accountSelect) {
        const newAccountFields = document.querySelectorAll('.dr-account-new');

        function toggleAccountFields() {
            const show = accountSelect.value === '';

            newAccountFields.forEach(function (field) {
                field.hidden = !show;
                field.querySelectorAll('input').forEach(function (input) {
                    input.disabled = !show;
                });
            });
        }

        accountSelect.addEventListener('change', toggleAccountFields);
        toggleAccountFields();
    }

    document.querySelectorAll('.dr-upload').forEach(function (input) {
        const preview = document.getElementById('dr-preview-' + input.id);
        const empty = document.getElementById('dr-empty-' + input.id);
        const fileLabel = document.getElementById('dr-file-label-' + input.id);

        const originalSrc = preview ? preview.getAttribute('src') : '';
        const originalLabel = fileLabel ? fileLabel.textContent.trim() : '';
        let objectUrl = null;

        input.addEventListener('change', function () {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            const file = input.files && input.files[0];

            if (!file) {
                if (preview) {
                    preview.src = originalSrc || '';
                    preview.hidden = !originalSrc;
                }
                if (empty) {
                    empty.hidden = Boolean(originalSrc);
                }
                if (fileLabel) {
                    fileLabel.textContent = originalLabel;
                }
                return;
            }

            if (input.dataset.kind === 'image' && preview) {
                if (!file.type.startsWith('image/')) {
                    alert('Vui lòng chọn tệp ảnh hợp lệ.');
                    input.value = '';
                    return;
                }

                objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
                preview.hidden = false;

                if (empty) {
                    empty.hidden = true;
                }
            } else if (fileLabel) {
                fileLabel.textContent = 'Đã chọn: ' + file.name;
            }
        });
    });
});
</script>
