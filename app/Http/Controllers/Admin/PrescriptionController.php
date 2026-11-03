<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePrescriptionRequest;
use App\Http\Requests\Admin\StorePrescriptionRequest;
use App\Models\ClinicalDocument;
use App\Models\ClinicalTimeline;
use App\Models\MedicalVisit;
use App\Models\Prescription;
use App\Models\Medicine;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\Doctor;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrescriptionController extends Controller
{
    public function create(MedicalVisit $visit)
    {
        $visit->load(['status', 'patient', 'doctor', 'appointment']);
        if (! $visit->canRecordClinicalData()) {
            return redirect()->route('admin.visits.show', $visit)->with('error', 'Chỉ có thể kê đơn khi bác sĩ đang khám hoặc khám lại.');
        }
        if ($visit->prescription()->exists()) {
            return redirect()->route('admin.visits.show', $visit)->with('error', 'Lượt khám này đã có đơn thuốc.');
        }
        $medicines = Medicine::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'medicine_code', 'name', 'strength', 'unit', 'price_per_unit', 'price_per_blister', 'price_per_box']);
        $saleUnits = $medicines->flatMap(fn ($medicine) => array_keys($medicine->salePrices()))->unique()->values();

        return view('admin.prescriptions.create', compact('visit', 'medicines', 'saleUnits'));
    }

    public function store(StorePrescriptionRequest $request, MedicalVisit $visit)
    {
        $data = $request->validated();
        $prescription = DB::transaction(function () use ($visit, $data, $request) {
            $lockedVisit = $visit->lockForClinicalWrite();
            ClinicalTimeline::assertEventTime(now()->format('Y-m-d H:i:s'), $lockedVisit->examination_started_at ?? $lockedVisit->checked_in_at, 'prescription', 'Thời điểm kê đơn');
            if ($lockedVisit->prescription()->exists()) {
                throw ValidationException::withMessages(['prescription' => 'Lượt khám này đã có đơn thuốc.']);
            }
            $doctor = Doctor::whereKey($lockedVisit->doctor_id)->lockForUpdate()->first();
            if (! $doctor || $doctor->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['prescription' => 'Bác sĩ của lượt khám không còn hoạt động. Vui lòng kiểm tra lại.']);
            }
            $ids = collect($data['items'])->pluck('medicine_id')->map(fn ($id) => (int) $id);
            $medicines = Medicine::whereIn('id', $ids)->where('status', 'ACTIVE')->orderBy('id')->lockForUpdate()->get();
            if ($medicines->count() !== $ids->count()) {
                throw ValidationException::withMessages(['items' => 'Có thuốc vừa ngừng hoạt động. Vui lòng chọn lại.']);
            }
            $items = [];
            $totalCents = 0;
            foreach ($data['items'] as $index => $item) {
                $medicine = $medicines->firstWhere('id', (int) $item['medicine_id']);
                $price = $medicine->salePrices()[$item['usage_unit']] ?? null;
                if ($price === null) {
                    throw ValidationException::withMessages(["items.$index.usage_unit" => 'Thuốc chưa có giá cho đơn vị đã chọn. Vui lòng kiểm tra danh mục thuốc.']);
                }
                // Decimal arithmetic in integer hundredths avoids floating-point billing errors.
                $priceCents = (int) str_replace('.', '', number_format((float) $price, 2, '.', ''));
                $quantityHundredths = (int) str_replace('.', '', number_format((float) $item['quantity'], 2, '.', ''));
                if ($priceCents > intdiv(99999999999900, $quantityHundredths)) {
                    throw ValidationException::withMessages(["items.$index.quantity" => 'Thành tiền vượt giới hạn cho phép.']);
                }
                $lineCents = intdiv($priceCents * $quantityHundredths + 5000, 10000) * 100;
                $totalCents += $lineCents;
                if ($totalCents > 999999999999) {
                    throw ValidationException::withMessages(['items' => 'Tổng tiền đơn thuốc vượt giới hạn cho phép.']);
                }
                $item['unit_price'] = intdiv($priceCents, 100).'.'.str_pad((string) ($priceCents % 100), 2, '0', STR_PAD_LEFT);
                $item['line_total'] = intdiv($lineCents, 100).'.'.str_pad((string) ($lineCents % 100), 2, '0', STR_PAD_LEFT);
                $items[] = $item;
            }
            do {
                $code = 'DT'.now()->format('ymdHis').Str::upper(Str::random(6));
            } while (Prescription::where('prescription_code', $code)->exists());
            $prescription = Prescription::create([
                'prescription_code' => $code, 'medical_visit_id' => $lockedVisit->id,
                'doctor_id' => $lockedVisit->doctor_id, 'prescribed_at' => now(), 'status' => 'ACTIVE',
                'general_instructions' => $data['general_instructions'] ?? null, 'doctor_notes' => $data['doctor_notes'] ?? null,
            ]);
            $prescription->items()->createMany($items);
            AuditLog::create([
                'user_id' => $request->user()->id, 'role_name_snapshot' => 'Administrator', 'action' => 'CREATE',
                'entity_type' => 'prescriptions', 'entity_id' => $prescription->id, 'entity_business_code' => $code,
                'old_values' => [], 'new_values' => ['medical_visit_id' => $lockedVisit->id, 'medicine_count' => $ids->count()],
                'description' => 'Tạo đơn thuốc '.$code.'.', 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now(),
            ]);
            return $prescription;
        }, 3);

        return redirect()->route('admin.prescriptions.show', $prescription)->with('success', 'Tạo đơn thuốc thành công.');
    }

    public function createPayable(Request $request, Prescription $prescription)
    {
        $request->validate(['confirm_purchase' => ['accepted']]);
        $payable = DB::transaction(function () use ($prescription, $request) {
            MedicalVisit::whereKey($prescription->medical_visit_id)->lockForUpdate()->firstOrFail();
            $prescription = Prescription::with('items')->whereKey($prescription->id)->lockForUpdate()->firstOrFail();
            if ($prescription->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['confirm_purchase' => 'Chỉ có thể tạo khoản thu cho đơn thuốc đang hiệu lực.']);
            }
            if (Payable::where('prescription_id', $prescription->id)->whereDoesntHave('paymentStatus', fn ($q) => $q->where('name', 'CANCELLED'))->exists()) {
                throw ValidationException::withMessages(['confirm_purchase' => 'Đơn thuốc này đã có khoản thu. Vui lòng kiểm tra khoản thu hiện có.']);
            }
            $total = $prescription->pricedTotal();
            if ($total === null || (float) $total <= 0) {
                throw ValidationException::withMessages(['confirm_purchase' => 'Đơn thuốc chưa có đủ giá hợp lệ; cần đối chiếu trước khi thu tiền.']);
            }
            $unpaid = PaymentStatus::where('name', 'UNPAID')->where('is_active', true)->first();
            if (! $unpaid) throw ValidationException::withMessages(['confirm_purchase' => 'Chưa cấu hình trạng thái chưa thanh toán.']);
            do { $code = 'PT'.strtoupper(Str::random(20)); } while (Payable::where('payable_code', $code)->exists());
            $payable = Payable::create([
                'payable_code' => $code, 'medical_visit_id' => $prescription->medical_visit_id,
                'prescription_id' => $prescription->id, 'payment_type' => 'MEDICINE_FEE',
                'description' => 'Tiền thuốc - '.$prescription->prescription_code,
                'amount' => $total, 'payment_status_id' => $unpaid->id, 'generated_at' => now(),
            ]);
            AuditLog::create([
                'user_id' => $request->user()->id, 'role_name_snapshot' => $request->user()->role?->name,
                'action' => 'CREATE', 'entity_type' => 'payables', 'entity_id' => $payable->id,
                'entity_business_code' => $code, 'old_values' => [],
                'new_values' => ['prescription_id' => $prescription->id, 'amount' => $total],
                'description' => 'Tạo khoản thu tiền thuốc cho đơn '.$prescription->prescription_code.'.',
                'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now(),
            ]);
            return $payable;
        }, 3);
        return redirect()->route('admin.payables.show', $payable)->with('success', 'Đã tạo khoản thu tiền thuốc.');
    }

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
            if ($updateData['status'] !== $prescription->status && Payable::where('prescription_id', $prescription->id)->whereDoesntHave('paymentStatus', fn ($q) => $q->where('name', 'CANCELLED'))->exists()) {
                throw ValidationException::withMessages(['status' => 'Đơn thuốc đã có khoản thu. Vui lòng đối chiếu khoản thu trước khi thay đổi trạng thái đơn.']);
            }
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
