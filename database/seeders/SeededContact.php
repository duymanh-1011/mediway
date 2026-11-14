<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

final class SeededContact
{
    public static function email(string $name): string
    {
        return Str::slug($name, '.').'@mediway.test';
    }

    public static function forProfile(string $name, string $currentAddress, ?string $permanentAddress = null): array
    {
        return [
            'email' => self::email($name),
            'hometown' => 'Hà Nội',
            'current_address' => $currentAddress,
            'permanent_address' => $permanentAddress ?? $currentAddress,
        ];
    }
}
