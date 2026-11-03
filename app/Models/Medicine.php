<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'medicine_code',
        'name',
        'active_ingredient',
        'strength',
        'dosage_form',
        'unit',
        'price_per_unit',
        'price_per_blister',
        'price_per_box',
        'route_of_administration',
        'manufacturer',
        'country_of_origin',
        'description',
        'indications',
        'contraindications',
        'warnings',
        'common_side_effects',
        'storage_instructions',
        'status',
        'notes',
    ];
    /** Các đơn vị có giá độc lập; đơn vị cơ bản có thể là viên, chai hoặc gói. */
    public function salePrices(): array
    {
        $prices = [];
        if ($this->unit && $this->price_per_unit !== null) {
            $prices[$this->unit] = $this->price_per_unit;
        }
        if ($this->price_per_blister !== null) $prices['vỉ'] = $this->price_per_blister;
        if ($this->price_per_box !== null) $prices['hộp'] = $this->price_per_box;
        return $prices;
    }
}
