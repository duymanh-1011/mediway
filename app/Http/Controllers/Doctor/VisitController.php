<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalVisit;
use App\Models\Disease;
use App\Models\VisitStatus;
use App\Models\VisitConclusion;
use App\Models\ServiceResult;
use App\Models\ClinicalDocument;
use App\Models\AuditLog;
use App\Services\DoctorVisitReadiness;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $doctor = $request->user()->doctor;
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', 'integer', 'exists:visit_statuses,id'],
        ], ['date_to.after_or_equal' => 'Đến ngày phải từ hoặc sau ngày bắt đầu.']);
        if ($validator->fails()) {
            // Chỉ giữ các giá trị văn bản hợp lệ để hiển thị lại biểu mẫu an toàn.
            $input = collect($request->only(['q', 'date_from', 'date_to', 'status']))
                ->filter(fn ($value) => is_string($value) || is_int($value))->all();
            return redirect()->route('doctor.visits.index')->withErrors($validator)->withInput($input);
        }
        $filters = $validator->validated();
        $term = trim($filters['q'] ?? '');
        $filters['q'] = $term;

        // Mọi bác sĩ đã đăng nhập được xem danh sách lượt khám.
        $assigned = MedicalVisit::query();
        $hasVisits = (clone $assigned)->exists();
        $visits = $assigned
            // Dùng cùng dữ liệu tiến độ với Admin, nạp theo danh sách thay vì từng dòng.
            ->withServiceProgress()
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('visit_code', 'like', '%'.$term.'%')
                ->orWhereHas('patient', fn ($patient) => $patient->where('full_name', 'like', '%'.$term.'%')
                    ->orWhere('patient_code', 'like', '%'.$term.'%'))))
            ->when(! empty($filters['date_from']), fn ($query) => $query->where('checked_in_at', '>=', $filters['date_from'].' 00:00:00'))
            ->when(! empty($filters['date_to']), fn ($query) => $query->where('checked_in_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay()))
            ->when(! empty($filters['status']), fn ($query) => $query->where('visit_status_id', $filters['status']))
            ->with(['patient', 'specialty', 'examinationRoom', 'status'])
            ->orderByDesc('checked_in_at')->orderByDesc('id')->paginate(10)->appends($filters);
        // Giữ trạng thái ngừng hoạt động nếu bác sĩ vẫn có hồ sơ lịch sử sử dụng nó.
        $statuses = VisitStatus::where(fn ($query) => $query->where('is_active', true)
            ->orWhereHas('medicalVisits'))
            ->orderBy('process_order')->orderBy('id')->get();
        return view('doctor.visits.index', compact('doctor', 'visits', 'filters', 'statuses', 'hasVisits'));
    }

    public function show(Request $request, int $visit, DoctorVisitReadiness $readiness): View
    {
        $doctor = $request->user()->doctor;
        // Chỉ tài khoản bác sĩ hợp lệ đã qua middleware mới đến được đây.
        $record = MedicalVisit::query()
            ->withServiceProgress()
            ->with(['doctor', 'patient', 'specialty', 'examinationRoom', 'status', 'appointment', 'clinicalNote',
                'diagnoses.disease', 'conclusion', 'prescription.items.medicine',
                'serviceOrders.items.medicalService', 'serviceOrders.items.serviceStatus', 'serviceOrders.items.result'])
            ->findOrFail($visit);
        $check = $readiness->check($record);
        $conclusionPrerequisites = $readiness->conclusionPrerequisites($record);
        $canEdit = $record->canRecordClinicalData();
        $prescriptionLocked = $record->prescription !== null;
        $conclusionLocked = $record->conclusion !== null;
        $canComplete = $conclusionLocked && $check['ready'] && $record->status?->allows_transition
            && in_array($record->status?->name, ['IN_EXAMINATION', 'RETURNING'], true);
        $canEditBeforePrescription = $canEdit && ! $prescriptionLocked && ! $conclusionLocked;
        $diseases = $canEditBeforePrescription ? Disease::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'disease_code']) : collect();
        // Chỉ gợi ý hướng xử trí từng được chính bác sĩ ghi nhận.
        $dispositionOptions = $canEdit ? VisitConclusion::where('doctor_id', $doctor->id)
            ->whereNotNull('disposition')->where('disposition', '!=', '')
            ->distinct()->orderBy('disposition')->limit(30)->pluck('disposition') : collect();
        return view('doctor.visits.show', compact('doctor', 'record', 'check', 'conclusionPrerequisites', 'canEdit', 'canEditBeforePrescription', 'prescriptionLocked', 'conclusionLocked', 'canComplete', 'diseases', 'dispositionOptions'));
    }

    public function complete(Request $request, int $visit, DoctorVisitReadiness $readiness): RedirectResponse
    {
        DB::transaction(function () use ($request, $visit, $readiness): void {
            // Khóa lượt trước khi kiểm tra quyền, tiến độ và chuyển trạng thái.
            $record = MedicalVisit::with('status')->whereKey($visit)
                ->lockForUpdate()->firstOrFail();
            $record->assertNotBeforeAppointmentDay();
            if (! in_array($record->status?->name, ['IN_EXAMINATION', 'RETURNING'], true)) {
                throw ValidationException::withMessages(['visit' => 'Lượt khám chưa ở trạng thái cho phép hoàn tất.']);
            }
            $check = $readiness->check($record);
            if (! $check['ready']) {
                throw ValidationException::withMessages(['visit' => implode(' ', $check['reasons'])]);
            }

            $finishedAt = now();
            $startedAt = $record->examination_started_at;
            $recoveredStart = ! $startedAt;
            if (! $startedAt) {
                // Khôi phục thời điểm thực tế từ hàng đợi khám ban đầu, không dùng giờ tiếp nhận để đoán.
                $startedAt = $record->queues()->where('queue_type', 'INITIAL_EXAM')
                    ->whereNotNull('service_started_at')->orderBy('service_started_at')->lockForUpdate()->first()?->service_started_at;
                if (! $startedAt) {
                    throw ValidationException::withMessages(['visit' => 'Lượt khám thiếu thời gian bắt đầu và không có thời gian bắt đầu trong hàng đợi. Vui lòng liên hệ quản trị viên kiểm tra dữ liệu.']);
                }
            }
            if ($startedAt->gt($finishedAt)) {
                throw ValidationException::withMessages(['visit' => 'Thời gian bắt đầu khám đang ở tương lai. Vui lòng kiểm tra giờ hệ thống và dữ liệu lượt khám.']);
            }
            // Đồng bộ cùng quy trình Admin; lỗi ở bất kỳ bước nào đều hoàn tác giao dịch.
            $completed = $record->transitionStatus('COMPLETED');
            $record->update(['visit_status_id' => $completed->id, 'examination_started_at' => $startedAt, 'examination_ended_at' => $finishedAt]);
            $record->completeOpenQueues($finishedAt);
            $record->synchronizeAppointment('COMPLETED');
            AuditLog::create(['user_id' => $request->user()->id, 'role_name_snapshot' => 'Doctor', 'action' => 'UPDATE',
                'entity_type' => 'medical_visits', 'entity_id' => $record->id, 'entity_business_code' => $record->visit_code,
                'description' => $recoveredStart ? 'Bác sĩ hoàn tất lượt khám; khôi phục thời gian bắt đầu từ hàng đợi khám ban đầu.' : 'Bác sĩ hoàn tất lượt khám.', 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now()]);
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã hoàn tất lượt khám.');
    }

    public function resultDocument(Request $request, int $visit, int $result, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['pdf', 'image'], true), 404);

        // Tệp phải thuộc đúng kết quả và lượt khám được yêu cầu.
        $record = ServiceResult::query()->whereKey($result)
            ->whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($query) => $query
                ->whereKey($visit))
            ->firstOrFail();

        $path = $type === 'pdf' ? $record->result_pdf_path : $record->result_image_path;
        // Bộ xử lý dùng chung kiểm tra đường dẫn, loại tệp và không lưu đệm nội dung y tế.
        return app(ClinicalDocument::class)->response($path, 'service-results/'.$record->service_order_item_id, $type);
    }
}
