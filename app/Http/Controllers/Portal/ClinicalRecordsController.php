<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClinicalDocument;
use App\Models\Prescription;
use App\Models\ServiceResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicalRecordsController extends Controller
{
    // Trang kết quả lấy dữ liệu và tài liệu của bệnh nhân đang đăng nhập.
    public function results(Request $request)
    {
        // Lấy hồ sơ từ tài khoản hiện tại và kiểm tra hồ sơ đang hoạt động.
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        // Chỉ lấy kết quả có nội dung hoặc tệp, thuộc lượt khám của bệnh nhân này.
        // Dữ liệu được hiển thị mà không kiểm tra trạng thái công bố.
        $results = ServiceResult::query()
            ->where(function ($query) {
                $query->whereNotNull('result_summary')
                    ->orWhereNotNull('result_conclusion')
                    ->orWhereNotNull('result_parameters')
                    ->orWhereNotNull('result_pdf_path')
                    ->orWhereNotNull('result_image_path');
            })
            ->whereHas('serviceOrderItem', function ($item) use ($patient) {
                $item->whereHas('serviceOrder.medicalVisit', fn ($visit) => $visit->where('patient_id', $patient->id));
            })
            ->with(['serviceOrderItem.medicalService', 'serviceOrderItem.serviceOrder.medicalVisit.doctor'])
            ->orderByDesc('resulted_at')
            ->paginate(10);

        // Lấy tài liệu cá nhân để hiển thị cùng trang kết quả khám.
        $documents = app(PatientDocumentController::class)->documentsFor($patient);

        return view('portal.clinical.results', compact('results', 'documents'));
    }

    // Lấy đơn thuốc của bệnh nhân qua mối liên hệ với lượt khám.
    public function prescriptions(Request $request)
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        // Nạp bác sĩ và chi tiết thuốc; sắp xếp đơn mới trước và chia 10 đơn mỗi trang.
        $prescriptions = Prescription::query()
            ->whereHas('medicalVisit', fn ($visit) => $visit
                ->where('patient_id', $patient->id))
            ->with(['doctor', 'medicalVisit.doctor', 'items.medicine'])
            ->orderByDesc('prescribed_at')
            ->paginate(10);

        return view('portal.clinical.prescriptions', compact('prescriptions'));
    }

    // Mở PDF hoặc ảnh kết quả sau khi kiểm tra kết quả thuộc bệnh nhân hiện tại.
    public function resultDocument(Request $request, int $result, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['result_pdf', 'result_image'], true), 404);

        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $clinicalResult = ServiceResult::query()
            ->whereKey($result)
            ->whereHas('serviceOrderItem', fn ($item) => $item
                ->whereHas('serviceOrder.medicalVisit', fn ($visit) => $visit->where('patient_id', $patient->id)))
            ->firstOrFail();

        // Xác định cột đường dẫn, loại tệp và thư mục theo loại tài liệu được yêu cầu.
        $pathColumn = $type === 'result_pdf' ? 'result_pdf_path' : 'result_image_path';
        $fileType = $type === 'result_pdf' ? 'pdf' : 'image';
        $directory = 'service-results/'.$clinicalResult->service_order_item_id;

        $this->auditDownload($request, 'ServiceResult', $clinicalResult->id, $type);
        return app(ClinicalDocument::class)->response($clinicalResult->{$pathColumn}, $directory, $fileType);
    }

    // Kiểm tra quyền sở hữu trước khi mở PDF đơn thuốc, kể cả khi truy cập trực tiếp URL.
    public function prescriptionDocument(Request $request, int $prescription): StreamedResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $record = Prescription::query()
            ->whereKey($prescription)
            ->whereHas('medicalVisit', fn ($visit) => $visit
                ->where('patient_id', $patient->id))
            ->firstOrFail();

        $this->auditDownload($request, 'Prescription', $record->id, 'prescription_pdf');
        return app(ClinicalDocument::class)->response($record->prescription_pdf_path, 'prescriptions/'.$record->id, 'pdf');
    }
    // Ghi nhật ký truy cập tệp kết quả hoặc đơn thuốc của bệnh nhân.
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
            'description' => 'Bệnh nhân đã truy cập tài liệu thuộc hồ sơ của mình.',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'performed_at' => now(),
            'created_at' => now(),
        ]);
    }
}
