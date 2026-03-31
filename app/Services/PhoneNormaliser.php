<?php

namespace App\Services;

class PhoneNormaliser
{
    public static function normalise(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }

        // Keep only digits and a leading +
        $digits = preg_replace('/[^\d+]/', '', $raw);

        // 00353... → +353...
        if (str_starts_with($digits, '00353')) {
            $digits = '+' . substr($digits, 2);
        }

        // 083... / 087... etc → +35383...
        if (str_starts_with($digits, '0') && !str_starts_with($digits, '00')) {
            $digits = '+353' . substr($digits, 1);
        }

        return $digits ?: null;
    }
}
