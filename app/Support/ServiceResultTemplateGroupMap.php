<?php

namespace App\Support;

final class ServiceResultTemplateGroupMap
{
    private const GROUP_TO_TEMPLATE = [
        'XN' => 'Phiếu xét nghiệm',
        'CDHA' => 'Phiếu chẩn đoán hình ảnh',
        'SA' => 'Phiếu chẩn đoán hình ảnh',
        'NS' => 'Phiếu nội soi',
        'TDCN' => 'Phiếu thăm dò chức năng',
        'TT' => 'Phiếu thực hiện dịch vụ',
        'PHCN' => 'Phiếu thực hiện dịch vụ',
        'CSSK' => 'Phiếu thực hiện dịch vụ',
    ];

    /** @param iterable<string> $groupCodes */
    public static function templateName(iterable $groupCodes): ?string
    {
        $codes = [];
        foreach ($groupCodes as $groupCode) {
            $codes[] = (string) $groupCode;
        }
        $codes = array_values(array_unique($codes));

        if (count($codes) !== 1) {
            return null;
        }

        return self::GROUP_TO_TEMPLATE[$codes[0]] ?? null;
    }
}
