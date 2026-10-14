<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceResultController extends Controller
{
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
                        'result_summary',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'result_conclusion',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhereHas(
                        'serviceOrderItem.serviceOrder',
                        function ($orderQuery) use ($keyword) {
                            $orderQuery->where(
                                'service_order_code',
                                'like',
                                '%' . $keyword . '%'
                            );
                        }
                    )
                    ->orWhereHas(
                        'serviceOrderItem.serviceOrder.medicalVisit',
                        function ($visitQuery) use ($keyword) {
                            $visitQuery->where(
                                'visit_code',
                                'like',
                                '%' . $keyword . '%'
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
                                    '%' . $keyword . '%'
                                )
                                ->orWhere(
                                    'name',
                                    'like',
                                    '%' . $keyword . '%'
                                );
                        }
                    )
                    ->orWhereHas(
                        'verifier',
                        function ($userQuery) use ($keyword) {
                            $userQuery->where(
                                'business_code',
                                'like',
                                '%' . $keyword . '%'
                            );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc thời gian trả kết quả
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Lọc người xác nhận
        |--------------------------------------------------------------------------
        */
        if ($request->filled('verified_by_user_id')) {
            $query->where(
                'verified_by_user_id',
                $request->verified_by_user_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Người xác nhận đang xuất hiện trong dữ liệu
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Phân trang
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Danh sách bác sĩ có thể xác nhận kết quả
        |--------------------------------------------------------------------------
        */
        $verifiers = DB::table('users')
            ->join(
                'roles',
                'roles.id',
                '=',
                'users.role_id'
            )
            ->where('roles.name', 'Doctor')
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
     * Cập nhật kết quả dịch vụ có kiểm soát.
     */
    public function update(Request $request, int $id)
    {
        $result = ServiceResult::query()
            ->with([
                'serviceOrderItem.serviceOrder',
                'serviceOrderItem.medicalService',
            ])
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Lấy role Doctor
        |--------------------------------------------------------------------------
        */
        $doctorRoleId = DB::table('roles')
            ->where('name', 'Doctor')
            ->value('id');

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */
        $validated = $request->validate([
            'result_summary' => [
                'nullable',
                'string',
            ],

            'result_conclusion' => [
                'nullable',
                'string',
            ],

            'result_parameters' => [
                'nullable',
                'json',
            ],

            'result_pdf_path' => [
                'nullable',
                'string',
                'max:500',
            ],

            'result_image_path' => [
                'nullable',
                'string',
                'max:500',
            ],

            'verified_by_user_id' => [
                'nullable',
                'integer',

                Rule::exists('users', 'id')
                    ->where(function ($query) use ($doctorRoleId) {
                        $query->where(
                            'role_id',
                            $doctorRoleId
                        );
                    }),
            ],

            'resulted_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'confirm_update' => [
                'accepted',
            ],
        ], [
            'result_parameters.json' =>
            'Thông số kết quả phải là JSON hợp lệ.',

            'result_pdf_path.max' =>
            'Đường dẫn PDF không được vượt quá 500 ký tự.',

            'result_image_path.max' =>
            'Đường dẫn ảnh không được vượt quá 500 ký tự.',

            'verified_by_user_id.integer' =>
            'Người xác nhận không hợp lệ.',

            'verified_by_user_id.exists' =>
            'Người xác nhận phải là bác sĩ hợp lệ.',

            'resulted_at.date' =>
            'Thời gian trả kết quả không hợp lệ.',

            'confirm_update.accepted' =>
            'Bạn phải xác nhận trước khi cập nhật kết quả y tế.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu cũ để ghi Audit Log
        |--------------------------------------------------------------------------
        */
        $oldValues = [
            'result_summary' =>
            $result->result_summary,

            'result_conclusion' =>
            $result->result_conclusion,

            'result_parameters' =>
            $result->result_parameters,

            'result_pdf_path' =>
            $result->result_pdf_path,

            'result_image_path' =>
            $result->result_image_path,

            'verified_by_user_id' =>
            $result->verified_by_user_id,

            'resulted_at' =>
            $result->resulted_at
                ? $result->resulted_at->format('Y-m-d H:i:s')
                : null,

            'notes' =>
            $result->notes,
        ];

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu cập nhật
        |--------------------------------------------------------------------------
        */
        $updateData = [
            'result_summary' =>
            $validated['result_summary'] ?? null,

            'result_conclusion' =>
            $validated['result_conclusion'] ?? null,

            'result_parameters' =>
            !empty($validated['result_parameters'])
                ? json_decode(
                    $validated['result_parameters'],
                    true
                )
                : null,

            'result_pdf_path' =>
            $validated['result_pdf_path'] ?? null,

            'result_image_path' =>
            $validated['result_image_path'] ?? null,

            'verified_by_user_id' =>
            $validated['verified_by_user_id'] ?? null,

            'resulted_at' =>
            $validated['resulted_at'] ?? null,

            'notes' =>
            $validated['notes'] ?? null,
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
        DB::transaction(function () use (
            $request,
            $result,
            $updateData,
            $oldValues,
            $roleNameSnapshot
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cập nhật kết quả
            |--------------------------------------------------------------------------
            */
            $result->update($updateData);

            $result->refresh();

            /*
            |--------------------------------------------------------------------------
            | Dữ liệu sau cập nhật
            |--------------------------------------------------------------------------
            */
            $newValues = [
                'result_summary' =>
                $result->result_summary,

                'result_conclusion' =>
                $result->result_conclusion,

                'result_parameters' =>
                $result->result_parameters,

                'result_pdf_path' =>
                $result->result_pdf_path,

                'result_image_path' =>
                $result->result_image_path,

                'verified_by_user_id' =>
                $result->verified_by_user_id,

                'resulted_at' =>
                $result->resulted_at
                    ? $result->resulted_at->format('Y-m-d H:i:s')
                    : null,

                'notes' =>
                $result->notes,
            ];

            /*
            |--------------------------------------------------------------------------
            | Thông tin nghiệp vụ phục vụ mô tả Audit Log
            |--------------------------------------------------------------------------
            */
            $orderCode =
                $result->serviceOrderItem
                ?->serviceOrder
                ?->service_order_code;

            $serviceCode =
                $result->serviceOrderItem
                ?->medicalService
                ?->service_code;

            $description = 'Cập nhật kết quả dịch vụ';

            if ($orderCode && $serviceCode) {
                $description .=
                    ' của phiếu '
                    . $orderCode
                    . ', dịch vụ '
                    . $serviceCode
                    . '.';
            } else {
                $description .= '.';
            }

            /*
            |--------------------------------------------------------------------------
            | Ghi Audit Log
            |--------------------------------------------------------------------------
            */
            DB::table('audit_logs')->insert([
                'user_id' =>
                Auth::id(),

                'role_name_snapshot' =>
                $roleNameSnapshot,

                'action' =>
                'UPDATE',

                'entity_type' =>
                'service_results',

                'entity_id' =>
                $result->id,

                /*
                 * service_results không có business_code riêng.
                 */
                'entity_business_code' =>
                null,

                'old_values' =>
                json_encode(
                    $oldValues,
                    JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                ),

                'new_values' =>
                json_encode(
                    $newValues,
                    JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                ),

                'description' =>
                $description,

                'ip_address' =>
                $request->ip(),

                'user_agent' =>
                $request->userAgent(),

                'performed_at' =>
                now(),

                'created_at' =>
                now(),
            ]);
        });

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
