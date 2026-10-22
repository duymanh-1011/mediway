<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceResultRequest;
use App\Http\Requests\Admin\UpdateServiceResultRequest;
use App\Models\ClinicalDocument;
use App\Models\ServiceOrderItem;
use App\Models\ServiceResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceResultController extends Controller
{
    public function document(int $id, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['pdf', 'image'], true), 404);
        $result = ServiceResult::findOrFail($id);

        return app(ClinicalDocument::class)->response(
            $result->getAttribute('result_'.$type.'_path'), 'service-results/'.$result->service_order_item_id, $type
        );
    }

    /**
     * Danh sách kết quả dịch vụ.
     */
    public function index(Request $request)
    {
        $query = ServiceResult::query()
            ->with([
                'serviceOrderItem.serviceOrder:id,service_order_code,medical_visit_id',
                'serviceOrderItem.serviceOrder.medicalVisit:id,visit_code',
                'serviceOrderItem.medicalService:id,service_code,name',
                'serviceOrderItem.serviceStatus:id,name',
                'verifier:id,business_code',
            ]);

        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'result_summary',
                        'like',
                        '%'.$keyword.'%'
                    )
                    ->orWhere(
                        'result_conclusion',
                        'like',
                        '%'.$keyword.'%'
                    )
                    ->orWhereHas(
                        'serviceOrderItem.serviceOrder',
                        function ($orderQuery) use ($keyword) {
                            $orderQuery->where(
                                'service_order_code',
                                'like',
                                '%'.$keyword.'%'
                            );
                        }
                    )
                    ->orWhereHas(
                        'serviceOrderItem.serviceOrder.medicalVisit',
                        function ($visitQuery) use ($keyword) {
                            $visitQuery->where(
                                'visit_code',
                                'like',
                                '%'.$keyword.'%'
                            );
                        }
                    )
                    ->orWhereHas(
                        'serviceOrderItem.medicalService',
                        function ($serviceQuery) use ($keyword) {
                            $serviceQuery
                                ->where(
                                    'service_code',
                                    'like',
                                    '%'.$keyword.'%'
                                )
                                ->orWhere(
                                    'name',
                                    'like',
                                    '%'.$keyword.'%'
                                );
                        }
                    )
                    ->orWhereHas(
                        'verifier',
                        function ($userQuery) use ($keyword) {
                            $userQuery->where(
                                'business_code',
                                'like',
                                '%'.$keyword.'%'
                            );
                        }
                    );
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'resulted_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'resulted_at',
                '<=',
                $request->date_to
            );
        }

        if ($request->filled('verified_by_user_id')) {
            $query->where(
                'verified_by_user_id',
                $request->verified_by_user_id
            );
        }

        $verifiers = DB::table('users')
            ->whereIn(
                'id',
                ServiceResult::query()
                    ->whereNotNull('verified_by_user_id')
                    ->select('verified_by_user_id')
            )
            ->orderBy('business_code')
            ->get([
                'id',
                'business_code',
            ]);

        $results = $query
            ->orderByDesc('resulted_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.service-results.index',
            compact(
                'results',
                'verifiers'
            )
        );
    }

    /**
     * Form tạo kết quả dịch vụ.
     */
    public function create(
        ServiceOrderItem $serviceOrderItem
    ) {
        $serviceOrderItem->load([
            'serviceOrder.medicalVisit',
            'serviceOrder.orderedByDoctor',
            'medicalService',
            'serviceStatus',
            'performer',
            'result',
        ]);

        if ($serviceOrderItem->result) {
            return redirect()
                ->route(
                    'admin.service-results.show',
                    $serviceOrderItem->result->id
                )
                ->with(
                    'error',
                    'Dịch vụ này đã có kết quả.'
                );
        }

        if (
            $serviceOrderItem
                ->serviceStatus
                ?->name
            !== 'PROCESSING'
        ) {
            return redirect()
                ->route(
                    'admin.service-orders.show',
                    $serviceOrderItem->service_order_id
                )
                ->with(
                    'error',
                    'Chỉ có thể nhập kết quả khi dịch vụ đang được thực hiện.'
                );
        }

        $verifiers = DB::table('users')
            ->join(
                'roles',
                'roles.id',
                '=',
                'users.role_id'
            )
            ->where(
                'roles.name',
                'Doctor'
            )
            ->orderBy('users.business_code')
            ->get([
                'users.id',
                'users.business_code',
            ]);

        return view(
            'admin.service-results.create',
            compact(
                'serviceOrderItem',
                'verifiers'
            )
        );
    }

    /**
     * Lưu kết quả dịch vụ.
     */
    public function store(
        StoreServiceResultRequest $request,
        ServiceOrderItem $serviceOrderItem
    ) {
        $validated = $request->validated();

        $result = ClinicalDocument::transaction(
            function (ClinicalDocument $documents) use (
                $request,
                $serviceOrderItem,
                $validated
            ) {
                $lockedItem = ServiceOrderItem::query()
                    ->with([
                        'serviceOrder',
                        'medicalService',
                        'serviceStatus',
                        'result',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $serviceOrderItem->id
                    );

                if ($lockedItem->result) {
                    return $lockedItem->result;
                }

                if (
                    $lockedItem
                        ->serviceStatus
                        ?->name
                    !== 'PROCESSING'
                ) {
                    return null;
                }

                $paths = $documents->store($request, 'service-results/'.$lockedItem->id, ClinicalDocument::RESULT_FILES);
                $result = ServiceResult::create([
                    'service_order_item_id' => $lockedItem->id,

                    'result_summary' => $validated['result_summary']
                        ?? null,

                    'result_conclusion' => $validated['result_conclusion']
                        ?? null,

                    'result_parameters' => null,

                    'result_pdf_path' => $paths['result_pdf_path']
                        ?? null,

                    'result_image_path' => $paths['result_image_path']
                        ?? null,

                    'verified_by_user_id' => $validated['verified_by_user_id']
                        ?? null,

                    'resulted_at' => $validated['resulted_at']
                        ?? null,

                    'notes' => $validated['notes']
                        ?? null,
                ]);

                if (! $result->hasContent()) {
                    throw ValidationException::withMessages(['result_summary' => 'Kết quả phải có nội dung hoặc tệp hợp lệ còn tồn tại.']);
                }

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

                $newValues = [
                    'service_order_item_id' => $result->service_order_item_id,

                    'result_summary' => $result->result_summary,

                    'result_conclusion' => $result->result_conclusion,

                    'result_pdf_path' => $result->result_pdf_path,

                    'result_image_path' => $result->result_image_path,

                    'verified_by_user_id' => $result->verified_by_user_id,

                    'resulted_at' => $result->resulted_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                    'notes' => $result->notes,
                ];

                $orderCode =
                    $lockedItem
                        ->serviceOrder
                        ?->service_order_code;

                $serviceCode =
                    $lockedItem
                        ->medicalService
                        ?->service_code;

                $description =
                    'Tạo kết quả dịch vụ';

                if ($orderCode && $serviceCode) {
                    $description .=
                        ' của phiếu '
                        .$orderCode
                        .', dịch vụ '
                        .$serviceCode
                        .'.';
                } else {
                    $description .= '.';
                }

                DB::table('audit_logs')->insert([
                    'user_id' => Auth::id(),

                    'role_name_snapshot' => $roleNameSnapshot,

                    'action' => 'CREATE',

                    'entity_type' => 'service_results',

                    'entity_id' => $result->id,

                    'entity_business_code' => null,

                    'old_values' => json_encode(
                        [],
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

                    'description' => $description,

                    'ip_address' => $request->ip(),

                    'user_agent' => $request->userAgent(),

                    'performed_at' => now(),

                    'created_at' => now(),
                ]);

                return $result;
            }
        );

        if (! $result) {
            return redirect()
                ->route(
                    'admin.service-orders.show',
                    $serviceOrderItem->service_order_id
                )
                ->with(
                    'error',
                    'Không thể tạo kết quả vì dịch vụ không còn ở trạng thái PROCESSING.'
                );
        }

        return redirect()
            ->route(
                'admin.service-results.show',
                $result->id
            )
            ->with(
                'success',
                'Tạo kết quả dịch vụ thành công.'
            );
    }

    /**
     * Chi tiết kết quả dịch vụ.
     */
    public function show(int $id)
    {
        $result = ServiceResult::query()
            ->with([
                'serviceOrderItem.serviceOrder',
                'serviceOrderItem.serviceOrder.medicalVisit',
                'serviceOrderItem.serviceOrder.orderedByDoctor',
                'serviceOrderItem.medicalService',
                'serviceOrderItem.serviceStatus',
                'serviceOrderItem.performer',
                'verifier:id,business_code',
            ])
            ->findOrFail($id);

        return view(
            'admin.service-results.show',
            compact('result')
        );
    }

    /**
     * Form sửa kết quả dịch vụ.
     */
    public function edit(int $id)
    {
        $result = ServiceResult::query()
            ->with([
                'serviceOrderItem.serviceOrder',
                'serviceOrderItem.serviceOrder.medicalVisit',
                'serviceOrderItem.serviceOrder.orderedByDoctor',
                'serviceOrderItem.medicalService',
                'serviceOrderItem.serviceStatus',
                'serviceOrderItem.performer',
                'verifier:id,business_code',
            ])
            ->findOrFail($id);

        $verifiers = DB::table('users')
            ->join(
                'roles',
                'roles.id',
                '=',
                'users.role_id'
            )
            ->where(
                'roles.name',
                'Doctor'
            )
            ->orderBy('users.business_code')
            ->get([
                'users.id',
                'users.business_code',
            ]);

        return view(
            'admin.service-results.edit',
            compact(
                'result',
                'verifiers'
            )
        );
    }

    /**
     * Cập nhật kết quả dịch vụ.
     */
    public function update(
        UpdateServiceResultRequest $request,
        int $id
    ) {
        $result = ServiceResult::query()
            ->with([
                'serviceOrderItem.serviceOrder',
                'serviceOrderItem.medicalService',
            ])
            ->findOrFail($id);

        $validated = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Không cập nhật result_parameters.
        | Trường này không còn hiển thị trên form.
        |--------------------------------------------------------------------------
        */
        $updateData = [
            'result_summary' => $validated['result_summary']
                ?? null,

            'result_conclusion' => $validated['result_conclusion']
                ?? null,

            'verified_by_user_id' => $validated['verified_by_user_id']
                ?? null,

            'resulted_at' => $validated['resulted_at']
                ?? null,

            'notes' => $validated['notes']
                ?? null,
        ];

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

        ClinicalDocument::transaction(
            function (ClinicalDocument $documents) use (
                $request,
                $result,
                $updateData,
                $roleNameSnapshot
            ) {
                ServiceOrderItem::whereKey($result->service_order_item_id)->lockForUpdate()->firstOrFail();
                $result = ServiceResult::with(['serviceOrderItem.serviceOrder', 'serviceOrderItem.medicalService'])
                    ->whereKey($result->id)->lockForUpdate()->firstOrFail();
                $oldValues = $result->only(['result_summary', 'result_conclusion', 'result_parameters', 'result_pdf_path', 'result_image_path', 'verified_by_user_id', 'notes']);
                $oldValues['resulted_at'] = $result->resulted_at?->format('Y-m-d H:i:s');
                $updateData = array_merge($updateData, $documents->store($request, 'service-results/'.$result->service_order_item_id, ClinicalDocument::RESULT_FILES));
                if (! ServiceResult::hasContentValues(array_merge($result->getAttributes(), $updateData), $result->service_order_item_id)) {
                    throw ValidationException::withMessages(['result_summary' => 'Kết quả phải có nội dung hoặc tệp hợp lệ còn tồn tại.']);
                }
                $result->update(
                    $updateData
                );

                $result->refresh();

                $newValues = [
                    'result_summary' => $result->result_summary,

                    'result_conclusion' => $result->result_conclusion,

                    'result_parameters' => $result->result_parameters,

                    'result_pdf_path' => $result->result_pdf_path,

                    'result_image_path' => $result->result_image_path,

                    'verified_by_user_id' => $result->verified_by_user_id,

                    'resulted_at' => $result->resulted_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                    'notes' => $result->notes,
                ];

                $orderCode =
                    $result
                        ->serviceOrderItem
                        ?->serviceOrder
                        ?->service_order_code;

                $serviceCode =
                    $result
                        ->serviceOrderItem
                        ?->medicalService
                        ?->service_code;

                $description =
                    'Cập nhật kết quả dịch vụ';

                if ($orderCode && $serviceCode) {
                    $description .=
                        ' của phiếu '
                        .$orderCode
                        .', dịch vụ '
                        .$serviceCode
                        .'.';
                } else {
                    $description .= '.';
                }

                DB::table('audit_logs')->insert([
                    'user_id' => Auth::id(),

                    'role_name_snapshot' => $roleNameSnapshot,

                    'action' => 'UPDATE',

                    'entity_type' => 'service_results',

                    'entity_id' => $result->id,

                    'entity_business_code' => null,

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

                    'description' => $description,

                    'ip_address' => $request->ip(),

                    'user_agent' => $request->userAgent(),

                    'performed_at' => now(),

                    'created_at' => now(),
                ]);
            }
        );

        return redirect()
            ->route(
                'admin.service-results.show',
                $result->id
            )
            ->with(
                'success',
                'Cập nhật kết quả dịch vụ thành công.'
            );
    }
}
