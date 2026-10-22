<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LookupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(AdminLookup::TYPES))],
            'q' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', Rule::in(['all', 'active'])],
        ]);
        [$model, $code, $name] = AdminLookup::TYPES[$data['type']];
        $term = trim($data['q'] ?? '');
        $query = $model::query()
            ->when(($data['scope'] ?? 'all') === 'active', fn ($q) => $q->where('status', 'ACTIVE'))
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where($name, 'like', '%'.$term.'%')->orWhere($code, 'like', '%'.$term.'%')))
            ->orderBy($name)->orderBy('id');
        $records = $query->limit(21)->get(['id', $code, $name]);

        return response()->json([
            'results' => $records->take(20)->map(fn ($record) => [
                'value' => (string) $record->id, 'label' => $record->{$code}.' — '.$record->{$name},
            ])->values(),
            'has_more' => $records->count() > 20,
        ]);
    }
}
