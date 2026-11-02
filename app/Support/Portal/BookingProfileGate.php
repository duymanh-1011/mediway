<?php

namespace App\Support\Portal;

use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class BookingProfileGate
{
    private const SESSION_KEY = 'patient.booking.pending';

    public function forgetPending(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function missingFields(Patient $patient): array
    {
        $patient->loadMissing(['user', 'detail']);

        $missing = [];

        // Họ tên
        $name = trim((string) $patient->full_name);

        if (
            mb_strlen($name) < 2
            || preg_match('/\p{L}/u', $name) !== 1
        ) {
            $missing[] = 'họ và tên';
        }

        // Ngày sinh
        $birth = $patient->date_of_birth;

        if (
            ! $birth
            || $birth->isFuture()
            || $birth->lt(today()->subYears(120))
        ) {
            $missing[] = 'ngày sinh';
        }

        // Giới tính
        $gender = mb_strtoupper(
            trim((string) $patient->gender),
            'UTF-8'
        );

        if (! in_array($gender, [
            'NAM',
            'NỮ',
            'NU',
            'MALE',
            'FEMALE',
            'M',
            'F',
            'OTHER',
            'KHÁC',
            'KHAC',
        ], true)) {
            $missing[] = 'giới tính';
        }

        // Số điện thoại
        $phone = preg_replace(
            '/[\s.-]+/',
            '',
            (string) ($patient->user?->phone ?? '')
        );

        if (preg_match('/^0\d{9}$/', $phone) !== 1) {
            $missing[] = 'số điện thoại liên hệ';
        }

        // Địa chỉ
        $current = trim(
            (string) ($patient->detail?->current_address ?? '')
        );

        $permanent = trim(
            (string) ($patient->detail?->permanent_address ?? '')
        );

        $address = $current !== ''
            ? $current
            : $permanent;

        if (
            mb_strlen($address) < 5
            || preg_match('/\p{L}/u', $address) !== 1
        ) {
            $missing[] = 'địa chỉ liên hệ';
        }

        $citizenId = (string) ($patient->detail?->citizen_id_number ?? '');
        if (preg_match('/\A[0-9]{12}\z/', $citizenId) !== 1) {
            $missing[] = 'số CCCD';
        }

        $emergencyName = trim((string) ($patient->detail?->emergency_contact_name ?? ''));
        if (mb_strlen($emergencyName) < 2 || preg_match('/\p{L}/u', $emergencyName) !== 1) {
            $missing[] = 'họ tên liên hệ khẩn cấp';
        }

        $emergencyPhone = preg_replace('/[\s.-]+/', '', (string) ($patient->detail?->emergency_contact_phone ?? ''));
        if (preg_match('/^0\d{9}$/', $emergencyPhone) !== 1) {
            $missing[] = 'số điện thoại liên hệ khẩn cấp';
        }

        return $missing;
    }

    public function redirectToProfile(
        Patient $patient,
        int $slotId,
        array $draft = []
    ): RedirectResponse {
        $missing = $this->missingFields($patient);

        $editRoute = 'patient.profile.edit';

        // Nếu M09 chưa tích hợp, ở lại trang xác nhận.
        // KHÔNG chuyển về dashboard/tài khoản.
        if (! Route::has($editRoute)) {
            return redirect()
                ->route('patient.appointments.create', [
                    'slot' => $slotId,
                ])
                ->withInput($draft)
                ->withErrors([
                    'profile' =>
                    'Hồ sơ còn thiếu: '
                        . implode(', ', $missing)
                        . '. Trang cập nhật hồ sơ chưa được tích hợp vào nhánh này.',
                ]);
        }

        // Lưu tạm thông tin đặt lịch.
        session()->put(self::SESSION_KEY, [
            'user_id' => (int) Auth::id(),
            'slot_id' => $slotId,
            'created_at' => now()->timestamp,
            'draft' => [
                'reason_for_visit' => $draft['reason_for_visit'] ?? '',
                'patient_notes' => $draft['patient_notes'] ?? null,
            ],
        ]);

        return redirect()
            ->route($editRoute)
            ->with(
                'warning',
                'Để đặt lịch khám, vui lòng bổ sung: '
                    . implode(', ', $missing)
                    . '.'
            );
    }

    public function resumeAfterProfileSaved(
        Patient $patient
    ): ?RedirectResponse {
        $pending = session(self::SESSION_KEY);

        if (! is_array($pending)) {
            return null;
        }

        $sameUser =
            (int) ($pending['user_id'] ?? 0)
            === (int) Auth::id();

        $notExpired =
            (int) ($pending['created_at'] ?? 0)
            >= now()->subMinutes(30)->timestamp;

        $slotId = (int) ($pending['slot_id'] ?? 0);

        if (
            ! $sameUser
            || ! $notExpired
            || $slotId < 1
        ) {
            session()->forget(self::SESSION_KEY);
            return null;
        }

        // M09 đã lưu thành công.
        // Lấy dữ liệu mới nhất từ database.
        $patient->refresh();

        $patient->unsetRelation('user');
        $patient->unsetRelation('detail');

        // Vẫn còn thiếu thì chưa cho quay lại đặt lịch.
        if ($this->missingFields($patient) !== []) {
            return null;
        }

        session()->forget(self::SESSION_KEY);

        // Quay lại chính xác suất khám đã chọn.
        // Giữ lại lý do khám và ghi chú đã nhập.
        return redirect()
            ->route('patient.appointments.create', [
                'slot' => $slotId,
            ])
            ->withInput($pending['draft'] ?? [])
            ->with(
                'success',
                'Hồ sơ đã đầy đủ. Vui lòng xác nhận lịch khám.'
            );
    }
}
