<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClinicalDocument;
use App\Models\PaymentTransaction;
use App\Models\Prescription;
use App\Models\ServiceResult;
use App\Support\PatientVisitServices;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicalRecordsController extends Controller
{
    public function results(Request $request)
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $visits = $patient->medicalVisits()
            ->with(['doctor:id,full_name', 'specialty:id,name', 'status:id,name,description',
                'diagnoses.disease:id,name,disease_code'])
            ->orderByDesc('checked_in_at')->orderByDesc('id')->paginate(10)->withQueryString();
        $recoveredEndTimes = $this->recordedCompletionTimes($visits->getCollection());

        return view('portal.clinical.results', compact('visits', 'patient', 'recoveredEndTimes'));
    }

    public function visit(Request $request, int $visit, PatientVisitServices $catalog)
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $record = $patient->medicalVisits()->with([
            'doctor:id,full_name', 'specialty:id,name', 'status:id,name,description', 'examinationRoom:id,name',
            'conclusion', 'diagnoses.disease:id,name,disease_code', 'prescription.doctor', 'prescription.items.medicine',
            'serviceOrders.items.medicalService.serviceGroups', 'serviceOrders.items.serviceStatus', 'serviceOrders.items.performer:id,full_name',
            'serviceOrders.items.result', 'payables.serviceOrderItem.medicalService',
            'payables.transactions' => fn ($query) => $query->where('transaction_result', 'SUCCESS')->with('collector:id,full_name'),
        ])->findOrFail($visit);

        $endedAt = $record->examination_ended_at ?? $this->recordedCompletionTimes(collect([$record]))->get($record->id);
        $tabs = ['tong-quan' => 'Tổng quan', 'dich-vu' => 'Dịch vụ', 'giay-to' => 'Giấy tờ', 'vien-phi' => 'Viện phí', 'don-thuoc' => 'Đơn thuốc'];
        $tab = $request->query('tab');
        $tab = is_string($tab) && isset($tabs[$tab]) ? $tab : 'tong-quan';
        $entries = $catalog->entries($record);
        $groups = $entries->groupBy('group');
        $groupFilter = $request->query('nhom');
        $groupFilter = is_string($groupFilter) && $groups->has($groupFilter) ? $groupFilter : '';
        $statusFilter = $request->query('trang-thai');
        $statusFilter = in_array($statusFilter, ['COMPLETED', 'PROCESSING', 'PENDING', 'CANCELLED'], true) ? $statusFilter : '';
        $sort = $request->query('sap-xep') === 'moi-nhat' ? 'moi-nhat' : 'theo-nhom';
        $opened = $request->query('dich-vu');
        $opened = is_string($opened) ? $opened : '';
        $visible = $entries->filter(fn ($entry) => (! $groupFilter || $entry['group'] === $groupFilter)
            && (! $statusFilter || $entry['status'] === $statusFilter));
        if ($sort === 'moi-nhat') {
            $visible = $visible->sortByDesc(fn ($entry) => ($entry['result']?->resulted_at ?? $entry['completed'] ?? $entry['started'] ?? $entry['ordered'])?->timestamp ?? 0);
        }

        $payments = $record->payables->flatMap(function ($payable) {
            return $payable->transactions->map(fn ($transaction) => [
                'payable' => $payable, 'transaction' => $transaction,
                'date' => $transaction->confirmed_at ?? $transaction->paid_at ?? $transaction->started_at ?? $transaction->created_at,
            ]);
        })->sortByDesc(fn ($payment) => $payment['date']?->timestamp ?? 0);

        return view('portal.clinical.visit', compact(
            'record', 'patient', 'endedAt', 'tabs', 'tab', 'entries', 'groups', 'visible', 'groupFilter', 'statusFilter', 'sort', 'opened', 'payments'
        ));
    }

    public function receipt(Request $request, int $visit, int $transaction)
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $receipt = PaymentTransaction::query()
            ->where('transaction_result', 'SUCCESS')
            ->whereHas('payable.medicalVisit', fn ($record) => $record->whereKey($visit)->where('patient_id', $patient->id))
            ->with(['collector:id,full_name', 'payable.medicalVisit', 'payable.serviceOrderItem.medicalService'])
            ->findOrFail($transaction);

        return view('portal.clinical.receipt', compact('receipt', 'patient'));
    }

    public function prescription(Request $request, int $prescription)
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $record = Prescription::query()->whereHas('medicalVisit', fn ($visit) => $visit->where('patient_id', $patient->id))
            ->with(['doctor', 'medicalVisit.diagnoses.disease', 'items.medicine'])
            ->findOrFail($prescription);

        return view('portal.clinical.prescription-document', compact('record', 'patient'));
    }

    private function recordedCompletionTimes(Collection $visits): Collection
    {
        $legacy = $visits->filter(fn ($visit) => ! $visit->examination_ended_at && $visit->status?->name === 'COMPLETED')->keyBy('id');
        if ($legacy->isEmpty()) {
            return collect();
        }

        return AuditLog::query()->where('entity_type', 'medical_visits')->where('action', 'STATUS_CHANGE')
            ->whereIn('entity_id', $legacy->keys())->orderByDesc('performed_at')->orderByDesc('id')->get()
            ->filter(fn ($log) => $log->performed_at && isset($log->old_values['visit_status_id'], $log->new_values['visit_status_id'])
                && (int) $log->new_values['visit_status_id'] === (int) $legacy[$log->entity_id]->visit_status_id
                && (int) ($log->old_values['visit_status_id'] ?? 0) !== (int) $legacy[$log->entity_id]->visit_status_id)
            ->unique('entity_id')->pluck('performed_at', 'entity_id');
    }

    // Lấy đơn thuốc của bệnh nhân qua mối liên hệ với lượt khám.
    public function prescriptions(Request $request)
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        // Nạp bác sĩ và chi tiết thuốc; sắp xếp đơn mới trước và chia 10 đơn mỗi trang.
        $visitId = $request->query('visit');
        if ($visitId !== null) {
            abort_unless(is_string($visitId) && ctype_digit($visitId) && (int) $visitId > 0, 404);
            $patient->medicalVisits()->whereKey((int) $visitId)->firstOrFail();
        }
        $prescriptions = Prescription::query()
            ->whereHas('medicalVisit', fn ($visit) => $visit
                ->where('patient_id', $patient->id))
            ->when($visitId !== null, fn ($query) => $query->where('medical_visit_id', (int) $visitId))
            ->with(['doctor', 'medicalVisit.doctor', 'items.medicine', 'payable.paymentStatus'])
            ->orderByDesc('prescribed_at')
            ->paginate(10)->withQueryString();

        return view('portal.clinical.prescriptions', compact('prescriptions', 'visitId'));
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
