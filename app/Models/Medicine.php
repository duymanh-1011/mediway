<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    /** Đơn vị cấp phát đếm theo vật phẩm nguyên; không áp dụng cho liều mỗi lần. */
    public const WHOLE_QUANTITY_UNITS = ['viên', 'vỉ', 'hộp', 'chai', 'lọ', 'gói', 'ống', 'tuýp', 'túi', 'miếng', 'ống tiêm', 'bình'];

    public static function requiresWholeQuantity(?string $unit): bool
    {
        return in_array(mb_strtolower(trim((string) $unit), 'UTF-8'), self::WHOLE_QUANTITY_UNITS, true);
    }

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
