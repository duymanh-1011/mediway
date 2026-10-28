<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminDisplay;
use App\Models\Disease;
use App\Models\DiseaseGroup;
use App\Models\ExaminationRoom;
use App\Models\MedicalService;
use App\Models\Medicine;
use App\Models\ServiceGroup;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogStatusController extends Controller
{
    private const CATALOGS = [
        'specialties' => Specialty::class, 'examination-rooms' => ExaminationRoom::class,
        'service-groups' => ServiceGroup::class, 'medical-services' => MedicalService::class,
        'disease-groups' => DiseaseGroup::class, 'diseases' => Disease::class, 'medicines' => Medicine::class,
    ];

    public function update(Request $request, string $catalog, int $id): RedirectResponse
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);
        $data = $request->validate(['status' => ['required', 'in:ACTIVE,INACTIVE']]);
        $record = DB::transaction(function () use ($catalog, $id, $data): Model {
            $record = self::CATALOGS[$catalog]::whereKey($id)->lockForUpdate()->firstOrFail();
            $record->update($data);

            return $record;
        });

        $action = $data['status'] === 'ACTIVE' ? 'Đã kích hoạt lại ' : 'Đã ngừng hoạt động ';

        return back()->with('success', $action.mb_strtolower(AdminDisplay::label($record->getTable())).' “'.$record->name.'”.');
    }
}
