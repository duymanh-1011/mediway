<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLookup;
use App\Models\Doctor;
use App\Models\Specialty;
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
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
        ]);
        [$model, $code, $name] = AdminLookup::TYPES[$data['type']];
        $term = trim($data['q'] ?? '');
        $query = $model::query()
            ->when(($data['scope'] ?? 'all') === 'active', fn ($q) => $q->where('status', 'ACTIVE'))
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where($name, 'like', '%'.$term.'%')->orWhere($code, 'like', '%'.$term.'%')))
            ->orderBy($name)->orderBy('id');
        if ($model === Doctor::class) {
            $query->with('specialty')->when(! empty($data['specialty_id']), fn ($q) => $q->forSpecialty((int) $data['specialty_id']));
            if (($data['scope'] ?? 'all') === 'active') {
                $query->whereHas('specialty', fn ($q) => $q->where('status', 'ACTIVE'));
            }
        } elseif ($model === Specialty::class && ! empty($data['doctor_id'])) {
            $query->forDoctor((int) $data['doctor_id']);
        }
        $columns = $model === Doctor::class ? ['id', $code, $name, 'specialty_id'] : ['id', $code, $name];
        $records = $query->limit(21)->get($columns);

        return response()->json([
            'results' => $records->take(20)->map(fn ($record) => [
                'value' => (string) $record->id, 'label' => $record->{$code}.' — '.$record->{$name},
                ...($model === Doctor::class ? ['specialty_id' => $record->specialty_id, 'is_general' => (bool) $record->specialty?->is_general] : []),
            ])->values(),
            'has_more' => $records->count() > 20,
        ]);
    }
}
