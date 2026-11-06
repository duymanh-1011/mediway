<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceResult extends Model
{
    protected $table = 'service_results';

    protected $fillable = [
        'service_order_item_id',
        'result_summary',
        'result_conclusion',
        'result_parameters',
        'result_pdf_path',
        'result_image_path',
        'verified_by_user_id',
        'resulted_at',
        'notes',
    ];

    protected $casts = [
        'result_parameters' => 'array',
        'resulted_at' => 'datetime',
    ];

    // Dấu vân tay của bản đang mở giúp từ chối biểu mẫu cũ khi có người lưu trước.
    public static function editVersion(?self $result): string
    {
        if (! $result) return hash('sha256', 'no-result');
        return hash('sha256', json_encode([
            $result->id, $result->result_summary, $result->result_conclusion,
            $result->result_parameters, $result->result_pdf_path, $result->result_image_path,
            $result->verified_by_user_id, $result->patient_publication_status,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /** Các chỉ số mới lưu dưới dạng danh sách {name, value, unit}; null là định dạng cũ. */
    public static function editableParameters(mixed $parameters): ?array
    {
        if (is_string($parameters)) {
            $parameters = json_decode($parameters, true);
        }
        if ($parameters === null || $parameters === []) return [];
        if (! is_array($parameters) || ! array_is_list($parameters)) return null;
        foreach ($parameters as $row) {
            if (! is_array($row) || ! isset($row['name'], $row['value'])
                || ! is_string($row['name']) || ! is_string($row['value'])
                || (isset($row['unit']) && ! is_string($row['unit']))
                || array_diff(array_keys($row), ['name', 'value', 'unit'])) return null;
        }
        return $parameters;
    }

    /** Định dạng cũ vẫn được hiển thị, không tự chuyển đổi hay ghi đè. */
    public function parameterDisplayRows(): array
    {
        $rows = self::editableParameters($this->result_parameters);
        if ($rows !== null) {
            return array_map(fn ($row) => [
                'label' => $row['name'],
                'value' => trim($row['value'].' '.($row['unit'] ?? '')),
            ], $rows);
        }
        return app(AuditValuePresenter::class)->rows($this->result_parameters, '', true);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function hasContentValues(array $values, ?int $serviceOrderItemId = null): bool
    {
        foreach (['result_summary', 'result_conclusion'] as $field) {
            if (is_string($values[$field] ?? null) && trim($values[$field]) !== '') {
                return true;
            }
        }

        // Chỉ số có tên và giá trị hợp lệ cũng được coi là nội dung kết quả.
        $parameters = $values['result_parameters'] ?? null;
        if (is_string($parameters)) $parameters = json_decode($parameters, true);
        $rows = self::editableParameters($parameters);
        if ($rows !== null) {
            foreach ($rows as $row) {
                if (trim($row['name']) !== '' && trim($row['value']) !== '') return true;
            }
        } elseif (is_array($parameters)) {
            // Dữ liệu cũ có cấu trúc khác chỉ được đọc, không tự sửa định dạng.
            foreach (app(AuditValuePresenter::class)->rows($parameters, '', true) as $row) {
                if (trim((string) $row['value']) !== '') return true;
            }
        }

        if ($serviceOrderItemId) {
            foreach (['pdf', 'image'] as $type) {
                $path = $values['result_'.$type.'_path'] ?? null;
                if (is_string($path) && app(ClinicalDocument::class)->exists($path, 'service-results/'.$serviceOrderItemId, $type)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasContent(): bool
    {
        return self::hasContentValues($this->getAttributes(), $this->service_order_item_id);
    }

    public function hasDocument(string $type): bool
    {
        return in_array($type, ['pdf', 'image'], true)
            && app(ClinicalDocument::class)->exists($this->getAttribute('result_'.$type.'_path'), 'service-results/'.$this->service_order_item_id, $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Quan hệ
    |--------------------------------------------------------------------------
    */

    /**
     * Kết quả thuộc chi tiết chỉ định nào.
     *
     * service_order_item_id là FK + UNIQUE,
     * nên mỗi item chỉ có tối đa một kết quả.
     */
    public function serviceOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrderItem::class,
            'service_order_item_id'
        );
    }

    /**
     * Người xác nhận kết quả.
     *
     * Có thể NULL.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by_user_id'
        );
    }
}
