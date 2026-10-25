<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePrescriptionRequest;
use App\Models\ClinicalDocument;
use App\Models\ClinicalTimeline;
use App\Models\MedicalVisit;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrescriptionController extends Controller
{
    public function document(int $id): StreamedResponse
    {
        $prescription = Prescription::findOrFail($id);

        return app(ClinicalDocument::class)->response($prescription->prescription_pdf_path, 'prescriptions/'.$prescription->id, 'pdf');
    }

    /**
     * Danh sách đơn thuốc.
     */
    public function index(Request $request)
    {
        $query = Prescription::query()
            ->leftJoin(
                'medical_visits',
                'medical_visits.id',
                '=',
                'prescriptions.medical_visit_id'
            )
            ->leftJoin(
                'doctors',
                'doctors.id',
                '=',
                'prescriptions.doctor_id'
            )
            ->select([
                'prescriptions.*',
                'medical_visits.visit_code',
                'doctors.doctor_code',
                'doctors.full_name as doctor_name',
            ])
            ->withCount('items')
            ->with('medicalVisit.status');

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */
        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'prescriptions.prescription_code',
                        'like',
                        '%'.$keyword.'%'
                    )
                    ->orWhere(
                        'medical_visits.visit_code',
                        'like',
                        '%'.$keyword.'%'
                    )
                    ->orWhere(
                        'doctors.doctor_code',
                        'like',
                        '%'.$keyword.'%'
                    )
                    ->orWhere(
                        'doctors.full_name',
                        'like',
                        '%'.$keyword.'%'
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $query->where(
                'prescriptions.status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc ngày kê đơn
        |--------------------------------------------------------------------------
        */
        if ($request->filled('date_from')) {
            $query->whereDate(
                'prescriptions.prescribed_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'prescriptions.prescribed_at',
                '<=',
                $request->date_to
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Các trạng thái thực tế đang có
        |--------------------------------------------------------------------------
        */
        $statuses = Prescription::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        /*
        |--------------------------------------------------------------------------
        | Phân trang
        |--------------------------------------------------------------------------
        */
        $prescriptions = $query
            ->orderByDesc('prescriptions.prescribed_at')
            ->orderByDesc('prescriptions.id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.prescriptions.index',
            compact(
                'prescriptions',
                'statuses'
            )
        );
    }

    /**
     * Chi tiết đơn thuốc.
     */
    public function show(int $id)
    {
        $prescription = Prescription::query()
            ->with([
                'medicalVisit.status',
                'items' => function ($query) {
                    $query->orderBy('id');
                },
            ])
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Lượt khám
        |--------------------------------------------------------------------------
        */
        $visit = DB::table('medical_visits')
            ->where(
                'id',
                $prescription->medical_visit_id
            )
            ->select([
                'id',
                'visit_code',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Bác sĩ
        |--------------------------------------------------------------------------
        */
        $doctor = DB::table('doctors')
            ->where(
                'id',
                $prescription->doctor_id
            )
            ->select([
                'id',
                'doctor_code',
                'full_name',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Thuốc
        |--------------------------------------------------------------------------
        */
        $medicineIds = $prescription->items
            ->pluck('medicine_id')
            ->filter()
            ->unique()
            ->values();

        $medicinesById = collect();

        if ($medicineIds->isNotEmpty()) {
            $medicinesById = DB::table('medicines')
                ->whereIn(
                    'id',
                    $medicineIds
                )
                ->select([
                    'id',
                    'medicine_code',
                    'name',
                    'active_ingredient',
                    'strength',
                    'dosage_form',
                    'unit',
                    'route_of_administration',
                    'status',
                ])
                ->get()
                ->keyBy('id');
        }

        return view(
            'admin.prescriptions.show',
            compact(
                'prescription',
                'visit',
                'doctor',
                'medicinesById'
            )
        );
    }

    /**
     * Form sửa đơn thuốc có kiểm soát.
     */
    public function edit(int $id)
    {
        $prescription = Prescription::query()
            ->withCount('items')
            ->findOrFail($id);

        abort_if($prescription->medicalVisit->status?->name === 'COMPLETED', 403, 'Lượt khám đã hoàn tất, không thể sửa đơn thuốc.');

        /*
        |--------------------------------------------------------------------------
        | Lượt khám
        |--------------------------------------------------------------------------
        */
        $visit = DB::table('medical_visits')
            ->where(
                'id',
                $prescription->medical_visit_id
            )
            ->select([
                'id',
                'visit_code',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Bác sĩ
        |--------------------------------------------------------------------------
        */
        $doctor = DB::table('doctors')
            ->where(
                'id',
                $prescription->doctor_id
            )
            ->select([
                'id',
                'doctor_code',
                'full_name',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Không tự tạo trạng thái ngoài dữ liệu hiện có
        |--------------------------------------------------------------------------
        */
        $statuses = Prescription::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view(
            'admin.prescriptions.edit',
            compact(
                'prescription',
                'visit',
                'doctor',
                'statuses'
            )
        );
    }

    /**
     * Cập nhật đơn thuốc có kiểm soát.
     */
    public function update(
        UpdatePrescriptionRequest $request,
        int $id
    ) {
        $prescription = Prescription::query()
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Validation đã được xử lý trong FormRequest
        |--------------------------------------------------------------------------
        |
        | Không nhận:
        | - medical_visit_id
        | - doctor_id
        | - prescription_code
        |
        | Vì vậy không thể thay đổi quan hệ nghiệp vụ gốc
        | thông qua request update.
        |
        */
        $validated = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu cập nhật
        |--------------------------------------------------------------------------
        */
        $updateData = [
            'prescribed_at' => $validated['prescribed_at'],

            'general_instructions' => $validated['general_instructions'] ?? null,

            'doctor_notes' => $validated['doctor_notes'] ?? null,

            'status' => $validated['status'],

        ];

        /*
        |--------------------------------------------------------------------------
        | Role snapshot của người đang thao tác
        |--------------------------------------------------------------------------
        */
        $roleNameSnapshot = null;

        if (Auth::check()) {
            $roleNameSnapshot = DB::table('users')
                ->join(
                    'roles',
                    'roles.id',
                    '=',
                    'users.role_id'
                )
                ->where(
                    'users.id',
                    Auth::id()
                )
                ->value('roles.name');
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */
        ClinicalDocument::transaction(function (ClinicalDocument $documents) use (
            $request,
            $prescription,
            $updateData,
            $roleNameSnapshot
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cập nhật đơn thuốc
            |--------------------------------------------------------------------------
            */
            $visit = MedicalVisit::with('status')->whereKey($prescription->medical_visit_id)->lockForUpdate()->firstOrFail();
            if ($visit->status?->name === 'COMPLETED') {
                throw ValidationException::withMessages(['prescription' => 'Lượt khám đã hoàn tất, không thể sửa đơn thuốc.']);
            }
            ClinicalTimeline::assertEventTime($updateData['prescribed_at'], $visit->examination_started_at ?? $visit->checked_in_at,
                'prescribed_at', 'Thời điểm kê đơn');
            $prescription = Prescription::whereKey($prescription->id)->lockForUpdate()->firstOrFail();
            $oldValues = $prescription->only(['general_instructions', 'doctor_notes', 'status', 'prescription_pdf_path']);
            $oldValues['prescribed_at'] = $prescription->prescribed_at?->format('Y-m-d H:i:s');
            $updateData = array_merge($updateData, $documents->store($request, 'prescriptions/'.$prescription->id, ['prescription_pdf' => 'prescription_pdf_path']));
            $prescription->update($updateData);

            $prescription->refresh();

            /*
            |--------------------------------------------------------------------------
            | Dữ liệu sau cập nhật
            |--------------------------------------------------------------------------
            */
            $newValues = [
                'prescribed_at' => $prescription->prescribed_at
                    ? $prescription->prescribed_at->format(
                        'Y-m-d H:i:s'
                    )
                    : null,

                'general_instructions' => $prescription->general_instructions,

                'doctor_notes' => $prescription->doctor_notes,

                'status' => $prescription->status,

                'prescription_pdf_path' => $prescription->prescription_pdf_path,
            ];

            /*
            |--------------------------------------------------------------------------
            | Ghi Audit Log
            |--------------------------------------------------------------------------
            */
            DB::table('audit_logs')->insert([
                'user_id' => Auth::id(),

                'role_name_snapshot' => $roleNameSnapshot,

                'action' => 'UPDATE',

                'entity_type' => 'prescriptions',

                'entity_id' => $prescription->id,

                'entity_business_code' => $prescription->prescription_code,

                'old_values' => json_encode(
                    $oldValues,
                    JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                ),

                'new_values' => json_encode(
                    $newValues,
                    JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                ),

                'description' => 'Cập nhật đơn thuốc '
                    .$prescription->prescription_code
                    .'.',

                'ip_address' => $request->ip(),

                'user_agent' => $request->userAgent(),

                'performed_at' => now(),

                'created_at' => now(),
            ]);
        });

        return redirect()
            ->route(
                'admin.prescriptions.show',
                $prescription->id
            )
            ->with(
                'success',
                'Cập nhật đơn thuốc thành công.'
            );
    }
}
