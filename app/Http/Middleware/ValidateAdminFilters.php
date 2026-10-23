<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateAdminFilters
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $filters = $request->all();
        $rules = [];
        foreach (array_keys($filters) as $field) {
            $rules[$field] = ['nullable', 'string', 'max:500'];
            if (str_ends_with($field, '_id')) {
                $rules[$field] = ['nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX];
            } elseif (in_array($field, ['page', 'per_page', 'limit'], true)) {
                $rules[$field] = ['nullable', 'integer', 'min:1', 'max:2147483647'];
            } elseif (in_array($field, ['date_from', 'date_to', 'date', 'work_date', 'appointment_date'], true)) {
                $rules[$field] = ['nullable', 'date_format:Y-m-d'];
            } elseif ($field === 'month') {
                $rules[$field] = ['nullable', 'date_format:Y-m'];
            } elseif (in_array($field, ['min_price', 'max_price'], true)) {
                $rules[$field] = ['nullable', 'numeric', 'min:0', 'max:9999999999.99'];
            } elseif (in_array($field, ['is_active', 'has_follow_up'], true)) {
                $rules[$field] = ['nullable', 'boolean'];
            }
            array_unshift($rules[$field], 'bail');
        }
        if ($request->filled('date_from') && $request->filled('date_to')
            && is_string($filters['date_from']) && is_string($filters['date_to'])) {
            $rules['date_to'][] = 'after_or_equal:date_from';
        }
        if ($request->filled('min_price') && $request->filled('max_price')
            && is_numeric($filters['min_price']) && is_numeric($filters['max_price'])) {
            $rules['max_price'][] = 'gte:min_price';
        }

        $validator = Validator::make($filters, $rules, [
            'page.integer' => 'Số trang chưa hợp lệ. Vui lòng dùng các nút phân trang bên dưới danh sách.',
            '*.string' => 'Vui lòng chỉ nhập một giá trị vào ô “:attribute”.',
            '*.integer' => 'Giá trị ở mục “:attribute” chưa hợp lệ. Vui lòng chọn lại hoặc nhập số nguyên.',
        ]);
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Chưa thể lọc dữ liệu. Vui lòng kiểm tra các mục được báo lỗi.', 'errors' => $validator->errors()], 422);
            }

            return redirect()->to($request->url())->withErrors($validator);
        }

        return $next($request);
    }
}
