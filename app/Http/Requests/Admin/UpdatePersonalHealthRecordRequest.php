<?php

namespace App\Http\Requests\Admin;

class UpdatePersonalHealthRecordRequest extends StorePersonalHealthRecordRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        /*
         * Không cho đổi bệnh nhân sở hữu bản ghi sức khỏe.
         * Controller sẽ giữ nguyên patient_id và patient_code.
         */
        unset($rules['patient_id']);

        return $rules;
    }
}
