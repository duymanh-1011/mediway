<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientDocumentController extends Controller
{
    // Các loại tệp cho phép xem và cột chứa đường dẫn tương ứng trong hồ sơ.
    private const DOCUMENTS = [
        'avatar' => ['column' => 'avatar_path', 'label' => 'Ảnh đại diện', 'type' => 'image'],
        'citizen-id-front' => ['column' => 'citizen_id_front_path', 'label' => 'Mặt trước giấy tờ tùy thân', 'type' => 'image'],
        'citizen-id-back' => ['column' => 'citizen_id_back_path', 'label' => 'Mặt sau giấy tờ tùy thân', 'type' => 'image'],
        'profile-pdf' => ['column' => 'profile_pdf_path', 'label' => 'Hồ sơ bệnh nhân PDF', 'type' => 'pdf'],
    ];

    // Lập danh sách tài liệu có tệp hợp lệ để đưa lên trang kết quả.
    public function documentsFor(\App\Models\Patient $patient)
    {
        $patient->loadMissing('detail');

        return collect(self::DOCUMENTS)
            ->map(function (array $definition, string $key) use ($patient): array {
                $path = $patient->detail?->{$definition['column']};

                return [
                    'key' => $key,
                    'label' => $definition['label'],
                    'available' => is_string($path) && $this->isSafePatientDocument($path, $patient->id, $definition['type']),
                ];
            })->filter(fn (array $document) => $document['available'])->values();
    }

    // Kiểm tra bệnh nhân và tệp trước khi trả nội dung tài liệu cho trình duyệt.
    public function show(Request $request, string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::DOCUMENTS), 404);
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);
        $patient->load('detail');

        $definition = self::DOCUMENTS[$type];
        $path = $patient->detail?->{$definition['column']};
        abort_unless(is_string($path) && $this->isSafePatientDocument($path, $patient->id, $definition['type']), 404);

        $disk = Storage::disk('local');
        // Hạn chế lưu tệp riêng tư trong bộ nhớ đệm của trình duyệt.
        $headers = [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ];
        $filename = $type === 'profile-pdf' ? 'ho-so-benh-nhan.pdf' : basename($path);

        $this->auditDownload($request, 'PatientDetail', (int) $patient->detail->id, $type);

        return $disk->response($path, $filename, $headers, 'inline');
    }

    // Chỉ chấp nhận tệp đúng thư mục bệnh nhân, có tồn tại và đúng định dạng.
    private function isSafePatientDocument(string $path, int $patientId, string $type): bool
    {
        $prefix = 'patient-documents/'.$patientId.'/';
        if (! str_starts_with($path, $prefix) || str_contains($path, '..') || str_contains($path, '\\')) {
            return false;
        }

        $disk = Storage::disk('local');
        // So sánh đường dẫn thực để ngăn tệp trỏ ra ngoài nơi lưu trữ.
        $root = realpath($disk->path(''));
        $resolved = realpath($disk->path($path));
        $expected = $root ? $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path) : null;
        if (! $root || ! $resolved || $resolved !== $expected || ! is_file($resolved) || ! is_readable($resolved)
            || ! $disk->exists($path) || $disk->size($path) < 1) {
            return false;
        }

        $mime = $disk->mimeType($path);

        return $type === 'pdf'
            ? $mime === 'application/pdf'
            : in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    // Ghi nhật ký khi bệnh nhân mở tài liệu cá nhân.
    private function auditDownload(Request $request, string $entity, int $id, string $type): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $request->user()->id,
            'role_name_snapshot' => 'Patient',
            'action' => 'PATIENT_DOCUMENT_DOWNLOADED',
            'entity_type' => $entity,
            'entity_id' => $id,
            'entity_business_code' => null,
            'old_values' => null,
            'new_values' => json_encode(['document_type' => $type], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'description' => 'Bệnh nhân đã truy cập tài liệu riêng tư thuộc hồ sơ của mình.',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'performed_at' => now(),
            'created_at' => now(),
        ]);
    }
}